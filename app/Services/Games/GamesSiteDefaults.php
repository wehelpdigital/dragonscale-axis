<?php

namespace App\Services\Games;

/**
 * The first game on the studio website, as the migration that made
 * games_site_games adds it. Edited from then on in Games > Website. The
 * pictures are the website's own (/images/games/salpakan/...); the website
 * carries the same words as its fallback when DS AXIS can't be reached.
 */
class GamesSiteDefaults
{
    public static function salpakan(): array
    {
        return [
            'gameKey' => 'salpakan',
            'slug' => 'salpakan-tactics',
            'name' => 'Salpakan Tactics!',
            'tagline' => 'Hidden ranks. Bold bluffs. One flag to capture.',
            'genre' => 'Strategy · 2 players',
            'status' => 'live',
            'playUrl' => rtrim((string) config('games.salpakan.api_url'), '/') . '/play/',
            'accent' => '#ff5a8a',
            'summary' => 'The Filipino battlefield classic, rebuilt as a bright 3D board game. Hide your ranks, read your rival, and send your Spy at their Five-Star General. Play the AI in your browser, or anyone online.',
            'description' => "Two armies of 21 pieces face each other across a board, every rank hidden from the other side. When two pieces meet, a referee compares them in secret: the higher rank stays, the lower one leaves, and equals knock each other out. Only the result is ever shown.\n\n"
                . "Win by capturing the enemy Flag, or by marching your own Flag across to the far row. Privates are many and brave, Spies take out any officer, and Generals rule the board -- until a Spy finds them.\n\n"
                . "Salpakan Tactics! keeps the game you grew up with and adds a little mischief: optional powerups (fog, decoys, intel, traps), ranked seasons, guilds, a world of hometown leaderboards, and a shop of boards, arenas and pieces from cultures all over the world. Every match is free to play.",
            'platforms' => [
                ['key' => 'web', 'status' => 'live', 'url' => ''],
                ['key' => 'android', 'status' => 'soon', 'url' => ''],
                ['key' => 'steam', 'status' => 'soon', 'url' => ''],
            ],
            'features' => [
                ['title' => 'Hidden ranks, real bluffs', 'text' => 'Only the referee sees both armies. Every clash is a reveal, and every quiet piece might be the Spy.'],
                ['title' => 'Play anyone, anywhere', 'text' => 'Quick matches, ranked seasons, private rooms and friend invites: the same account on the web, phone and PC.'],
                ['title' => 'Powerups for the bold', 'text' => '222 cards of fog, decoys, traps and intel. Or switch them off for the classic game.'],
                ['title' => 'Learn in five minutes', 'text' => 'Boot Camp teaches the ranks in eight short drills on the real board, then a first game against the Rookie AI.'],
                ['title' => 'Make the board yours', 'text' => 'Arenas, boards, pieces, toppers and taunts from cultures all over the world, with Battle Clips when you win a clash.'],
                ['title' => 'Climb with your town', 'text' => 'Leaderboards for your city, province and country, guilds to join, and titles to earn.'],
            ],
            'screenshots' => [
                ['url' => '/images/games/salpakan/01-match.webp', 'caption' => 'Every rank hidden: only the referee sees both armies.'],
                ['url' => '/images/games/salpakan/02-clash.webp', 'caption' => 'A clash, and a Battle Clip when you win it.'],
                ['url' => '/images/games/salpakan/03-powerups.webp', 'caption' => 'Powerups: fog, decoys, intel and traps.'],
                ['url' => '/images/games/salpakan/04-cultures.webp', 'caption' => 'Arenas from cultures all over the world.'],
                ['url' => '/images/games/salpakan/05-my-board.webp', 'caption' => 'Make the board yours.'],
                ['url' => '/images/games/salpakan/06-boot-camp.webp', 'caption' => 'Boot Camp: learn the ranks in five minutes.'],
                ['url' => '/images/games/salpakan/07-home.webp', 'caption' => 'Ranked, quick matches, rooms and friends.'],
                ['url' => '/images/games/salpakan/08-phone.webp', 'caption' => 'Made for phones, tablets and PCs alike.'],
            ],
            'sortOrder' => 1,
        ];
    }
}
