<?php

namespace App\Services\Games;

use App\Models\GamePayment;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The web checkout (Games > Website > Payments): a purchase in a web game,
 * paid on the studio website, checked here, and granted by the game server.
 *
 *   1. The game opens an order and sends the player to the website's
 *      /pay/{token}. The website asks open(): the order is read from the game
 *      server (its price, never the website's), and the payment row is made.
 *   2. The buyer sends money to the GCash account and uploads the receipt:
 *      submitGcash() keeps it and marks the payment "checking".
 *   3. check() has the AI read the receipt and the code judge it
 *      (GamesReceiptCheck). Every check passed and auto-approve on: approved.
 *      Otherwise it waits for a person (review), who approves or rejects.
 *   4. approve() tells the game server to complete the order and grant the
 *      item (fulfill()). Only then is the payment "paid". A game server that
 *      can't be reached is tried again by sweep().
 *
 * Nothing the buyer sends decides anything: a token from the address bar only
 * finds the payment; the price, the item and the player come from the game.
 */
class GamePayments
{
    public const GAME = 'salpakan';

    public function __construct(private SalpakanApiClient $game, private GamesReceiptCheck $receipts) {}

    /**
     * The payment for a checkout link, made from the game's order the first
     * time. Expired links and orders the game no longer sells are refused.
     *
     * @throws GamePaymentException
     */
    public function open(string $token, ?string $ip = null): GamePayment
    {
        if (! preg_match('/^[A-Za-z0-9]{20,64}$/', $token)) {
            throw new GamePaymentException('That payment link is not right. Open the shop in the game and try again.', 404);
        }

        $existing = GamePayment::active()->where('checkoutToken', $token)->first();
        if ($existing) {
            return $this->expireIfDue($existing);
        }

        try {
            $order = $this->game->checkout($token);
        } catch (SalpakanApiException $e) {
            if ($e->status === 404) {
                throw new GamePaymentException('That payment link was not found. Open the shop in the game and buy it again.', 404);
            }
            report($e);

            throw new GamePaymentException('The game could not be reached just now. Please try again in a moment.', 503);
        }

        $completed = ($order['status'] ?? '') === 'completed';
        if (! $completed && ! ($order['payable'] ?? false)) {
            throw new GamePaymentException('This payment link has run out. Open the shop in the game and buy it again.', 410);
        }

        $cents = (int) ($order['price_cents'] ?? 0);
        $price = (float) ($order['price_php'] ?? 0) ?: (float) ceil($cents / 100 * (float) config('games.salpakan.usd_to_php_rate', 58));
        $fee = (float) GamesSiteSettings::pay()['gcashFee'];

        try {
            return GamePayment::create([
                'gameKey' => self::GAME,
                'checkoutToken' => $token,
                'orderId' => $order['order_id'] ?? null,
                'playerId' => $order['player_id'] ?? null,
                'username' => mb_substr((string) ($order['username'] ?? ''), 0, 100) ?: null,
                'email' => mb_substr((string) ($order['email'] ?? ''), 0, 255) ?: null,
                'itemName' => mb_substr((string) ($order['item_name'] ?? 'Item'), 0, 160),
                'itemCategory' => $order['item_category'] ?? null,
                'itemSlug' => $order['item_slug'] ?? null,
                'priceUsdCents' => $cents,
                'pricePhp' => $price,
                'feePhp' => $fee,
                'totalPhp' => $price + $fee,
                'method' => 'gcash',
                'status' => $completed ? GamePayment::PAID : GamePayment::AWAITING,
                'fulfilledAt' => $completed ? now() : null,
                'buyerIp' => $ip,
                'expiresAt' => isset($order['expires_at']) ? \Carbon\Carbon::parse($order['expires_at']) : now()->addHours(48),
            ]);
        } catch (QueryException $e) {
            // The same link opened twice at once: the other request made it.
            return GamePayment::where('checkoutToken', $token)->firstOrFail();
        }
    }

    /**
     * The buyer's GCash receipt: kept on the public disk under a random name
     * (shown to admins only through Games > Website > Payments), then checked.
     *
     * @throws GamePaymentException
     */
    public function submitGcash(GamePayment $payment, UploadedFile $file, ?string $reference, ?string $ip = null): GamePayment
    {
        $payment = $this->expireIfDue($payment);
        if (! $payment->takesProof()) {
            throw new GamePaymentException(match ($payment->status) {
                GamePayment::PAID, GamePayment::APPROVED => 'This payment is already accepted.',
                GamePayment::CHECKING => 'Your receipt is being checked.',
                GamePayment::EXPIRED => 'This payment link has run out. Open the shop in the game and buy it again.',
                GamePayment::REJECTED => 'This payment was not accepted. Open the shop in the game to try again.',
                default => 'A person is already checking your receipt.',
            }, 409);
        }
        if (! GamesSiteSettings::gcashReady()) {
            throw new GamePaymentException('GCash payments are not open just now. Please try again later.', 503);
        }

        $bytes = (string) file_get_contents($file->getRealPath());
        $mime = (string) $file->getMimeType();
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if ($ext === null || $bytes === '') {
            throw new GamePaymentException('Upload the receipt as a picture (JPG, PNG or WebP).', 422);
        }

        $path = 'games/payments/' . now()->format('Y/m') . '/' . Str::random(40) . '.' . $ext;
        Storage::disk('public')->put($path, $bytes);

        $payment->update([
            'method' => 'gcash',
            'refNumber' => $reference !== null && trim($reference) !== '' ? mb_substr(trim($reference), 0, 64) : null,
            'proofPath' => $path,
            'proofMime' => $mime,
            'proofSha' => hash('sha256', $bytes),
            'proofUploadedAt' => now(),
            'proofUploads' => $payment->proofUploads + 1,
            'status' => GamePayment::CHECKING,
            'aiStatus' => null,
            'aiScore' => null,
            'aiReport' => null,
            'aiCheckedAt' => null,
            'buyerIp' => $ip ?? $payment->buyerIp,
        ]);

        return $payment->fresh();
    }

    /**
     * Read and judge a payment in "checking" (once: a run claims it, so the
     * request that took the receipt and the scheduler never both read it).
     */
    public function check(GamePayment $payment): GamePayment
    {
        $stale = now()->subSeconds((int) config('games.site.check_stale_seconds', 180));
        $claimed = GamePayment::whereKey($payment->id)->where('status', GamePayment::CHECKING)
            ->where(fn ($q) => $q->whereNull('aiStatus')->orWhere(fn ($q) => $q->where('aiStatus', 'running')->where('aiCheckedAt', '<', $stale)))
            ->update(['aiStatus' => 'running', 'aiCheckedAt' => now()]);
        if (! $claimed) {
            return $payment->fresh();
        }
        $payment = $payment->fresh();
        $pay = GamesSiteSettings::pay();

        try {
            $report = $pay['aiCheck']
                ? $this->receipts->run($payment)
                : ['verdict' => 'skipped', 'summary' => 'The AI check is switched off: a person checks every payment.', 'at' => now()->toDateTimeString()];
        } catch (\Throwable $e) {
            report($e);
            $report = ['verdict' => 'error', 'summary' => 'The check stopped with an error. A person will look.', 'at' => now()->toDateTimeString()];
        }

        $payment->update([
            'aiStatus' => $report['verdict'],
            'aiScore' => $report['read']['authenticity'] ?? null,
            'aiReport' => $report,
            'aiCheckedAt' => now(),
            'status' => GamePayment::REVIEW,
        ]);

        if ($report['verdict'] === 'pass' && $pay['aiAutoApprove']) {
            return $this->approve($payment, 'ai');
        }

        return $payment->fresh();
    }

    /**
     * Accept a payment ($by: 'ai', or the admin's id) and have the game grant
     * the item.
     *
     * @throws GamePaymentException
     */
    public function approve(GamePayment $payment, string|int $by, ?string $note = null): GamePayment
    {
        if (in_array($payment->status, [GamePayment::PAID, GamePayment::EXPIRED], true)) {
            throw new GamePaymentException('This payment is ' . strtolower(GamePayment::STATUSES[$payment->status]) . ' already.', 409);
        }

        $payment->update([
            'status' => GamePayment::APPROVED,
            'reviewedBy' => $by === 'ai' ? null : (int) $by,
            'reviewedAt' => $by === 'ai' ? null : now(),
            'reviewNote' => $note !== null && trim($note) !== '' ? trim($note) : ($by === 'ai' ? 'Approved by the AI: every check passed.' : $payment->reviewNote),
        ]);

        return $this->fulfill($payment->fresh());
    }

    /**
     * Turn a payment down with a reason the buyer sees.
     *
     * @throws GamePaymentException
     */
    public function reject(GamePayment $payment, int $by, string $note): GamePayment
    {
        if (in_array($payment->status, [GamePayment::PAID, GamePayment::APPROVED], true)) {
            throw new GamePaymentException('This payment was accepted and the item given; it can no longer be rejected here.', 409);
        }

        $payment->update(['status' => GamePayment::REJECTED, 'reviewNote' => trim($note), 'reviewedBy' => $by, 'reviewedAt' => now()]);

        return $payment->fresh();
    }

    /** Read the receipt again (a person's request). */
    public function recheck(GamePayment $payment): GamePayment
    {
        if (! in_array($payment->status, [GamePayment::REVIEW, GamePayment::CHECKING], true) || ! $payment->proofPath) {
            return $payment;
        }
        $payment->update(['status' => GamePayment::CHECKING, 'aiStatus' => null]);

        return $this->check($payment->fresh());
    }

    /** Tell the game to complete the order and grant the item; paid once it has. */
    public function fulfill(GamePayment $payment): GamePayment
    {
        if ($payment->status !== GamePayment::APPROVED) {
            return $payment;
        }

        $reference = $payment->refNumber ?: ($payment->aiReport['read']['ref'] ?? null);

        try {
            $this->game->fulfill($payment->checkoutToken, (string) $payment->method, $reference ? (string) $reference : null);
            $payment->update(['status' => GamePayment::PAID, 'fulfilledAt' => now(), 'fulfillError' => null, 'fulfillAttempts' => $payment->fulfillAttempts + 1]);
        } catch (SalpakanApiException $e) {
            $payment->update(['fulfillAttempts' => $payment->fulfillAttempts + 1, 'fulfillError' => mb_substr($e->getMessage(), 0, 1000)]);
        }

        return $payment->fresh();
    }

    /**
     * The scheduler's round (games:payments): checkings no request finished,
     * approvals the game didn't hear about yet, and links that ran out unpaid.
     */
    public function sweep(): array
    {
        $done = ['checked' => 0, 'fulfilled' => 0, 'expired' => 0];

        $stale = now()->subSeconds((int) config('games.site.check_stale_seconds', 180));
        GamePayment::active()->where('status', GamePayment::CHECKING)
            ->where(fn ($q) => $q->whereNull('aiStatus')->orWhere('aiCheckedAt', '<', $stale))
            ->orderBy('id')->limit(10)->get()
            ->each(function (GamePayment $p) use (&$done) {
                $this->check($p);
                $done['checked']++;
            });

        GamePayment::active()->where('status', GamePayment::APPROVED)
            ->where('fulfillAttempts', '<', (int) config('games.site.fulfill_max_attempts', 30))
            ->orderBy('id')->limit(20)->get()
            ->each(function (GamePayment $p) use (&$done) {
                if ($this->fulfill($p)->status === GamePayment::PAID) {
                    $done['fulfilled']++;
                }
            });

        $done['expired'] = GamePayment::active()->where('status', GamePayment::AWAITING)
            ->whereNotNull('expiresAt')->where('expiresAt', '<', now())
            ->update(['status' => GamePayment::EXPIRED]);

        return $done;
    }

    /** What the website shows for a payment. */
    public function view(GamePayment $payment): array
    {
        $pay = GamesSiteSettings::pay();
        $report = is_array($payment->aiReport) ? $payment->aiReport : [];
        $problems = collect($report['checks'] ?? [])->filter(fn ($c) => ($c['ok'] ?? null) !== true)
            ->map(fn ($c) => ['label' => (string) ($c['label'] ?? ''), 'detail' => (string) ($c['detail'] ?? '')])->values()->all();

        return [
            'token' => $payment->checkoutToken,
            'status' => $payment->status,
            'statusLabel' => GamePayment::STATUSES[$payment->status] ?? $payment->status,
            'game' => ['key' => $payment->gameKey, 'name' => (string) config('games.' . $payment->gameKey . '.name', $payment->gameKey)],
            'player' => $payment->username,
            'item' => ['name' => $payment->itemName, 'category' => $payment->itemCategory],
            'amounts' => [
                'currency' => 'PHP',
                'price' => (float) $payment->pricePhp,
                'fee' => (float) $payment->feePhp,
                'total' => (float) $payment->totalPhp,
                'usd' => round($payment->priceUsdCents / 100, 2),
            ],
            'methods' => [
                'gcash' => [
                    'ready' => GamesSiteSettings::gcashReady(),
                    'number' => GamesSiteSettings::spacedNumber(),
                    'name' => $pay['gcashName'],
                    'qrUrl' => GamesSiteSettings::qrUrl(),
                ],
                'paypal' => ['ready' => false, 'soon' => true],
            ],
            'canUpload' => $payment->takesProof(),
            'uploads' => (int) $payment->proofUploads,
            'maxUploads' => GamePayment::MAX_UPLOADS,
            'refNumber' => $payment->refNumber,
            'check' => $payment->aiStatus && $payment->aiStatus !== 'running' ? [
                'verdict' => $payment->aiStatus,
                'summary' => (string) ($report['summary'] ?? ''),
                'problems' => $problems,
            ] : null,
            'reviewHours' => (int) $pay['reviewHours'],
            'note' => in_array($payment->status, [GamePayment::REJECTED], true) ? $payment->reviewNote : null,
            'expiresAt' => $payment->expiresAt?->toIso8601String(),
            'paidAt' => $payment->fulfilledAt?->toIso8601String(),
            'returnUrl' => rtrim((string) config('games.' . $payment->gameKey . '.api_url'), '/') . '/play/?paid=' . urlencode((string) $payment->orderId),
        ];
    }

    /** The same receipt file sent with another payment (here) before. */
    public static function shaSeenElsewhere(string $sha, int $paymentId): bool
    {
        return GamePayment::where('proofSha', $sha)->where('id', '!=', $paymentId)->exists();
    }

    private function expireIfDue(GamePayment $payment): GamePayment
    {
        if ($payment->status === GamePayment::AWAITING && $payment->isExpired()) {
            $payment->update(['status' => GamePayment::EXPIRED]);

            return $payment->fresh();
        }

        return $payment;
    }
}
