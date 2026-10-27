<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * One player of one game (games_players), as the game server last described
 * them. Kept in step by games:sync; nothing here is edited by hand except the
 * Acumbamail columns, which the sync writes as it adds people to the list.
 *
 * Shared by every admin (no usersId): scope by game with forGame().
 */
class GamePlayer extends BaseModel
{
    protected $table = 'games_players';

    public const SIGN_UP_METHODS = [
        'password' => 'Email & password',
        'google' => 'Google',
        'steam' => 'Steam',
    ];

    public const PLATFORMS = [
        'web' => 'Web',
        'android' => 'Android',
        'ios' => 'iOS',
        'windows' => 'Windows',
        'linux' => 'Linux',
        'mac' => 'Mac',
    ];

    public const ACUMBAMAIL_STATUSES = [
        'pending' => 'Not added yet',
        'subscribed' => 'Subscribed',
        'skipped' => 'Not added',
        'failed' => 'Failed',
        'unsubscribed' => 'Unsubscribed',
    ];

    /** Steam accounts without a real address get one ending in this. */
    public const STEAM_PLACEHOLDER_DOMAIN = '@steam.invalid';

    protected $fillable = [
        'gameKey',
        'remotePlayerId',
        'username',
        'email',
        'signUpMethod',
        'registeredAt',
        'remoteUpdatedAt',
        'lastActiveAt',
        'lastPlatform',
        'countryCode',
        'country',
        'region',
        'city',
        'gamesPlayed',
        'wins',
        'losses',
        'draws',
        'elo',
        'rankTier',
        'rankName',
        'xp',
        'credits',
        'adFree',
        'isBanned',
        'marketingOptOut',
        'purchasesCount',
        'totalSpentCents',
        'acumbamailStatus',
        'acumbamailEmail',
        'acumbamailSubscriberId',
        'acumbamailSubscribedAt',
        'acumbamailUnsubscribedAt',
        'acumbamailTriedAt',
        'acumbamailAttempts',
        'acumbamailMessage',
        'delete_status',
    ];

    protected $casts = [
        'remotePlayerId' => 'integer',
        'registeredAt' => 'datetime',
        'remoteUpdatedAt' => 'datetime',
        'lastActiveAt' => 'datetime',
        'gamesPlayed' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'draws' => 'integer',
        'elo' => 'integer',
        'rankTier' => 'integer',
        'xp' => 'integer',
        'credits' => 'integer',
        'adFree' => 'boolean',
        'isBanned' => 'boolean',
        'marketingOptOut' => 'boolean',
        'purchasesCount' => 'integer',
        'totalSpentCents' => 'integer',
        'acumbamailSubscribedAt' => 'datetime',
        'acumbamailUnsubscribedAt' => 'datetime',
        'acumbamailTriedAt' => 'datetime',
        'acumbamailAttempts' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForGame($query, string $gameKey)
    {
        return $query->where('gameKey', $gameKey);
    }

    /** Players who have not played for at least $days days (never played counts from sign-up). */
    public function scopeInactiveFor($query, int $days)
    {
        $before = now()->subDays($days)->format('Y-m-d H:i:s');

        return $query->where(function ($q) use ($before) {
            $q->where('lastActiveAt', '<=', $before)
                ->orWhere(function ($w) use ($before) {
                    $w->whereNull('lastActiveAt')->where('registeredAt', '<=', $before);
                });
        });
    }

    /** Players an email could reach: a real address, and not opted out of marketing. */
    public function scopeReachable($query)
    {
        return $query->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('email', 'not like', '%' . self::STEAM_PLACEHOLDER_DOMAIN)
            ->where('marketingOptOut', false);
    }

    public function activity()
    {
        return GamePlayerActivity::forGame($this->gameKey)->where('remotePlayerId', $this->remotePlayerId);
    }

    public function orders()
    {
        return GameOrder::forGame($this->gameKey)->where('remotePlayerId', $this->remotePlayerId);
    }

    /** A real address: present, and not the placeholder Steam accounts get. */
    public static function isRealEmail(?string $email): bool
    {
        $email = trim((string) $email);

        return $email !== ''
            && ! str_ends_with(strtolower($email), self::STEAM_PLACEHOLDER_DOMAIN)
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function hasRealEmail(): bool
    {
        return self::isRealEmail($this->email);
    }

    /** When they last played, or signed up if they never have. */
    public function lastSeen(): ?Carbon
    {
        return $this->lastActiveAt ?? $this->registeredAt;
    }

    public function daysInactive(): ?int
    {
        $seen = $this->lastSeen();

        return $seen ? (int) floor($seen->diffInDays(now(), true)) : null;
    }

    public function winRate(): ?float
    {
        return $this->gamesPlayed > 0 ? round($this->wins / $this->gamesPlayed * 100, 1) : null;
    }

    public function location(): string
    {
        return implode(', ', array_filter([$this->city, $this->region, $this->country ?: $this->countryCode]));
    }

    public function signUpLabel(): string
    {
        return self::SIGN_UP_METHODS[$this->signUpMethod] ?? ucfirst((string) $this->signUpMethod);
    }

    public function platformLabel(): string
    {
        return self::PLATFORMS[$this->lastPlatform] ?? ucfirst((string) $this->lastPlatform);
    }

    public function acumbamailLabel(): string
    {
        return self::ACUMBAMAIL_STATUSES[$this->acumbamailStatus] ?? ucfirst((string) $this->acumbamailStatus);
    }
}
