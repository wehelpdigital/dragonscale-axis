<?php

namespace App\Http\Controllers\Streamr;

use App\Models\EcomOrderPayment;
use App\Models\StreamrApiRequest;
use App\Models\StreamrOrder;
use App\Models\StreamrPaymentProof;
use App\Services\Streamr\StreamrAiNotes;
use App\Services\Streamr\StreamrConfigBuilder;
use App\Services\Streamr\StreamrSettings;
use App\Services\Streamr\StreamrTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Streamr > Payment reviews: Streamr's purchases as /ecom-orders has them,
 * with the receipt, what Streamr's AI read on it and every check. Verify and
 * Reject use the e-commerce module's own endpoint
 * (PUT /ecom-order-payments/{paymentId}/verify, OrdersController), so the
 * invoice, the audit trail and the order's status come out exactly as in
 * /ecom-orders, and the order hook rings Streamr, which applies the plan or
 * credits. The Payment settings tab is how Streamr is paid (GET /config).
 */
class StreamrPaymentsController extends StreamrController
{
    private const PER_PAGE = 30;

    public const CHIPS = [
        'review' => 'To review',
        'approved' => 'Approved',
        'auto7' => 'Auto-approved · last 7 days',
        'rejected' => 'Rejected',
        'closed' => 'Cancelled/Refunded',
        'all' => 'All',
    ];

    public const AI_CHIPS = [
        'pass' => ['AI: passed', 'bg-success'],
        'fail' => ['AI: failed', 'bg-danger'],
        'unsure' => ['AI: unsure', 'bg-warning text-dark'],
        'error' => ['AI: couldn\'t read', 'bg-secondary'],
        'skipped' => ['AI: not checked', 'bg-light text-secondary'],
        'running' => ['AI: checking', 'bg-info text-white'],
    ];

    public function index(Request $request)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Payment reviews');
        }

        $pay = StreamrSettings::pay();
        $store = StreamrSettings::storeRow();
        $lastFetch = StreamrApiRequest::where('direction', 'in')->where('path', '/api/streamr/v1/config')->where('status', 200)
            ->orderByDesc('id')->value('created_at');

        return view('streamr.payments', [
            'counts' => $this->counts(),
            'pay' => $pay,
            'store' => $store,
            'storeName' => StreamrSettings::storeName(),
            'storeSettingsUrl' => $store ? url('/ecom-store-settings?id=' . $store->id . '&tab=payment') : null,
            'qrUrls' => ['gcash' => $this->publicUrl($pay['gcashQr']), 'maya' => $this->publicUrl($pay['mayaQr'])],
            'lastFetch' => $lastFetch ? StreamrTime::parse($lastFetch) : null,
            'open' => (string) $request->query('open', ''),
            'tab' => $request->query('tab') === 'settings' || $request->session()->has('errors') ? 'settings' : 'reviews',
            'linked' => (string) config('streamr.app_url', '') !== '',
        ]);
    }

    /** The list: ?status= (a chip), ?q= (number, reference, name, email), ?page= (30 a page). */
    public function data(Request $request)
    {
        if (! $this->installed()) {
            return $this->fail('The Streamr tables are not installed yet.', 503);
        }

        $status = array_key_exists((string) $request->query('status'), self::CHIPS) ? (string) $request->query('status') : 'review';
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $query = $this->base();
        $this->chip($query, $status);
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('so.orderNumber', 'like', $like)
                    ->orWhere('o.paymentReferenceNumber', 'like', $like)
                    ->orWhere('o.clientEmail', 'like', $like)
                    ->orWhere('o.clientFirstName', 'like', $like)
                    ->orWhere('o.clientLastName', 'like', $like)
                    ->orWhere('c.storeHandle', 'like', $like)
                    ->orWhereExists(fn ($p) => $p->select(DB::raw(1))->from('ecom_order_payments as pr')
                        ->whereColumn('pr.orderId', 'o.id')->where('pr.referenceNumber', 'like', $like));
            });
        }

        $rows = $query->orderByDesc('so.id')
            ->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE + 1)
            ->get(['so.id', 'so.uuid', 'so.orderNumber', 'so.kind', 'so.variantName', 'so.total', 'so.method', 'so.aiVerdict', 'so.aiScore',
                'so.autoApproved', 'so.decidedBy', 'so.created_at', 'so.streamrUserId', 'o.id as ecomId', 'o.orderStatus',
                'o.paymentVerificationStatus', 'o.deleteStatus', 'o.clientFirstName', 'o.clientLastName', 'o.clientEmail',
                'c.storeHandle', 'c.id as clientRowId']);

        return $this->json([
            'rows' => $rows->take(self::PER_PAGE)->map(fn ($r) => $this->row($r))->values(),
            'more' => $rows->count() > self::PER_PAGE,
            'counts' => $this->counts(),
        ]);
    }

    /** One order for the review modal: /streamr-payments-one?number=STR-... */
    public function one(Request $request, $number = null)
    {
        if (! $this->installed()) {
            return $this->fail('The Streamr tables are not installed yet.', 503);
        }

        $number = trim((string) ($number ?? $request->query('number', '')));
        $row = $this->base()->where('so.orderNumber', $number)->first([
            'so.*', 'o.id as ecomId', 'o.orderStatus', 'o.paymentVerificationStatus', 'o.deleteStatus', 'o.clientFirstName',
            'o.clientLastName', 'o.clientEmail', 'o.clientPhone', 'o.grandTotal', 'o.paymentReferenceNumber', 'o.paymentPayerName',
            'o.paymentPhoneNumber', 'o.paymentNotes', 'o.created_at as orderCreatedAt', 'c.storeHandle', 'c.storeName', 'c.id as clientRowId',
        ]);
        if (! $row) {
            return $this->fail('That Streamr order was not found.', 404);
        }

        $payments = EcomOrderPayment::where('orderId', $row->ecomId)->where('deleteStatus', 1)->with('verifier')->orderBy('id')->get();
        $proofs = StreamrPaymentProof::where('streamrOrderId', $row->id)->orderBy('n')->get(StreamrPaymentProof::LIGHT);
        $audits = DB::table('ecom_order_audit_logs')->where('orderId', $row->ecomId)->where('deleteStatus', 1)->orderBy('id')
            ->get(['userName', 'actionType', 'description', 'created_at']);
        $pending = $payments->where('paymentStatus', 'pending')->sortByDesc('id')->first();
        $receiving = app(StreamrConfigBuilder::class)->receiving();
        $open = (int) $row->deleteStatus === 1 && ! in_array($row->orderStatus, ['cancelled', 'refunded'], true);

        return $this->json([
            'number' => $row->orderNumber,
            'uuid' => $row->uuid,
            'ecomId' => (int) $row->ecomId,
            'created' => StreamrTime::show($row->orderCreatedAt),
            'kind' => StreamrOrder::KINDS[$row->kind] ?? $row->kind,
            'item' => $row->variantName,
            'sku' => $row->sku,
            'total' => (float) $row->grandTotal,
            'method' => StreamrOrder::METHODS[$row->method] ?? $row->method,
            'methodKey' => $row->method,
            'status' => $this->status($row),
            'autoApproved' => (bool) $row->autoApproved,
            'decidedBy' => $row->decidedBy,
            'ai' => $this->aiChip($row->aiVerdict, $row->aiScore),
            'client' => [
                'name' => trim(trim((string) $row->clientFirstName) . ' ' . trim((string) $row->clientLastName)) ?: (string) $row->clientEmail,
                'email' => (string) $row->clientEmail,
                'phone' => (string) $row->clientPhone,
                'storeHandle' => $row->storeHandle,
                'storeName' => $row->storeName,
                'streamrUserId' => (int) $row->streamrUserId,
                'clientsUrl' => $row->clientRowId ? route('streamr-clients.index', ['open' => $row->clientRowId]) : null,
            ],
            'facts' => [
                'reference' => $row->paymentReferenceNumber,
                'payer' => $row->paymentPayerName,
                'senderPhone' => $row->paymentPhoneNumber,
                'buyerNote' => $row->paymentNotes,
            ],
            'expected' => [
                'amount' => (float) $row->grandTotal,
                'gcash' => $receiving['gcash'],
                'maya' => $receiving['maya'],
                'bank' => $receiving['bank'],
            ],
            'proofs' => $proofs->map(fn (StreamrPaymentProof $p) => [
                'n' => $p->n,
                'kind' => $p->kind,
                'mime' => $p->mime,
                'name' => $p->name,
                'size' => $p->size,
                'url' => $p->hasFile() ? route('streamr-proofs.show', ['order' => $row->orderNumber, 'n' => $p->n]) : null,
                'reference' => $p->reference,
                'submitted' => StreamrTime::show($p->submittedAt ?? $p->created_at),
                'paymentNumber' => optional($payments->firstWhere('id', $p->ecomPaymentId))->paymentNumber,
                'ai' => $this->aiBlock($p->aiReport),
                'rechecks' => array_values(array_map(fn ($r) => $this->aiBlock(is_array($r) ? $r : null),
                    array_filter((array) (($p->aiReport ?? [])['rechecks'] ?? []), 'is_array'))),
            ])->values(),
            'payments' => $payments->map(fn (EcomOrderPayment $p) => [
                'id' => $p->id,
                'number' => $p->paymentNumber,
                'status' => $p->paymentStatus,
                'statusLabel' => $p->paymentStatusLabel,
                'badge' => $p->paymentStatusBadgeClass,
                'amountSent' => (float) $p->amountSent,
                'amountVerified' => $p->amountVerified !== null ? (float) $p->amountVerified : null,
                'verifiedBy' => $p->verifiedAt ? ($p->verifier->name ?? ($p->verifiedBy ? 'An admin' : ($row->decidedBy === 'streamr_admin' ? 'Streamr admin' : 'Streamr AI'))) : null,
                'verifiedAt' => $p->verifiedAt ? StreamrTime::show($p->verifiedAt) : null,
                'invoiceUrl' => $p->invoiceToken && $p->paymentStatus === 'verified' ? url('/invoice/' . $p->invoiceToken) : null,
                'notes' => (string) $p->verificationNotes,
            ])->values(),
            'timeline' => $audits->map(fn ($a) => [
                'at' => StreamrTime::show($a->created_at),
                'who' => (string) ($a->userName ?: 'System'),
                'what' => ucfirst(str_replace('_', ' ', (string) $a->actionType)),
                'description' => (string) $a->description,
            ])->values(),
            'can' => [
                'verify' => $open && $pending !== null,
                'reject' => $open && $pending !== null,
                'paymentId' => $pending?->id,
                'amount' => $pending ? (float) $pending->amountSent : null,
            ],
            'links' => [
                'streamr' => $this->streamrUrl('/admin/billing-orders/' . $row->uuid),
                'verify' => $pending ? url('/ecom-order-payments/' . $pending->id . '/verify') : null,
            ],
        ]);
    }

    public function saveSettings(Request $request)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Payment reviews');
        }

        $clean = fn ($v) => preg_replace('/[\s\-]+/', '', (string) $v);
        $request->merge(['gcashNumber' => $clean($request->input('gcashNumber')), 'mayaNumber' => $clean($request->input('mayaNumber'))]);
        $phone = ['nullable', 'regex:/^(09|\+639)\d{9}$/'];

        $validator = Validator::make($request->all(), [
            'source' => ['required', 'in:ecom,own'],
            'gcashNumber' => $phone,
            'gcashNameMasked' => ['nullable', 'string', 'max:80'],
            'qrDisplayName' => ['nullable', 'string', 'max:80'],
            'qrAccount' => ['nullable', 'string', 'max:80'],
            'mayaNumber' => $phone,
            'mayaName' => ['nullable', 'string', 'max:80'],
            'bankName' => ['nullable', 'string', 'max:80'],
            'bankAccountName' => ['nullable', 'string', 'max:120'],
            'bankAccountNumber' => ['nullable', 'string', 'max:40'],
            'bankBranch' => ['nullable', 'string', 'max:80'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'receiptGuide' => ['nullable', 'string', 'max:4000'],
            'creditsDailyCapPhp' => ['required', 'integer', 'min:0', 'max:100000'],
            'reviewHours' => ['required', 'integer', 'min:1', 'max:168'],
        ], [
            'gcashNumber.regex' => 'The GCash number looks like 09XXXXXXXXX.',
            'mayaNumber.regex' => 'The Maya number looks like 09XXXXXXXXX.',
        ]);
        if ($validator->fails()) {
            return redirect()->route('streamr-payments.index', ['tab' => 'settings'])->withErrors($validator)->withInput();
        }

        $local = fn ($v) => trim((string) $v) === '' ? '' : StreamrSettings::localPhone((string) $v);
        StreamrSettings::savePay([
            'source' => (string) $request->input('source'),
            'gcashNumber' => $local($request->input('gcashNumber')),
            'gcashNameMasked' => trim((string) $request->input('gcashNameMasked')),
            'qrDisplayName' => trim((string) $request->input('qrDisplayName')),
            'qrAccount' => trim((string) $request->input('qrAccount')),
            'mayaNumber' => $local($request->input('mayaNumber')),
            'mayaName' => trim((string) $request->input('mayaName')),
            'bankName' => trim((string) $request->input('bankName')),
            'bankAccountName' => trim((string) $request->input('bankAccountName')),
            'bankAccountNumber' => trim((string) $request->input('bankAccountNumber')),
            'bankBranch' => trim((string) $request->input('bankBranch')),
            'instructions' => trim((string) $request->input('instructions')),
            'receiptGuide' => trim((string) $request->input('receiptGuide')),
            'autoApprovePlans' => $request->boolean('autoApprovePlans'),
            'autoApproveLifetime' => $request->boolean('autoApproveLifetime'),
            'creditsDailyCapPhp' => (int) $request->input('creditsDailyCapPhp'),
            'reviewHours' => (int) $request->input('reviewHours'),
        ], Auth::user());

        return redirect()->route('streamr-payments.index', ['tab' => 'settings'])
            ->with('success', 'Payment settings saved. Streamr reads them within 10 minutes.');
    }

    /** A GCash or Maya QR for Streamr's checkout: /streamr-payments-qr?method=gcash|maya (stored on the public disk). */
    public function uploadQr(Request $request, $method = null)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Payment reviews');
        }

        $method = (string) ($method ?? $request->query('method', ''));
        if (! in_array($method, ['gcash', 'maya'], true)) {
            return redirect()->route('streamr-payments.index', ['tab' => 'settings'])->with('error', 'Choose GCash or Maya for the QR.');
        }
        $key = $method === 'gcash' ? 'gcashQr' : 'mayaQr';
        $old = (string) StreamrSettings::pay()[$key];

        if ($request->boolean('remove')) {
            $this->deleteQr($old);
            StreamrSettings::savePay([$key => ''], Auth::user());

            return redirect()->route('streamr-payments.index', ['tab' => 'settings'])->with('success', 'QR removed.');
        }

        $validator = Validator::make($request->all(), [
            'qr' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'qr.required' => 'Choose the QR picture.',
            'qr.image' => 'The QR must be a picture (JPG, PNG or WebP).',
            'qr.mimes' => 'The QR must be a JPG, PNG or WebP picture.',
            'qr.max' => 'The QR picture must be 4 MB or smaller.',
        ]);
        if ($validator->fails()) {
            return redirect()->route('streamr-payments.index', ['tab' => 'settings'])->withErrors($validator);
        }

        // Checked by its content, not by its name.
        $file = $request->file('qr');
        $content = strtolower((string) @(new \finfo(FILEINFO_MIME_TYPE))->file((string) $file->getRealPath()));
        $exts = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        if (! isset($exts[$content])) {
            return redirect()->route('streamr-payments.index', ['tab' => 'settings'])
                ->withErrors(['qr' => 'The QR must be a JPG, PNG or WebP picture.']);
        }
        $ext = $exts[$content];
        $path = Storage::disk('public')->putFileAs('streamr/pay', $file, $method . '-qr-' . Str::lower(Str::random(16)) . '.' . $ext);
        if (! $path) {
            return redirect()->route('streamr-payments.index', ['tab' => 'settings'])->with('error', 'The QR could not be stored. Try again.');
        }
        StreamrSettings::savePay([$key => $path], Auth::user());
        if ($old !== $path) {
            $this->deleteQr($old);
        }

        return redirect()->route('streamr-payments.index', ['tab' => 'settings'])
            ->with('success', ($method === 'gcash' ? 'GCash' : 'Maya') . ' QR saved. Streamr shows it within 10 minutes.');
    }

    // ------------------------------------------------------------------

    /** Streamr orders with their /ecom-orders row, only those of the Streamr store. */
    private function base()
    {
        $store = StreamrSettings::storeName();

        return DB::table('streamr_orders as so')
            ->join('ecom_orders as o', 'o.id', '=', 'so.ecomOrderId')
            ->leftJoin('streamr_clients as c', 'c.streamrUserId', '=', 'so.streamrUserId')
            ->where('so.delete_status', 'active')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('ecom_order_items as i')
                ->whereColumn('i.orderId', 'o.id')->where('i.deleteStatus', 1)->where('i.productStore', $store));
    }

    private function chip($query, string $status): void
    {
        $pendingOrder = fn ($q) => $q->where('o.orderStatus', 'pending')->orWhereNull('o.orderStatus');

        switch ($status) {
            case 'review':
                $query->where('o.deleteStatus', 1)->where($pendingOrder)->where('o.paymentVerificationStatus', 'pending');
                break;
            case 'approved':
                $query->where('o.deleteStatus', 1)
                    ->where(fn ($q) => $q->where('o.paymentVerificationStatus', 'verified')->orWhereIn('o.orderStatus', ['complete', 'paid']))
                    ->where(fn ($q) => $q->whereNull('o.orderStatus')->orWhereNotIn('o.orderStatus', ['cancelled', 'refunded']));
                break;
            case 'auto7':
                $query->where('so.autoApproved', true)->where('so.created_at', '>=', StreamrTime::now()->subDays(7)->format('Y-m-d H:i:s'));
                break;
            case 'rejected':
                $query->where('o.deleteStatus', 1)->where($pendingOrder)->where('o.paymentVerificationStatus', 'rejected');
                break;
            case 'closed':
                $query->where(fn ($q) => $q->where('o.deleteStatus', '!=', 1)->orWhereIn('o.orderStatus', ['cancelled', 'refunded']));
                break;
        }
    }

    private function counts(): array
    {
        $counts = [];
        foreach (array_keys(self::CHIPS) as $chip) {
            try {
                $query = $this->base();
                $this->chip($query, $chip);
                $counts[$chip] = $query->count();
            } catch (\Throwable $e) {
                $counts[$chip] = 0;
            }
        }

        return $counts;
    }

    private function row(object $r): array
    {
        return [
            'number' => $r->orderNumber,
            'client' => trim(trim((string) $r->clientFirstName) . ' ' . trim((string) $r->clientLastName)) ?: (string) $r->clientEmail,
            'email' => (string) $r->clientEmail,
            'storeHandle' => $r->storeHandle,
            'item' => $r->variantName,
            'kind' => StreamrOrder::KINDS[$r->kind] ?? $r->kind,
            'total' => (float) $r->total,
            'method' => StreamrOrder::METHODS[$r->method] ?? $r->method,
            'ai' => $this->aiChip($r->aiVerdict, $r->aiScore),
            'autoApproved' => (bool) $r->autoApproved,
            'age' => ($at = StreamrTime::parse($r->created_at)) ? $at->diffForHumans() : '',
            'created' => StreamrTime::show($r->created_at),
            'status' => $this->status($r),
        ];
    }

    private function status(object $r): array
    {
        if ((int) $r->deleteStatus !== 1) {
            return ['key' => 'deleted', 'label' => 'Deleted', 'badge' => 'bg-dark'];
        }

        return match (true) {
            $r->orderStatus === 'cancelled' => ['key' => 'cancelled', 'label' => 'Cancelled', 'badge' => 'bg-secondary'],
            $r->orderStatus === 'refunded' => ['key' => 'refunded', 'label' => 'Refunded', 'badge' => 'bg-danger'],
            $r->paymentVerificationStatus === 'verified' || in_array($r->orderStatus, ['complete', 'paid'], true)
                => ['key' => 'approved', 'label' => 'Approved', 'badge' => 'bg-success'],
            $r->paymentVerificationStatus === 'rejected' => ['key' => 'rejected', 'label' => 'Rejected', 'badge' => 'bg-danger'],
            default => ['key' => 'review', 'label' => 'To review', 'badge' => 'bg-warning text-dark'],
        };
    }

    private function aiChip(?string $verdict, $score): ?array
    {
        if (! $verdict) {
            return null;
        }
        [$label, $badge] = self::AI_CHIPS[$verdict] ?? ['AI: ' . $verdict, 'bg-secondary'];
        if ($verdict === 'pass' && $score !== null) {
            $label .= ' ' . (int) $score;
        }

        return ['verdict' => $verdict, 'label' => $label, 'badge' => $badge, 'score' => $score !== null ? (int) $score : null];
    }

    private function aiBlock(?array $report): ?array
    {
        if (! is_array($report) || ! isset($report['verdict'])) {
            return null;
        }

        return [
            'verdict' => (string) $report['verdict'],
            'word' => StreamrAiNotes::VERDICTS[(string) $report['verdict']] ?? strtoupper((string) $report['verdict']),
            'chip' => $this->aiChip((string) $report['verdict'], $report['score'] ?? null),
            'score' => isset($report['score']) && is_numeric($report['score']) ? (int) $report['score'] : null,
            'summary' => (string) ($report['summary'] ?? ''),
            'checks' => array_values(array_map(fn ($c) => [
                'label' => (string) ($c['label'] ?? ''),
                'ok' => array_key_exists('ok', $c) ? $c['ok'] : null,
                'hard' => (bool) ($c['hard'] ?? false),
                'detail' => (string) ($c['detail'] ?? ''),
            ], array_filter((array) ($report['checks'] ?? []), 'is_array'))),
            'provider' => (string) ($report['provider'] ?? ''),
            'model' => (string) ($report['model'] ?? ''),
            'checkedAt' => StreamrTime::show($report['checked_at'] ?? null, ''),
        ];
    }

    private function publicUrl(string $path): ?string
    {
        if ($path === '') {
            return null;
        }
        try {
            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function deleteQr(string $path): void
    {
        if ($path !== '' && str_starts_with($path, 'streamr/pay/')) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                // An orphaned picture is harmless.
            }
        }
    }
}
