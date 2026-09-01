<?php

namespace App\Support;

/**
 * How the client's shed counts, said the same way on both sides of the glass.
 *
 * The twin of anee.io's `App\Models\AsInventoryItem` constants. The two apps
 * share one database, so a unit stored by a farmer has to mean the same thing
 * when a technician opens the same row here — which means this list and that
 * one must be edited together. It is a Support class rather than a model
 * because this side reads `as_inventory_items` through the query builder.
 *
 * ONE unit, and the count is in that unit. A farm that buys Urea in 50 kg bags
 * counts bags: "12 bags (50 kg)". It used to be a base unit plus an optional
 * pack size and pack name that were only a way of saying the base unit — three
 * fields for one fact, and a shelf that read "12 bags · 600 kg" to somebody who
 * asked how many bags are left. The pack columns are still in the table and are
 * no longer written or read.
 */
class InventoryUnits
{
    /**
     * Every unit stock can be counted in.
     *
     * `one` and `many` are the word; `of` is what one of them holds, said
     * rather than converted — nothing is ever divided by it. The size lives in
     * the name so that a bare number is never ambiguous on a shelf or in a log.
     */
    public const UNITS = [
        'kg' => ['one' => 'kg', 'many' => 'kg'],
        'g' => ['one' => 'g', 'many' => 'g'],
        'L' => ['one' => 'L', 'many' => 'L'],
        'ml' => ['one' => 'ml', 'many' => 'ml'],
        'piece' => ['one' => 'piece', 'many' => 'pieces'],
        'bag50' => ['one' => 'bag', 'many' => 'bags', 'of' => '50 kg'],
        'bag40' => ['one' => 'bag', 'many' => 'bags', 'of' => '40 kg'],
        'bag25' => ['one' => 'bag', 'many' => 'bags', 'of' => '25 kg'],
        'bag20' => ['one' => 'bag', 'many' => 'bags', 'of' => '20 kg'],
        'sack' => ['one' => 'sack', 'many' => 'sacks'],
        'bottle1' => ['one' => 'bottle', 'many' => 'bottles', 'of' => '1 L'],
        'bottle250' => ['one' => 'bottle', 'many' => 'bottles', 'of' => '250 ml'],
        'jug5' => ['one' => 'jug', 'many' => 'jugs', 'of' => '5 L'],
        'drum200' => ['one' => 'drum', 'many' => 'drums', 'of' => '200 L'],
        'sachet' => ['one' => 'sachet', 'many' => 'sachets'],
        'box' => ['one' => 'box', 'many' => 'boxes'],
        'roll' => ['one' => 'roll', 'many' => 'rolls'],
    ];

    /** The kinds, and the units each one is actually bought in. */
    public const KINDS = [
        'granular' => ['label' => 'Granular fertiliser', 'icon' => '🧂',
            'units' => ['bag50', 'bag25', 'kg', 'g', 'sack']],
        'foliar' => ['label' => 'Foliar / liquid feed', 'icon' => '🧪',
            'units' => ['bottle1', 'L', 'ml', 'jug5', 'sachet']],
        'pesticide' => ['label' => 'Pesticide', 'icon' => '🐛',
            'units' => ['bottle250', 'bottle1', 'L', 'ml', 'sachet', 'kg', 'g']],
        'herbicide' => ['label' => 'Herbicide', 'icon' => '🌿',
            'units' => ['bottle1', 'L', 'ml', 'sachet', 'kg', 'g']],
        'fungicide' => ['label' => 'Fungicide', 'icon' => '🍄',
            'units' => ['sachet', 'bottle250', 'kg', 'g', 'L', 'ml']],
        'molluscicide' => ['label' => 'Molluscicide', 'icon' => '🐌',
            'units' => ['bag25', 'kg', 'g', 'sachet']],
        'seed' => ['label' => 'Seed', 'icon' => '🌱',
            'units' => ['bag40', 'bag20', 'kg', 'g', 'sack', 'piece']],
        'fuel' => ['label' => 'Fuel', 'icon' => '⛽',
            'units' => ['L', 'drum200']],
        'tool' => ['label' => 'Tool / supply', 'icon' => '🧰',
            'units' => ['piece', 'box', 'roll', 'sack']],
        'other' => ['label' => 'Other', 'icon' => '📦',
            'units' => ['piece', 'kg', 'g', 'L', 'ml', 'box', 'sack']],
    ];

    /** The units this kind is offered, defaulting to the whole list. */
    public static function unitsFor(?string $kind): array
    {
        return self::KINDS[$kind]['units'] ?? array_keys(self::UNITS);
    }

    /**
     * A unit as a name — "bags (50 kg)", "kg".
     *
     * A unit from before this list existed is said as it stands rather than
     * swapped for a guess: the shed is the client's, and a technician's screen
     * inventing a different word for it would be worse than an odd one.
     */
    public static function unitSays(?string $key, bool $singular = true): string
    {
        $u = self::UNITS[$key] ?? null;
        if (! $u) {
            return (string) $key;
        }
        $word = $singular ? $u['one'] : $u['many'];

        return isset($u['of']) ? $word . ' (' . $u['of'] . ')' : $word;
    }

    /** A quantity said the way the item is counted: "12 bags (50 kg)". */
    public static function say(float $qty, ?string $unit): string
    {
        return self::trim($qty) . ' ' . self::unitSays($unit, abs($qty) == 1.0);
    }

    /** A number without the noise: 12 rather than 12.000. */
    public static function trim(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ','), '0'), '.') ?: '0';
    }
}
