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
        // 'long' is picker detail only — twin of anee's AsInventoryItem::UNITS.
        'kg' => ['one' => 'kg', 'many' => 'kg', 'long' => 'kilograms', 'dim' => 'mass', 'factor' => 1],
        'g' => ['one' => 'g', 'many' => 'g', 'long' => 'grams', 'dim' => 'mass', 'factor' => 0.001],
        'L' => ['one' => 'L', 'many' => 'L', 'long' => 'liters', 'dim' => 'volume', 'factor' => 1],
        'ml' => ['one' => 'ml', 'many' => 'ml', 'long' => 'milliliters', 'dim' => 'volume', 'factor' => 0.001],
        'piece' => ['one' => 'piece', 'many' => 'pieces', 'dim' => 'piece', 'factor' => 1],
        'bag50' => ['one' => 'bag', 'many' => 'bags', 'of' => '50 kg', 'dim' => 'mass', 'factor' => 50],
        'bag40' => ['one' => 'bag', 'many' => 'bags', 'of' => '40 kg', 'dim' => 'mass', 'factor' => 40],
        'bag25' => ['one' => 'bag', 'many' => 'bags', 'of' => '25 kg', 'dim' => 'mass', 'factor' => 25],
        'bag20' => ['one' => 'bag', 'many' => 'bags', 'of' => '20 kg', 'dim' => 'mass', 'factor' => 20],
        'sack' => ['one' => 'sack', 'many' => 'sacks', 'dim' => 'sack', 'factor' => 1],
        'bottle1' => ['one' => 'bottle', 'many' => 'bottles', 'of' => '1 L', 'dim' => 'volume', 'factor' => 1],
        'bottle250' => ['one' => 'bottle', 'many' => 'bottles', 'of' => '250 ml', 'dim' => 'volume', 'factor' => 0.25],
        'jug5' => ['one' => 'jug', 'many' => 'jugs', 'of' => '5 L', 'dim' => 'volume', 'factor' => 5],
        'drum200' => ['one' => 'drum', 'many' => 'drums', 'of' => '200 L', 'dim' => 'volume', 'factor' => 200],
        'sachet' => ['one' => 'sachet', 'many' => 'sachets', 'dim' => 'sachet', 'factor' => 1],
        'box' => ['one' => 'box', 'many' => 'boxes', 'dim' => 'box', 'factor' => 1],
        'roll' => ['one' => 'roll', 'many' => 'rolls', 'dim' => 'roll', 'factor' => 1],
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

    /** A quantity in one unit said in another, or null across dimensions.
        The twin of anee.io's AsInventoryItem::convert — edit together. */
    public static function convert(float $qty, string $from, string $to): ?float
    {
        if ($from === $to) {
            return $qty;
        }
        $a = self::UNITS[$from] ?? null;
        $b = self::UNITS[$to] ?? null;
        if (! $a || ! $b || $a['dim'] !== $b['dim']) {
            return null;
        }

        return $qty * $a['factor'] / $b['factor'];
    }

    /** A number without the noise: 12 rather than 12.000. */
    public static function trim(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ','), '0'), '.') ?: '0';
    }
}
