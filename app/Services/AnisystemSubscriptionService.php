<?php

namespace App\Services;

use App\Models\AnisystemSubscription;
use App\Models\AnisystemUser;
use App\Models\AsEmailTemplate;
use App\Models\AsMailSmtpSetting;
use App\Models\EcomOrder;
use App\Models\EcomOrderAuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Admin-side management of AniSystem client subscriptions living in the
 * shared DB (anisystem_users / anisystem_subscriptions), plus the email
 * notifications sent through the AniSystem SMTP group
 * (as_mail_smtp_settings + as_email_templates).
 */
class AnisystemSubscriptionService
{
    public const MAIL_GROUP = 'AniSystem';
    public const SITE_NAME = 'AniSystem';
    public const LOGIN_URL = 'http://anisystem.test/login';

    /* ------------------------------------------------------------------
     |  Admin actions (Clients module)
     * ----------------------------------------------------------------- */

    /**
     * Suspend the client's latest usable subscription.
     *
     * @return AnisystemSubscription the suspended subscription
     * @throws \RuntimeException when there is nothing to suspend
     */
    public function suspend(AnisystemUser $user): AnisystemSubscription
    {
        $subscription = AnisystemSubscription::active()
            ->where('userId', $user->id)
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('id', 'desc')
            ->first();

        if (!$subscription) {
            throw new \RuntimeException('No active or pending subscription found for this client.');
        }

        $subscription->status = 'suspended';
        $subscription->suspendedAt = Carbon::now('Asia/Manila');
        $subscription->save();

        Log::info('AniSystem subscription suspended by admin', [
            'anisystem_user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'admin_id' => Auth::id(),
        ]);

        $this->sendTemplateEmail('subscription_suspended', $user, $subscription);

        return $subscription;
    }

    /**
     * Unsuspend the client's latest suspended subscription. Goes back to
     * 'active' when expiresAt is still in the future, otherwise 'expired'.
     *
     * @return AnisystemSubscription
     * @throws \RuntimeException when there is no suspended subscription
     */
    public function unsuspend(AnisystemUser $user): AnisystemSubscription
    {
        $subscription = AnisystemSubscription::active()
            ->where('userId', $user->id)
            ->where('status', 'suspended')
            ->orderBy('id', 'desc')
            ->first();

        if (!$subscription) {
            throw new \RuntimeException('No suspended subscription found for this client.');
        }

        $stillValid = $subscription->expiresAt
            && Carbon::parse($subscription->expiresAt)->timezone('Asia/Manila')->isFuture();

        $subscription->status = $stillValid ? 'active' : 'expired';
        $subscription->suspendedAt = null;
        $subscription->save();

        Log::info('AniSystem subscription unsuspended by admin', [
            'anisystem_user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'restored_status' => $subscription->status,
            'admin_id' => Auth::id(),
        ]);

        return $subscription;
    }

    /**
     * Cancel the client's latest cancellable subscription. If the linked
     * ecom order is still unverified and not already terminal, the order is
     * cancelled too (mirrors OrdersController@cancelOrder behavior).
     *
     * @return AnisystemSubscription
     * @throws \RuntimeException when there is nothing to cancel
     */
    public function cancel(AnisystemUser $user): AnisystemSubscription
    {
        $subscription = AnisystemSubscription::active()
            ->where('userId', $user->id)
            ->whereIn('status', ['pending', 'active', 'suspended'])
            ->orderBy('id', 'desc')
            ->first();

        if (!$subscription) {
            throw new \RuntimeException('No cancellable subscription found for this client.');
        }

        $subscription->status = 'cancelled';
        $subscription->cancelledAt = Carbon::now('Asia/Manila');
        $subscription->save();

        // Cancel the linked ecom order when payment was never verified.
        if ($subscription->ecomOrderId) {
            try {
                $order = EcomOrder::where('id', $subscription->ecomOrderId)->active()->first();
                if (
                    $order
                    && $order->paymentVerificationStatus !== 'verified'
                    && !in_array($order->orderStatus, ['cancelled', 'refunded'])
                ) {
                    $oldStatus = $order->orderStatus;
                    $order->orderStatus = 'cancelled';
                    $order->save();

                    EcomOrderAuditLog::logAction(
                        $order,
                        'order_cancelled',
                        'orderStatus',
                        $oldStatus,
                        'cancelled',
                        "Order cancelled because its linked AniSystem subscription #{$subscription->id} ({$subscription->planName}) was cancelled by admin (previous status: '{$oldStatus}')"
                    );

                    Log::info('Ecom order cancelled due to AniSystem subscription cancellation', [
                        'order_id' => $order->id,
                        'order_number' => $order->orderNumber,
                        'subscription_id' => $subscription->id,
                        'admin_id' => Auth::id(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to cancel linked ecom order for AniSystem subscription: ' . $e->getMessage(), [
                    'subscription_id' => $subscription->id,
                    'ecom_order_id' => $subscription->ecomOrderId,
                ]);
            }
        }

        Log::info('AniSystem subscription cancelled by admin', [
            'anisystem_user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'admin_id' => Auth::id(),
        ]);

        $this->sendTemplateEmail('subscription_cancelled', $user, $subscription);

        return $subscription;
    }

    /* ------------------------------------------------------------------
     |  Ecom order decision hook (called from OrdersController)
     * ----------------------------------------------------------------- */

    /**
     * React to an admin decision on an ecom order (payment verified /
     * rejected, order cancelled / refunded) by updating the matching
     * AniSystem subscription. Safe to call for any order — no-op unless the
     * order has an active AniSystem item and a linked subscription.
     */
    public function handleOrderDecision(EcomOrder $order): void
    {
        try {
            $hasAniSystemItem = $order->items()
                ->where('deleteStatus', 1)
                ->where('productStore', self::SITE_NAME)
                ->exists();

            if (!$hasAniSystemItem) {
                return;
            }

            $subscription = AnisystemSubscription::active()
                ->where('ecomOrderId', $order->id)
                ->orderBy('id', 'desc')
                ->first();

            if (!$subscription) {
                return;
            }

            $order->refresh();

            if (in_array($order->orderStatus, ['cancelled', 'refunded'])) {
                // Do not touch a subscription that already went live.
                $alreadyLive = $subscription->status === 'active' && $subscription->verifiedAt;
                if (!$alreadyLive && !in_array($subscription->status, ['cancelled', 'rejected'])) {
                    $subscription->status = 'cancelled';
                    $subscription->cancelledAt = Carbon::now('Asia/Manila');
                    $subscription->save();

                    Log::info('AniSystem subscription cancelled (ecom order cancelled/refunded)', [
                        'order_id' => $order->id,
                        'subscription_id' => $subscription->id,
                    ]);
                }
                return;
            }

            if ($order->paymentVerificationStatus === 'verified') {
                // Lock + re-check inside a transaction so this hook and the
                // AniSystem-side sync can never double-activate the same row
                // (which would discard stacked renewal time). Accept 'rejected'
                // too, so an admin who rejects then re-verifies still grants
                // access instead of leaving the customer stuck.
                $activated = DB::transaction(function () use ($subscription) {
                    $fresh = AnisystemSubscription::where('id', $subscription->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$fresh || !in_array($fresh->status, ['pending', 'rejected'])) {
                        return null; // already active/cancelled or handled elsewhere
                    }

                    $now = Carbon::now('Asia/Manila');
                    $startsAt = $now->copy();
                    $isRenewal = false;

                    $current = AnisystemSubscription::active()
                        ->where('userId', $fresh->userId)
                        ->where('id', '!=', $fresh->id)
                        ->where('status', 'active')
                        ->whereNotNull('expiresAt')
                        ->where('expiresAt', '>', $now)
                        ->orderBy('expiresAt', 'desc')
                        ->first();

                    if ($current) {
                        $startsAt = Carbon::parse($current->expiresAt)->timezone('Asia/Manila');
                        $isRenewal = true;
                    }

                    $fresh->status = 'active';
                    $fresh->startsAt = $startsAt;
                    $fresh->expiresAt = $startsAt->copy()->addDays((int) $fresh->durationDays);
                    $fresh->verifiedAt = $now;
                    $fresh->save();

                    return ['subscription' => $fresh, 'isRenewal' => $isRenewal];
                });

                if ($activated) {
                    $subscription = $activated['subscription'];

                    Log::info('AniSystem subscription activated (payment verified)', [
                        'order_id' => $order->id,
                        'subscription_id' => $subscription->id,
                        'starts_at' => $subscription->startsAt,
                        'expires_at' => $subscription->expiresAt,
                        'stacked_renewal' => $activated['isRenewal'],
                    ]);

                    $user = AnisystemUser::active()->find($subscription->userId);
                    if ($user) {
                        $this->sendTemplateEmail(
                            $activated['isRenewal'] ? 'subscription_renewed' : 'payment_approved',
                            $user,
                            $subscription
                        );
                    }
                }
                return;
            }

            if ($order->paymentVerificationStatus === 'rejected') {
                if (in_array($subscription->status, ['rejected', 'cancelled'])) {
                    return;
                }
                if ($subscription->status === 'active' && $subscription->verifiedAt) {
                    return; // never downgrade a live subscription on a stray reject
                }

                $subscription->status = 'rejected';
                $subscription->save();

                Log::info('AniSystem subscription rejected (payment rejected)', [
                    'order_id' => $order->id,
                    'subscription_id' => $subscription->id,
                ]);

                $user = AnisystemUser::active()->find($subscription->userId);
                if ($user) {
                    $this->sendTemplateEmail('payment_rejected', $user, $subscription);
                }
            }
        } catch (\Throwable $e) {
            Log::error('AniSystem handleOrderDecision failed: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
            ]);
        }
    }

    /* ------------------------------------------------------------------
     |  Mail helpers (AniSystem SMTP group + as_email_templates)
     * ----------------------------------------------------------------- */

    /**
     * Send a templated email to an AniSystem client using the AniSystem
     * SMTP group. Failures are logged, never thrown.
     */
    public function sendTemplateEmail(string $templateKey, AnisystemUser $user, ?AnisystemSubscription $subscription = null, array $extraTags = []): bool
    {
        try {
            $tags = array_merge([
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'email' => $user->email,
                'siteName' => self::SITE_NAME,
                'loginUrl' => self::LOGIN_URL,
                'planName' => $subscription->planName ?? '',
                'price' => $subscription ? number_format((float) $subscription->price, 2) : '',
                'orderNumber' => $subscription->orderNumber ?? '',
                'expiresAt' => ($subscription && $subscription->expiresAt)
                    ? Carbon::parse($subscription->expiresAt)->timezone('Asia/Manila')->format('M j, Y')
                    : '',
            ], $extraTags);

            $template = AsEmailTemplate::active()
                ->forGroup(self::MAIL_GROUP)
                ->where('templateKey', $templateKey)
                ->where('isActive', 1)
                ->first();

            if (!$template) {
                Log::warning("AniSystem email template '{$templateKey}' not found or inactive — email skipped.", [
                    'to' => $user->email,
                ]);
                return false;
            }

            $subject = AsEmailTemplate::renderTags($template->subject, $tags);
            $body = AsEmailTemplate::renderTags($template->bodyHtml, $tags);

            return $this->sendViaGroupSmtp(self::MAIL_GROUP, $user->email, $subject, $body, $user->fullName);
        } catch (\Throwable $e) {
            Log::error("AniSystem sendTemplateEmail('{$templateKey}') failed: " . $e->getMessage(), [
                'anisystem_user_id' => $user->id,
            ]);
            return false;
        }
    }

    /**
     * Low-level PHPMailer send using a mail group's SMTP settings row.
     * (as_mail_smtp_settings stores the password in plain text.)
     */
    public function sendViaGroupSmtp(string $groupKey, string $toEmail, string $subject, string $body, ?string $toName = null): bool
    {
        $smtp = AsMailSmtpSetting::active()
            ->forGroup($groupKey)
            ->where('isActive', 1)
            ->first();

        if (!$smtp || !$smtp->isConfigured()) {
            Log::warning("SMTP not configured for mail group '{$groupKey}' — email skipped.", [
                'to' => $toEmail,
                'subject' => $subject,
            ]);
            return false;
        }

        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $smtp->smtpHost;
            $mail->Port = $smtp->smtpPort;
            $mail->CharSet = 'UTF-8';

            if ($smtp->smtpUsername) {
                $mail->SMTPAuth = true;
                $mail->Username = $smtp->smtpUsername;
                $mail->Password = $smtp->smtpPassword; // plain text column
            }

            if ($smtp->smtpEncryption !== 'none') {
                $mail->SMTPSecure = $smtp->smtpEncryption === 'ssl'
                    ? PHPMailer::ENCRYPTION_SMTPS
                    : PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($smtp->smtpFromEmail, $smtp->smtpFromName ?: $groupKey);
            $mail->addAddress($toEmail, $toName ?: '');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();

            Log::info('AniSystem group email sent', [
                'group' => $groupKey,
                'to' => $toEmail,
                'subject' => $subject,
            ]);

            return true;
        } catch (PHPMailerException $e) {
            Log::error("AniSystem group email failed ({$groupKey}): " . $e->getMessage(), ['to' => $toEmail]);
            return false;
        } catch (\Throwable $e) {
            Log::error("AniSystem group email exception ({$groupKey}): " . $e->getMessage(), ['to' => $toEmail]);
            return false;
        }
    }
}
