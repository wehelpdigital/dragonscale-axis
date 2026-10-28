<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\GamePayment;
use App\Services\Games\GamePaymentException;
use App\Services\Games\GamePayments;
use App\Services\Games\GamesSiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Games > Website > Payments: the web checkout. Every payment with the
 * receipt the buyer sent and what the AI read on it; a person approves (the
 * game then grants the item), rejects with a reason the buyer sees, has the
 * receipt read again, or sends an approval the game hasn't heard yet. The
 * payment settings (the GCash account, the AI fee, auto-approve) are here too.
 */
class GamesPaymentsController extends Controller
{
    private const PER_PAGE = 30;

    public function __construct(private GamePayments $payments) {}

    public function index(Request $request)
    {
        $status = (string) $request->input('status', '');
        $search = trim((string) $request->input('q', ''));

        $query = GamePayment::active()
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(fn ($q) => $q->where('username', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('itemName', 'like', '%' . $search . '%')
                    ->orWhere('refNumber', 'like', '%' . $search . '%')
                    ->orWhere('orderId', $search));
            })
            ->orderByRaw("CASE WHEN status IN ('review','approved','checking') THEN 0 ELSE 1 END")
            ->orderByDesc('id');

        $counts = GamePayment::active()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')->all();
        $paidTotal = (float) GamePayment::active()->where('status', GamePayment::PAID)->sum('totalPhp');

        return view('games.site.payments', [
            'payments' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'counts' => $counts,
            'paidTotal' => $paidTotal,
            'status' => $status,
            'search' => $search,
            'pay' => GamesSiteSettings::pay(),
            'qrUrl' => GamesSiteSettings::qrUrl(),
            'gcashReady' => GamesSiteSettings::gcashReady(),
        ]);
    }

    public function show(Request $request)
    {
        $payment = GamePayment::active()->findOrFail((int) $request->input('id'));

        return response()->json(['success' => true, 'payment' => [
            'id' => $payment->id,
            'status' => $payment->status,
            'statusLabel' => GamePayment::STATUSES[$payment->status] ?? $payment->status,
            'game' => (string) config('games.' . $payment->gameKey . '.name', $payment->gameKey),
            'orderId' => $payment->orderId,
            'player' => $payment->username,
            'playerId' => $payment->playerId,
            'email' => $payment->email,
            'item' => $payment->itemName,
            'category' => $payment->itemCategory,
            'usd' => round($payment->priceUsdCents / 100, 2),
            'price' => (float) $payment->pricePhp,
            'fee' => (float) $payment->feePhp,
            'total' => (float) $payment->totalPhp,
            'method' => GamePayment::METHODS[$payment->method] ?? $payment->method,
            'refNumber' => $payment->refNumber,
            'proofUrl' => $payment->proofPath ? route('games-payments.proof', ['id' => $payment->id]) : null,
            'uploads' => $payment->proofUploads,
            'uploadedAt' => $payment->proofUploadedAt?->timezone('Asia/Manila')->format('M j, Y g:i A'),
            'ai' => $payment->aiReport,
            'reviewNote' => $payment->reviewNote,
            'reviewedAt' => $payment->reviewedAt?->timezone('Asia/Manila')->format('M j, Y g:i A'),
            'fulfilledAt' => $payment->fulfilledAt?->timezone('Asia/Manila')->format('M j, Y g:i A'),
            'fulfillError' => $payment->fulfillError,
            'createdAt' => $payment->created_at?->format('M j, Y g:i A'),
            'expiresAt' => $payment->expiresAt?->timezone('Asia/Manila')->format('M j, Y g:i A'),
            'ip' => $payment->buyerIp,
            'can' => [
                'approve' => in_array($payment->status, [GamePayment::REVIEW, GamePayment::CHECKING, GamePayment::REJECTED], true) && $payment->proofPath,
                'reject' => in_array($payment->status, [GamePayment::REVIEW, GamePayment::CHECKING, GamePayment::AWAITING], true),
                'recheck' => in_array($payment->status, [GamePayment::REVIEW, GamePayment::CHECKING], true) && $payment->proofPath,
                'deliver' => $payment->status === GamePayment::APPROVED,
            ],
        ]]);
    }

    /** The buyer's receipt, for admins only (it carries names and numbers). */
    public function proof(Request $request)
    {
        $payment = GamePayment::active()->findOrFail((int) $request->input('id'));
        abort_unless($payment->proofPath && Storage::disk('public')->exists($payment->proofPath), 404);

        return response(Storage::disk('public')->get($payment->proofPath), 200, [
            'Content-Type' => $payment->proofMime ?: 'image/jpeg',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    public function approve(Request $request)
    {
        return $this->act($request, fn (GamePayment $p) => $this->payments->approve($p, (int) Auth::id(), $request->input('note')),
            fn (GamePayment $p) => $p->status === GamePayment::PAID ? 'Approved: the game gave ' . $p->username . ' the ' . $p->itemName . '.' : 'Approved, but the game has not taken it yet: ' . ($p->fulfillError ?: 'it will be tried again every minute.'));
    }

    public function reject(Request $request)
    {
        $note = trim((string) $request->input('note'));
        if ($note === '') {
            return response()->json(['success' => false, 'message' => 'Say why, in words the buyer will read.'], 422);
        }

        return $this->act($request, fn (GamePayment $p) => $this->payments->reject($p, (int) Auth::id(), $note), fn () => 'Rejected. The buyer sees your reason on the payment page.');
    }

    public function recheck(Request $request)
    {
        return $this->act($request, fn (GamePayment $p) => $this->payments->recheck($p), fn (GamePayment $p) => 'Read again: ' . ($p->aiReport['summary'] ?? $p->aiStatus));
    }

    public function deliver(Request $request)
    {
        return $this->act($request, fn (GamePayment $p) => $this->payments->fulfill($p),
            fn (GamePayment $p) => $p->status === GamePayment::PAID ? 'The game gave the item.' : 'The game did not take it: ' . $p->fulfillError);
    }

    public function saveSettings(Request $request)
    {
        $request->merge(['gcashNumber' => preg_replace('/[\s\-]+/', '', (string) $request->input('gcashNumber'))]);
        $validator = Validator::make($request->all(), [
            'gcashNumber' => ['required', 'regex:/^(09|\+639)\d{9}$/'],
            'gcashName' => ['required', 'string', 'max:80'],
            'gcashFee' => ['required', 'numeric', 'min:0', 'max:100'],
            'reviewHours' => ['required', 'integer', 'min:1', 'max:168'],
            'gcashQrFile' => ['nullable', 'image', 'max:4096'],
            'receiptGuide' => ['nullable', 'string', 'max:4000'],
        ], ['gcashNumber.regex' => 'The GCash number looks like 09XXXXXXXXX.']);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $old = GamesSiteSettings::pay();
        $values = [
            'gcashNumber' => preg_replace('/^\+63/', '0', (string) $request->input('gcashNumber')),
            'gcashName' => trim((string) $request->input('gcashName')),
            'gcashFee' => round((float) $request->input('gcashFee'), 2),
            'reviewHours' => (int) $request->input('reviewHours'),
            'aiCheck' => $request->boolean('aiCheck'),
            'aiAutoApprove' => $request->boolean('aiAutoApprove'),
            'receiptGuide' => trim((string) $request->input('receiptGuide', '')),
            'gcashQr' => $old['gcashQr'],
        ];
        if ($request->hasFile('gcashQrFile')) {
            $values['gcashQr'] = (string) $request->file('gcashQrFile')->store('games/pay', 'public');
        } elseif ($request->boolean('gcashQrReset')) {
            $values['gcashQr'] = '';
        }
        if ($old['gcashQr'] !== $values['gcashQr'] && str_starts_with((string) $old['gcashQr'], 'games/pay/')) {
            Storage::disk('public')->delete($old['gcashQr']);
        }
        GamesSiteSettings::savePay($values);

        return redirect()->route('games-payments.index')->with('success', 'Payment settings saved. The website uses them from its next page.');
    }

    private function act(Request $request, callable $do, callable $said)
    {
        $payment = GamePayment::active()->findOrFail((int) $request->input('id'));

        try {
            $payment = $do($payment);
        } catch (GamePaymentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
        }

        return response()->json(['success' => true, 'message' => $said($payment), 'status' => $payment->status]);
    }
}
