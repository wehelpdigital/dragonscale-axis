<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Who the console is, when it speaks inside the farmer app.
 *
 * The two apps keep separate people. An admin signing in here does not exist
 * in `anisystem_users`, and every row a farmer-app conversation is made of
 * insists on naming one — a Collab Room message, a Collab Room question to
 * Anee. So a console answering a client had nobody to be.
 *
 * The choices were to borrow the client's own id and mark the text, or to
 * give the console a person of its own. Borrowing puts words in the client's
 * name in their own app, where a mark in the body is the only thing standing
 * between them and believing they wrote it. A person of its own costs one
 * row, and then the client's app shows a name and an avatar that are true
 * without anything having to be explained.
 *
 * The account cannot be signed into. Its password is random and is never
 * stored anywhere it could be recovered from, and nothing here writes a
 * session for it — it exists to be named as the author of a message.
 */
class AniSensoTechnician
{
    /**
     * The address that identifies the row.
     *
     * A constant, not a setting: it is how the row is found again on every
     * deploy and in every environment, so changing it would silently make a
     * second technician rather than rename the first.
     */
    public const EMAIL = 'technician@anee.io';

    private const FIRST = 'AniSystem';

    private const LAST = 'Technician';

    /**
     * The technician's id in the farmer app, making the row if it is missing.
     *
     * Made on demand rather than in a migration because it is needed the
     * first time an admin says something and not before, and because both
     * apps share one database — a migration on this side creating people on
     * the other side is a surprise waiting to happen.
     */
    public static function id(): int
    {
        $found = DB::table('anisystem_users')->where('email', self::EMAIL)->value('id');
        if ($found) {
            return (int) $found;
        }

        return (int) DB::table('anisystem_users')->insertGetId([
            'firstName' => self::FIRST,
            'lastName' => self::LAST,
            // No one signs in as this. A long random secret nobody keeps is
            // the honest way to fill a column that cannot be null.
            'password' => bcrypt(Str::random(48)),
            'email' => self::EMAIL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** What to call them on a screen. */
    public static function name(): string
    {
        return self::FIRST . ' ' . self::LAST;
    }
}
