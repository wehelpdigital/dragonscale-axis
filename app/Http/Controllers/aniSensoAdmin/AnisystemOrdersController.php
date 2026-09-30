<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsSiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * AniSystem > Orders (2026-09-30): anee.io's purchases paid by hand -- GCash,
 * bank or PayPal -- with the proof the buyer sent, Anee's reading of it, and
 * the decisions.
 *
 * Everything shown is read straight from the shared database (as_orders and
 * the proof in as_order_files). Nothing is DECIDED here: approve, reject,
 * revoke and "read it again" are sent to anee.io (/mother-api/orders/...,
 * with the shared secret), whose OrderService is the one place a decision
 * is applied -- the plan started or lined up, the credits granted or taken
 * back, the buyer emailed and belled.
 *
 * The payment settings on this page are anee's `pay.manual` (the GCash
 * number, name, QR and fee, whether Anee may approve a GCash plan herself,
 * the bank account) and `pay.receiptGuide` (more for Anee to know about
 * genuine receipts), both on the settings shelf.
 */
class AnisystemOrdersController extends Controller
{
    private const PER_PAGE = 30;

    public const PAY_KEY = 'pay.manual';

    public const GUIDE_KEY = 'pay.receiptGuide';

    /** The twin of anee's App\Support\ManualPay::DEFAULTS; keep them alike. */
    public const PAY_DEFAULTS = [
        'gcashNumber' => '09569044144',
        'gcashName' => 'ME****O JO*N I* T.',
        'gcashQr' => '',
        'gcashFee' => 5,
        'aiAutoApprove' => true,
        'bankName' => '',
        'bankAccountName' => '',
        'bankAccountNumber' => '',
        'bankBranch' => '',
        'bankNote' => '',
        'reviewHours' => 24,
    ];

    public function index()
    {
        $ready = true;
        try {
            $counts = DB::table('as_orders')->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status')->all();
        } catch (\Throwable $e) {
            $counts = [];
            $ready = false;
        }
        $pay = $this->pay();
        $base = rtrim((string) config('anisystem.url'), '/');
        $qrUrl = $pay['gcashQr'] !== ''
            ? (preg_match('#^https?://#i', $pay['gcashQr']) ? $pay['gcashQr'] : Storage::disk('public')->url($pay['gcashQr']))
            : $base . '/images/pay/gcash-qr.png';

        return view('aniSensoAdmin.orders.index', [
            'counts' => $counts,
            'ready' => $ready,
            'pay' => $pay,
            'qrUrl' => $qrUrl,
            'guide' => (string) AsSiteSetting::get(self::GUIDE_KEY, ''),
            'linked' => filled(config('services.anisystem_media.token')),
        ]);
    }

    public function data(Request $request)
    {
        $status = (string) $request->query('status', 'review');
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $rows = DB::table('as_orders as o')
            ->leftJoin('anisystem_users as u', 'u.id', '=', 'o.userId')
            ->when($status !== 'all', fn ($w) => $w->where('o.status', $status))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('o.orderNumber', 'like', "%{$q}%")
                ->orWhere('o.refNumber', 'like', "%{$q}%")->orWhere('u.email', 'like', "%{$q}%")
                ->orWhereRaw("CONCAT(u.firstName, ' ', u.lastName) LIKE ?", ["%{$q}%"])))
            ->orderByDesc('o.id')
            ->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE + 1)
            ->get(['o.id', 'o.orderNumber', 'o.status', 'o.kind', 'o.itemName', 'o.method', 'o.currency', 'o.total', 'o.aiStatus', 'o.decidedBy',
                'o.created_at', 'o.submittedAt', 'u.firstName', 'u.lastName', 'u.email']);

        return response()->json([
            'success' => true,
            'rows' => $rows->take(self::PER_PAGE)->map(fn ($r) => [
                'id' => $r->id, 'number' => $r->orderNumber, 'status' => $r->status, 'statusLabel' => self::statusLabel($r->status),
                'kind' => $r->kind, 'item' => $r->itemName, 'method' => self::methodLabel($r->method), 'methodKey' => $r->method,
                'total' => self::money($r->currency, $r->total), 'ai' => $r->aiStatus, 'byAi' => $r->decidedBy === 'ai',
                'buyer' => trim(($r->firstName ?? '') . ' ' . ($r->lastName ?? '')) ?: 'Deleted account', 'email' => $r->email,
                'at' => self::at($r->submittedAt ?? $r->created_at),
            ])->values(),
            'more' => $rows->count() > self::PER_PAGE,
        ]);
    }

    public function one(Request $request)
    {
        $o = DB::table('as_orders')->where('id', (int) $request->query('id'))->first();
        abort_unless($o, 404);
        $u = DB::table('anisystem_users')->where('id', $o->userId)->first(['id', 'firstName', 'lastName', 'email', 'phone']);
        $file = $o->proofFileId ? DB::table('as_order_files')->where('id', $o->proofFileId)->first(['id', 'mime', 'size', 'name']) : null;
        $pay = $this->pay();

        return response()->json(['success' => true, 'data' => [
            'id' => $o->id, 'number' => $o->orderNumber, 'status' => $o->status, 'statusLabel' => self::statusLabel($o->status),
            'kind' => $o->kind, 'item' => $o->itemName, 'method' => self::methodLabel($o->method), 'methodKey' => $o->method,
            'currency' => $o->currency, 'price' => self::money($o->currency, $o->price), 'fee' => (float) $o->fee ? self::money($o->currency, $o->fee) : null,
            'total' => self::money($o->currency, $o->total),
            'buyer' => $u ? trim($u->firstName . ' ' . $u->lastName) . ' (' . $u->email . ')' : 'Deleted account',
            'refNumber' => $o->refNumber, 'note' => $o->buyerNote,
            'file' => $file ? ['mime' => $file->mime, 'name' => $file->name, 'size' => (int) $file->size, 'url' => route('anisenso-orders.file', ['id' => $o->id])] : null,
            'ai' => $o->aiReport ? json_decode($o->aiReport, true) : null,
            'expected' => ['total' => self::money($o->currency, $o->total), 'to' => $pay['gcashNumber'] . ' · ' . $pay['gcashName']],
            'timeline' => array_values(array_filter([
                ['at' => self::at($o->created_at), 'say' => 'Order opened, paying by ' . self::methodLabel($o->method)],
                $o->submittedAt ? ['at' => self::at($o->submittedAt), 'say' => 'Proof sent (' . ($o->proofKind === 'ref' ? 'reference number' : $o->proofKind) . ')'] : null,
                $o->aiCheckedAt ? ['at' => self::at($o->aiCheckedAt), 'say' => 'Anee read the receipt: ' . $o->aiStatus] : null,
                $o->approvedAt ? ['at' => self::at($o->approvedAt), 'say' => 'Approved by ' . $this->who($o->decidedBy)] : null,
                $o->rejectedAt ? ['at' => self::at($o->rejectedAt), 'say' => 'Rejected by ' . $this->who($o->decidedBy) . ($o->rejectReason ? ': ' . $o->rejectReason : '')] : null,
                $o->revokedAt ? ['at' => self::at($o->revokedAt), 'say' => 'Revoked by ' . $this->who($o->revokedBy) . ($o->revokeReason ? ': ' . $o->revokeReason : '')] : null,
            ])),
        ]]);
    }

    /** The proof, streamed to a signed-in admin only. */
    public function file(Request $request)
    {
        $o = DB::table('as_orders')->where('id', (int) $request->query('id'))->first(['id', 'orderNumber', 'proofFileId']);
        abort_unless($o && $o->proofFileId, 404);
        $f = DB::table('as_order_files')->where('id', $o->proofFileId)->first();
        abort_unless($f, 404);

        return response($f->bytes, 200, [
            'Content-Type' => $f->mime,
            'Content-Disposition' => 'inline; filename="' . $o->orderNumber . '-proof.' . ($f->mime === 'application/pdf' ? 'pdf' : 'jpg') . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Approve / reject / revoke / read again: decided by anee.io, asked from here. */
    public function act(Request $request)
    {
        $id = (int) $request->query('id', $request->input('id'));
        $action = (string) $request->query('action', $request->input('action'));
        if (! in_array($action, ['approve', 'reject', 'revoke', 'recheck'], true)) {
            return response()->json(['success' => false, 'message' => 'Unknown action.'], 422);
        }
        $token = (string) config('services.anisystem_media.token');
        $base = rtrim((string) config('anisystem.url'), '/');
        if ($token === '' || $base === '') {
            return response()->json(['success' => false, 'message' => 'This app is not linked to anee.io (ANISYSTEM_URL / ANISYSTEM_MEDIA_TOKEN).'], 422);
        }
        try {
            $res = Http::timeout($action === 'recheck' ? 150 : 45)->acceptJson()
                ->withHeaders(['X-Anee-Token' => $token])
                ->post($base . '/mother-api/orders/' . $id . '/' . $action, [
                    'admin' => mb_substr((string) (auth()->user()->name ?? 'an admin'), 0, 60),
                    'reason' => (string) $request->input('reason', ''),
                ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'anee.io could not be reached: ' . $e->getMessage()], 502);
        }
        $json = $res->json() ?? [];
        if (! $res->successful() || empty($json['success'])) {
            return response()->json(['success' => false, 'message' => $json['message'] ?? ('anee.io answered ' . $res->status() . '.')], 422);
        }
        $said = [
            'approve' => 'Approved. The purchase is applied and the buyer was told.',
            'reject' => 'Rejected. The buyer was told.',
            'revoke' => 'Revoked. What it gave was taken back and the buyer was told.',
            'recheck' => 'Anee read the receipt again.',
        ][$action];

        return response()->json(['success' => true, 'message' => $said]);
    }

    /** The payment settings anee.io reads: pay.manual and pay.receiptGuide. */
    public function saveSettings(Request $request)
    {
        // "0956 904 4144" is how the checkout page writes it; accept it so.
        $request->merge(['gcashNumber' => preg_replace('/[\s\-]+/', '', (string) $request->input('gcashNumber'))]);
        $request->validate([
            'gcashNumber' => ['required', 'regex:/^(09|\+639)\d{9}$/'],
            'gcashName' => ['required', 'string', 'max:80'],
            'gcashFee' => ['required', 'numeric', 'min:0', 'max:100'],
            'reviewHours' => ['required', 'integer', 'min:1', 'max:168'],
            'gcashQrFile' => ['nullable', 'image', 'max:4096'],
            'bankName' => ['nullable', 'string', 'max:80'],
            'bankAccountName' => ['nullable', 'string', 'max:80'],
            'bankAccountNumber' => ['nullable', 'string', 'max:40'],
            'bankBranch' => ['nullable', 'string', 'max:80'],
            'bankNote' => ['nullable', 'string', 'max:300'],
            'guide' => ['nullable', 'string', 'max:4000'],
        ], ['gcashNumber.regex' => 'The GCash number looks like 09XXXXXXXXX.']);

        $old = $this->pay();
        $pay = [
            'gcashNumber' => preg_replace('/^\+63/', '0', (string) $request->input('gcashNumber')),
            'gcashName' => trim((string) $request->input('gcashName')),
            'gcashQr' => $old['gcashQr'],
            'gcashFee' => round((float) $request->input('gcashFee'), 2),
            'aiAutoApprove' => $request->boolean('aiAutoApprove'),
            'bankName' => trim((string) $request->input('bankName')),
            'bankAccountName' => trim((string) $request->input('bankAccountName')),
            'bankAccountNumber' => trim((string) $request->input('bankAccountNumber')),
            'bankBranch' => trim((string) $request->input('bankBranch')),
            'bankNote' => trim((string) $request->input('bankNote')),
            'reviewHours' => (int) $request->input('reviewHours'),
        ];
        if ($request->hasFile('gcashQrFile')) {
            $pay['gcashQr'] = (string) $request->file('gcashQrFile')->store('anisystem/pay', 'public');
        } elseif ($request->boolean('gcashQrReset')) {
            $pay['gcashQr'] = '';
        }
        // The QR it replaced, if it was one uploaded here.
        if ($old['gcashQr'] !== $pay['gcashQr'] && str_starts_with($old['gcashQr'], 'anisystem/pay/')) {
            Storage::disk('public')->delete($old['gcashQr']);
        }

        AsSiteSetting::put(self::PAY_KEY, json_encode($pay, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        AsSiteSetting::put(self::GUIDE_KEY, trim((string) $request->input('guide', '')));

        return redirect()->route('anisenso-orders.index')->with('success', 'Payment settings saved. anee.io uses them from the next page it draws.');
    }

    // ---------------------------------------------------------------------

    private function pay(): array
    {
        $stored = json_decode((string) AsSiteSetting::get(self::PAY_KEY, ''), true);

        return array_replace(self::PAY_DEFAULTS, is_array($stored) ? array_intersect_key($stored, self::PAY_DEFAULTS) : []);
    }

    private function who(?string $by): string
    {
        if ($by === 'ai') {
            return 'Anee (automatic)';
        }
        if (str_starts_with((string) $by, 'admin:')) {
            $u = DB::table('anisystem_users')->where('id', (int) substr($by, 6))->first(['firstName', 'lastName']);

            return $u ? trim($u->firstName . ' ' . $u->lastName) . ' (anee.io admin)' : 'an anee.io admin';
        }
        if (str_starts_with((string) $by, 'mother:')) {
            return substr($by, 7) . ' (here)';
        }

        return $by ?: 'someone';
    }

    private static function statusLabel(?string $s): string
    {
        return [
            'awaiting' => 'Not paid yet', 'review' => 'To review', 'approved' => 'Approved',
            'rejected' => 'Rejected', 'revoked' => 'Revoked', 'cancelled' => 'Cancelled',
        ][$s] ?? ucfirst((string) $s);
    }

    private static function methodLabel(?string $m): string
    {
        return ['gcash' => 'GCash', 'bank' => 'Bank transfer', 'paypal' => 'PayPal'][$m] ?? ucfirst((string) $m);
    }

    private static function money(?string $currency, $v): string
    {
        return ($currency === 'PHP' ? '₱' : '$') . number_format((float) $v, 2);
    }

    private static function at($t): ?string
    {
        return $t ? \Carbon\Carbon::parse($t, config('app.timezone'))->timezone('Asia/Manila')->format('M j, Y g:i A') : null;
    }
}
