<?php

namespace App\Support;

/**
 * Every crop a lot can be set to.
 *
 * A copy of the farmer app's App\Support\CropCatalog — the same eighty-five
 * crops in the same nine groups, because a lot set here and a lot set there
 * are the same row, and a name that exists on one side and not the other is
 * a lot that reads as "not set" in the app that did not get the list.
 *
 * Not to be confused with CropStages, which is the few crops that have a
 * stage TABLE. That answers "what stage is this lot at"; this answers "what
 * is growing on it", and far more crops can answer the second than the first.
 */
class CropCatalog
{
    /** Counted in days from the lot's day zero. */
    public const ANNUAL = 'annual';

    /** Counted in months of age; the lot records when it was planted. */
    public const PERENNIAL = 'perennial';

    /**
     * The shapes a season comes in, as fractions of the way to harvest.
     *
     * [fraction-of-maturity, label, what is happening, what it usually needs]
     *
     * Fractions rather than days, so one pattern serves a forty-day pechay
     * and a hundred-day cabbage without either being told the other's dates.
     */
    public const PATTERNS = [
        'leafy' => [
            [0.00, 'Seedling', 'Roots take hold and the first true leaves open.', 'Light, frequent water. Shade at midday if the sun is harsh.'],
            [0.18, 'Leaf growth', 'The frame builds; this is where the yield is made.', 'Nitrogen. Thin to spacing and keep the bed weeded.'],
            [0.55, 'Heading & filling', 'The part you sell tightens and fills out.', 'Even moisture. Ease off nitrogen. Watch for worms.'],
            [0.85, 'Harvest', 'Cut as it is ready; do not let it bolt.', 'Harvest in the cool of the morning and keep it shaded.'],
        ],
        'fruiting' => [
            [0.00, 'Seedling', 'Roots and the first true leaves.', 'Steady moisture. Harden off before transplanting.'],
            [0.18, 'Vegetative', 'Leaves and branches build the frame that carries the fruit.', 'Nitrogen. Stake or trellis what needs it.'],
            [0.42, 'Flowering', 'Flowers set the crop; this week decides the yield.', 'Even water — swings drop flowers. Do not push nitrogen now.'],
            [0.58, 'Fruit set & filling', 'Fruit forms and swells.', 'Potassium and calcium. Watch for fruit borer and mites.'],
            [0.80, 'Harvest', 'Pick as it comes, and keep picking.', 'Frequent picking keeps the plant setting more.'],
        ],
        'cucurbit' => [
            [0.00, 'Seedling', 'Cotyledons and the first true leaves.', 'Warm, moist soil. Guard against cutworm at the base.'],
            [0.15, 'Vine growth', 'Runners and tendrils reach out.', 'Nitrogen. Get the trellis up before the vines ask for it.'],
            [0.38, 'Flowering', 'Male flowers first, then female ones behind them.', 'Do not spray at flowering — the bees are doing the work.'],
            [0.55, 'Fruit set & filling', 'Fruit sets on the female flowers and swells fast.', 'Heavy water and potassium. Rest fruit off wet soil.'],
            [0.80, 'Harvest', 'Cut while still tender; a missed fruit stops the vine.', 'Pick every two or three days.'],
        ],
        'legume' => [
            [0.00, 'Emergence', 'Seedlings break ground.', 'Do not overwater — legumes rot in a soaked seedbed.'],
            [0.18, 'Vegetative', 'Leaves build and the nodules start fixing nitrogen.', 'Go easy on nitrogen; it has its own. Phosphorus helps.'],
            [0.42, 'Flowering', 'Flowers open and set the pods.', 'Even moisture. This is the week that decides the pods.'],
            [0.58, 'Pod filling', 'Pods fill and the seed hardens.', 'Water matters most now. Watch for pod borer.'],
            [0.82, 'Maturity & harvest', 'Pods dry down or reach picking size.', 'For dry seed, harvest when most pods have turned.'],
        ],
        'root' => [
            [0.00, 'Establishment', 'Cuttings or seed pieces take root.', 'Keep the soil moist until they hold.'],
            [0.15, 'Vegetative', 'Tops build the leaves that will feed the root.', 'Nitrogen early. Weed hard — roots hate competition.'],
            [0.45, 'Root initiation', 'Storage roots begin to form under the canopy.', 'Hill up. Switch from nitrogen toward potassium.'],
            [0.65, 'Bulking', 'The root swells; this is the whole yield.', 'Steady water and potassium. Do not disturb the hills.'],
            [0.88, 'Harvest', 'Roots reach size and the tops begin to yellow.', 'Lift carefully — a bruised root will not keep.'],
        ],
        'bulb' => [
            [0.00, 'Establishment', 'Sets or seedlings root in.', 'Firm, moist soil. Shallow planting for bulbs.'],
            [0.20, 'Leaf growth', 'Every leaf becomes a ring in the bulb.', 'Nitrogen now — leaves made late do not become bulb.'],
            [0.50, 'Bulbing', 'The base swells and the plant stops making leaves.', 'Stop nitrogen. Potassium and steady, lighter water.'],
            [0.80, 'Maturity & curing', 'Tops soften and fall over.', 'Withhold water. Lift and cure in shade before storing.'],
        ],
        'cereal' => [
            [0.00, 'Emergence', 'Seedlings break ground and set their first roots.', 'Even moisture. Guard the seedbed from birds and rats.'],
            [0.15, 'Vegetative', 'Tillers or leaves build the frame.', 'The main nitrogen goes on here.'],
            [0.42, 'Reproductive', 'The head or ear forms inside the stem.', 'Do not let it dry now. Second nitrogen if the crop looks pale.'],
            [0.60, 'Flowering', 'Pollination — the week that sets the grain.', 'Water without fail. Heat and drought here cost the most.'],
            [0.72, 'Grain filling', 'Grain fills and hardens.', 'Keep water on until the dough stage. Watch for rats and birds.'],
            [0.90, 'Ripening & harvest', 'Grain hardens and the crop dries down.', 'Drain the field. Harvest when most grains have turned.'],
        ],
        /* Trees, in months of age. The numbers here are months, not
         * fractions — a tree's life is not a run-up to one harvest, so
         * there is no maturity to take fractions of. */
        'tree' => [
            [0, 'Establishment', 'The young tree puts down roots and holds.', 'Water through the dry months. Keep a clean weed-free ring.'],
            [12, 'Juvenile growth', 'Frame and canopy build. No crop yet.', 'Feed lightly but often. Train the frame now — it is permanent.'],
            [36, 'First bearing', 'The first flowers and a light crop.', 'Thin the first fruits; a young tree over-bearing sets itself back.'],
            [72, 'Mature bearing', 'Full crop each season.', 'Feed after harvest, prune for light, and watch the flowering flush.'],
            [240, 'Old and declining', 'Yield eases off; wood is heavy and shaded.', 'Rejuvenation pruning, or plan the replacement.'],
        ],
    ];

    /**
     * Every crop, grouped for the picker.
     *
     * key => [label, icon, group, kind, counter, maturity|bearingAt, pattern|stages]
     *
     * `maturity` is days from the counter's own day zero to harvest, and it
     * is the number a lot may override. `bearingAt` is the age in months a
     * tree usually first crops at, which is the equivalent fact for one.
     */
    public const CROPS = [
        /* ---------------- Cereals & grains ---------------- */
        'rice' => [
            'label' => 'Rice — transplanted (Palay)', 'icon' => '🌾', 'group' => 'Cereals & grains',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 110,
        ],
        'rice_upland' => [
            'label' => 'Rice — upland (Palay sa tuyo)', 'icon' => '🌾', 'group' => 'Cereals & grains',
            'kind' => self::ANNUAL, 'counter' => 'DAS', 'maturity' => 115, 'pattern' => 'cereal',
        ],
        'corn_yellow' => [
            'label' => 'Corn — yellow / field (Mais)', 'icon' => '🌽', 'group' => 'Cereals & grains',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 110,
        ],
        'corn_sweet' => [
            'label' => 'Corn — sweet (Mais na matamis)', 'icon' => '🌽', 'group' => 'Cereals & grains',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 75,
        ],
        'corn_glutinous' => [
            'label' => 'Corn — white / glutinous (Mais na malagkit)', 'icon' => '🌽', 'group' => 'Cereals & grains',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 95, 'pattern' => 'cereal',
        ],
        'sorghum' => [
            'label' => 'Sorghum (Batad)', 'icon' => '🌾', 'group' => 'Cereals & grains',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 110, 'pattern' => 'cereal',
        ],

        /* ---------------- Legumes ---------------- */
        'mungbean' => [
            'label' => 'Mungbean (Monggo)', 'icon' => '🫘', 'group' => 'Legumes',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 65, 'pattern' => 'legume',
        ],
        'peanut' => [
            'label' => 'Peanut (Mani)', 'icon' => '🥜', 'group' => 'Legumes',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 100, 'pattern' => 'legume',
        ],
        'soybean' => [
            'label' => 'Soybean (Utaw)', 'icon' => '🫘', 'group' => 'Legumes',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 100, 'pattern' => 'legume',
        ],
        'stringbean' => [
            'label' => 'String bean (Sitaw)', 'icon' => '🫛', 'group' => 'Legumes',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 55, 'pattern' => 'legume',
        ],
        'cowpea' => [
            'label' => 'Cowpea (Paayap)', 'icon' => '🫛', 'group' => 'Legumes',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 70, 'pattern' => 'legume',
        ],
        'wingedbean' => [
            'label' => 'Winged bean (Sigarilyas)', 'icon' => '🫛', 'group' => 'Legumes',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 70, 'pattern' => 'legume',
        ],
        'limabean' => [
            'label' => 'Lima bean (Patani)', 'icon' => '🫘', 'group' => 'Legumes',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 85, 'pattern' => 'legume',
        ],
        'pigeonpea' => [
            'label' => 'Pigeon pea (Kadyos)', 'icon' => '🫘', 'group' => 'Legumes',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 150, 'pattern' => 'legume',
        ],

        /* ---------------- Root crops ---------------- */
        'sweetpotato' => [
            'label' => 'Sweet potato (Kamote)', 'icon' => '🍠', 'group' => 'Root crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 105, 'pattern' => 'root',
            'counters' => ['DAP'],
        ],
        'cassava' => [
            'label' => 'Cassava (Kamoteng kahoy)', 'icon' => '🥔', 'group' => 'Root crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 270, 'pattern' => 'root',
            'counters' => ['DAP'],
        ],
        'taro' => [
            'label' => 'Taro (Gabi)', 'icon' => '🥔', 'group' => 'Root crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 180, 'pattern' => 'root',
            'counters' => ['DAP'],
        ],
        'ubi' => [
            'label' => 'Purple yam (Ubi)', 'icon' => '🍠', 'group' => 'Root crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 270, 'pattern' => 'root',
            'counters' => ['DAP'],
        ],
        'potato' => [
            'label' => 'Potato (Patatas)', 'icon' => '🥔', 'group' => 'Root crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 100, 'pattern' => 'root',
            'counters' => ['DAP'],
        ],
        'carrot' => [
            'label' => 'Carrot (Karot)', 'icon' => '🥕', 'group' => 'Root crops',
            'kind' => self::ANNUAL, 'counter' => 'DAS', 'maturity' => 100, 'pattern' => 'root',
        ],
        'radish' => [
            'label' => 'Radish (Labanos)', 'icon' => '🥬', 'group' => 'Root crops',
            'kind' => self::ANNUAL, 'counter' => 'DAS', 'maturity' => 45, 'pattern' => 'root',
        ],
        'ginger' => [
            'label' => 'Ginger (Luya)', 'icon' => '🫚', 'group' => 'Root crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 270, 'pattern' => 'root',
            'counters' => ['DAP'],
        ],
        'turmeric' => [
            'label' => 'Turmeric (Luyang dilaw)', 'icon' => '🫚', 'group' => 'Root crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 255, 'pattern' => 'root',
            'counters' => ['DAP'],
        ],

        /* ---------------- Leafy & brassicas ---------------- */
        'pechay' => [
            'label' => 'Pechay (Petsay)', 'icon' => '🥬', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 35, 'pattern' => 'leafy',
        ],
        'cabbage' => [
            'label' => 'Cabbage (Repolyo)', 'icon' => '🥬', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 85, 'pattern' => 'leafy',
        ],
        'lettuce' => [
            'label' => 'Lettuce (Litsugas)', 'icon' => '🥬', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 55, 'pattern' => 'leafy',
        ],
        'kangkong' => [
            'label' => 'Water spinach (Kangkong)', 'icon' => '🥬', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAS', 'maturity' => 30, 'pattern' => 'leafy',
        ],
        'mustard' => [
            'label' => 'Mustard (Mustasa)', 'icon' => '🥬', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAS', 'maturity' => 38, 'pattern' => 'leafy',
        ],
        'broccoli' => [
            'label' => 'Broccoli (Brokoli)', 'icon' => '🥦', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 80, 'pattern' => 'leafy',
        ],
        'cauliflower' => [
            'label' => 'Cauliflower (Koliplor)', 'icon' => '🥦', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 90, 'pattern' => 'leafy',
        ],
        'alugbati' => [
            'label' => 'Malabar spinach (Alugbati)', 'icon' => '🥬', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 45, 'pattern' => 'leafy',
        ],
        'saluyot' => [
            'label' => 'Jute mallow (Saluyot)', 'icon' => '🥬', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAS', 'maturity' => 40, 'pattern' => 'leafy',
        ],
        'celery' => [
            'label' => 'Celery (Kintsay)', 'icon' => '🥬', 'group' => 'Leafy vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 105, 'pattern' => 'leafy',
        ],

        /* ---------------- Fruit vegetables ---------------- */
        'tomato' => [
            'label' => 'Tomato (Kamatis)', 'icon' => '🍅', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 80, 'pattern' => 'fruiting',
        ],
        'eggplant' => [
            'label' => 'Eggplant (Talong)', 'icon' => '🍆', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 80, 'pattern' => 'fruiting',
        ],
        'ampalaya' => [
            'label' => 'Bitter gourd (Ampalaya)', 'icon' => '🥒', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 62, 'pattern' => 'cucurbit',
        ],
        'squash' => [
            'label' => 'Squash (Kalabasa)', 'icon' => '🎃', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 90, 'pattern' => 'cucurbit',
        ],
        'cucumber' => [
            'label' => 'Cucumber (Pipino)', 'icon' => '🥒', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 50, 'pattern' => 'cucurbit',
        ],
        'okra' => [
            'label' => 'Okra', 'icon' => '🌿', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 50, 'pattern' => 'fruiting',
        ],
        'chili' => [
            'label' => 'Chili (Sili)', 'icon' => '🌶️', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 85, 'pattern' => 'fruiting',
        ],
        'bellpepper' => [
            'label' => 'Bell pepper (Atsal)', 'icon' => '🫑', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 85, 'pattern' => 'fruiting',
        ],
        'patola' => [
            'label' => 'Sponge gourd (Patola)', 'icon' => '🥒', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 62, 'pattern' => 'cucurbit',
        ],
        'upo' => [
            'label' => 'Bottle gourd (Upo)', 'icon' => '🥒', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 68, 'pattern' => 'cucurbit',
        ],
        'sayote' => [
            'label' => 'Chayote (Sayote)', 'icon' => '🥒', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 105, 'pattern' => 'cucurbit',
            'counters' => ['DAP'],
        ],
        'watermelon' => [
            'label' => 'Watermelon (Pakwan)', 'icon' => '🍉', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 85, 'pattern' => 'cucurbit',
        ],
        'melon' => [
            'label' => 'Melon (Milon)', 'icon' => '🍈', 'group' => 'Fruit vegetables',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 80, 'pattern' => 'cucurbit',
        ],

        /* ---------------- Onions & garlic ---------------- */
        'onion' => [
            'label' => 'Onion — bulb (Sibuyas)', 'icon' => '🧅', 'group' => 'Onions & garlic',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 115, 'pattern' => 'bulb',
            'counters' => ['DAT', 'DAS', 'DAP'],
        ],
        'onion_spring' => [
            'label' => 'Spring onion (Sibuyas na mura)', 'icon' => '🧅', 'group' => 'Onions & garlic',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 50, 'pattern' => 'leafy',
        ],
        'garlic' => [
            'label' => 'Garlic (Bawang)', 'icon' => '🧄', 'group' => 'Onions & garlic',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 125, 'pattern' => 'bulb',
            'counters' => ['DAP'],
        ],
        'shallot' => [
            'label' => 'Shallot (Sibuyas Tagalog)', 'icon' => '🧅', 'group' => 'Onions & garlic',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 85, 'pattern' => 'bulb',
            'counters' => ['DAP'],
        ],

        /* ---------------- Industrial & plantation ---------------- */
        'sugarcane' => [
            'label' => 'Sugarcane (Tubo)', 'icon' => '🎋', 'group' => 'Industrial crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 330,
            'counters' => ['DAP'],
        ],
        'pineapple' => [
            'label' => 'Pineapple (Pinya)', 'icon' => '🍍', 'group' => 'Industrial crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 480, 'pattern' => 'root',
            'counters' => ['DAP'],
        ],
        'tobacco' => [
            'label' => 'Tobacco (Tabako)', 'icon' => '🌿', 'group' => 'Industrial crops',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 105, 'pattern' => 'leafy',
        ],
        'cotton' => [
            'label' => 'Cotton (Bulak)', 'icon' => '🌾', 'group' => 'Industrial crops',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 165, 'pattern' => 'fruiting',
        ],
        'abaca' => [
            'label' => 'Abaca (Abaka)', 'icon' => '🌿', 'group' => 'Industrial crops',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 20,
        ],
        'rubber' => [
            'label' => 'Rubber (Goma)', 'icon' => '🌳', 'group' => 'Industrial crops',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 72,
        ],
        'oilpalm' => [
            'label' => 'Oil palm (Palma)', 'icon' => '🌴', 'group' => 'Industrial crops',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 36,
        ],
        'bamboo' => [
            'label' => 'Bamboo (Kawayan)', 'icon' => '🎋', 'group' => 'Industrial crops',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 36,
        ],

        /* ---------------- Fruit trees ---------------- */
        'banana' => [
            'label' => 'Banana (Saging)', 'icon' => '🍌', 'group' => 'Fruit trees',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 330,
            'counters' => ['DAP'],
        ],
        'papaya' => [
            'label' => 'Papaya', 'icon' => '🍈', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 9,
        ],
        'mango' => [
            'label' => 'Mango (Mangga)', 'icon' => '🥭', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 60,
        ],
        'coconut' => [
            'label' => 'Coconut (Niyog)', 'icon' => '🥥', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 72,
        ],
        'calamansi' => [
            'label' => 'Calamansi (Kalamansi)', 'icon' => '🍋', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 30,
        ],
        'citrus' => [
            'label' => 'Citrus — dalandan / ponkan', 'icon' => '🍊', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 42,
        ],
        'pomelo' => [
            'label' => 'Pomelo (Suha)', 'icon' => '🍊', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 48,
        ],
        'jackfruit' => [
            'label' => 'Jackfruit (Langka)', 'icon' => '🍈', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 42,
        ],
        'avocado' => [
            'label' => 'Avocado (Abukado)', 'icon' => '🥑', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 48,
        ],
        'guava' => [
            'label' => 'Guava (Bayabas)', 'icon' => '🍐', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 24,
        ],
        'lanzones' => [
            'label' => 'Lanzones (Lansones)', 'icon' => '🫒', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 96,
        ],
        'rambutan' => [
            'label' => 'Rambutan', 'icon' => '🍒', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 60,
        ],
        'durian' => [
            'label' => 'Durian (Duryan)', 'icon' => '🍈', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 72,
        ],
        'mangosteen' => [
            'label' => 'Mangosteen (Mangostan)', 'icon' => '🫐', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 96,
        ],
        'chico' => [
            'label' => 'Chico (Sapodilla)', 'icon' => '🥔', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 48,
        ],
        'atis' => [
            'label' => 'Sugar apple (Atis)', 'icon' => '🍏', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 30,
        ],
        'guyabano' => [
            'label' => 'Soursop (Guyabano)', 'icon' => '🍏', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 36,
        ],
        'santol' => [
            'label' => 'Santol', 'icon' => '🍑', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 60,
        ],
        'starapple' => [
            'label' => 'Star apple (Kaimito)', 'icon' => '🍏', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 60,
        ],
        'tamarind' => [
            'label' => 'Tamarind (Sampalok)', 'icon' => '🫘', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 72,
        ],
        'cashew' => [
            'label' => 'Cashew (Kasoy)', 'icon' => '🥜', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 36,
        ],
        'dragonfruit' => [
            'label' => 'Dragon fruit (Saniata)', 'icon' => '🐉', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 18,
        ],
        'coffee' => [
            'label' => 'Coffee (Kape)', 'icon' => '☕', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 36,
        ],
        'cacao' => [
            'label' => 'Cacao (Kakaw)', 'icon' => '🍫', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 30,
        ],
        'malunggay' => [
            'label' => 'Moringa (Malunggay)', 'icon' => '🌿', 'group' => 'Fruit trees',
            'kind' => self::PERENNIAL, 'counter' => 'AGE', 'bearingAt' => 8,
        ],

        /* ---------------- The catch-all ---------------- */
        'vegetables' => [
            'label' => 'Vegetables — mixed (Gulay)', 'icon' => '🥗', 'group' => 'Other',
            'kind' => self::ANNUAL, 'counter' => 'DAP', 'maturity' => 70, 'pattern' => 'fruiting',
        ],
        'strawberry' => [
            'label' => 'Strawberry (Presa)', 'icon' => '🍓', 'group' => 'Other',
            'kind' => self::ANNUAL, 'counter' => 'DAT', 'maturity' => 75, 'pattern' => 'fruiting',
        ],
    ];

    /** The order the picker shows its sections in. */
    public const GROUPS = [
        'Cereals & grains',
        'Fruit vegetables',
        'Leafy vegetables',
        'Root crops',
        'Legumes',
        'Onions & garlic',
        'Fruit trees',
        'Industrial crops',
        'Other',
    ];

    /** Does this crop live for years, and count its age rather than its days? */
    /**
     * Crops recorded under a name this list no longer uses.
     *
     * `corn` is the one that matters: this catalogue splits it into yellow,
     * sweet and glutinous, so lots recorded before the split match nothing.
     * The Tagalog names are from before there was a list to pick from.
     *
     * A twin of the farmer app's CropStages::RENAMED — both sides read the
     * same rows, so both have to know the same old names.
     */
    public const RENAMED = [
        'corn' => 'corn_yellow',
        'mais' => 'corn_yellow',
        'palay' => 'rice',
        'saging' => 'banana',
        'mangga' => 'mango',
        'niyog' => 'coconut',
        'tubo' => 'sugarcane',
        'gulay' => 'vegetables',
    ];

    /**
     * A stored crop key as this catalogue knows it.
     *
     * Everything that looks a crop up should come through here, or a lot
     * recorded under an old name reads as a lot with no crop at all.
     */
    public static function normalize(?string $crop): ?string
    {
        $crop = strtolower(trim((string) $crop));
        if ($crop === '') {
            return null;
        }
        if (isset(self::CROPS[$crop])) {
            return $crop;
        }

        return self::RENAMED[$crop] ?? null;
    }

    public static function isPerennial(?string $key): bool
    {
        return ($key && isset(self::CROPS[$key]))
            && (self::CROPS[$key]['kind'] ?? self::ANNUAL) === self::PERENNIAL;
    }

    /**
     * The ways THIS crop can honestly be counted.
     *
     * The lot form offered all three to everything, which is how a mango lot
     * came to be set to "sown, then transplanted" and a cassava lot to DAS.
     * Neither is a thing that happens: cassava goes in as a cutting and has
     * no sowing to count from, and a standing tree has no day zero at all.
     *
     * Read off how the crop is actually established, not off a preference:
     *   • a crop raised in a seedbed and moved         → DAT, or DAS if
     *     somebody direct-seeds it instead
     *   • a crop sown where it stands                  → DAS or DAP
     *   • a crop planted as a cutting, tuber or set    → DAP only, because
     *     there was never a seed in the ground
     *   • a standing perennial                         → TREE only
     *
     * The first entry is the one the form picks by default: the way the crop
     * is most often grown here.
     *
     * @return array<int, string>
     */
    public static function countersFor(?string $key): array
    {
        if (! $key || ! isset(self::CROPS[$key])) {
            // Nothing chosen: every way is still open.
            return ['DAT', 'DAS', 'DAP'];
        }
        if (self::isPerennial($key)) {
            return ['TREE'];
        }

        $crop = self::CROPS[$key];
        if (! empty($crop['counters'])) {
            return $crop['counters'];
        }

        return match ($crop['counter'] ?? 'DAP') {
            'DAT' => ['DAT', 'DAS'],
            'DAS' => ['DAS', 'DAP'],
            default => ['DAP', 'DAS'],
        };
    }

    /** The crop's own typical days to harvest, before a lot overrides it. */
    public static function maturity(?string $key): ?int
    {
        if (! $key || ! isset(self::CROPS[$key])) {
            return null;
        }

        return isset(self::CROPS[$key]['maturity']) ? (int) self::CROPS[$key]['maturity'] : null;
    }

    /** The age in months a tree usually first crops at. */
    public static function bearingAt(?string $key): ?int
    {
        if (! $key || ! isset(self::CROPS[$key])) {
            return null;
        }

        return isset(self::CROPS[$key]['bearingAt']) ? (int) self::CROPS[$key]['bearingAt'] : null;
    }

    /**
     * A crop's stage table, in the units it is counted in.
     *
     * Annuals: days, laid out against `$maturity` — the lot's own figure when
     * it has one, the crop's otherwise. A crop with a hand-written table uses
     * it as written and is not stretched, because those numbers are the
     * crop's real calendar rather than a shape fitted to it.
     *
     * Perennials: months of age, with the tree's own first-bearing age
     * standing in for the pattern's generic one.
     *
     * @return array<int, array{0:int,1:string,2:string,3:string}>
     */
    public static function stages(?string $key, ?int $maturity = null): array
    {
        if (! $key || ! isset(self::CROPS[$key])) {
            return [];
        }
        $crop = self::CROPS[$key];

        if (self::isPerennial($key)) {
            return self::treeStages((int) ($crop['bearingAt'] ?? 36));
        }

        $pattern = $crop['pattern'] ?? null;
        if (! $pattern || ! isset(self::PATTERNS[$pattern])) {
            return [];   // hand-written, and CropStages holds it
        }

        $days = $maturity ?: (int) ($crop['maturity'] ?? 0);
        if ($days < 7) {
            return [];
        }

        $out = [];
        foreach (self::PATTERNS[$pattern] as $row) {
            $out[] = [(int) round($row[0] * $days), $row[1], $row[2], $row[3]];
        }

        return self::spreadOut($out);
    }

    /**
     * The tree pattern, moved so that "first bearing" lands on this tree's
     * own age rather than the generic one. A calamansi crops at two and a
     * half years and a lanzones at eight; one table for both would tell the
     * calamansi grower to wait five more years.
     */
    private static function treeStages(int $bearingAt): array
    {
        $pattern = self::PATTERNS['tree'];
        $generic = (int) $pattern[2][0];   // the pattern's own first-bearing month
        $scale = $generic > 0 ? $bearingAt / $generic : 1;

        $out = [];
        foreach ($pattern as $i => $row) {
            // Establishment always starts at nought; the rest move with the
            // tree, and the last one is capped so a slow bearer's decline is
            // not pushed beyond a lifetime.
            $at = $i === 0 ? 0 : (int) round($row[0] * $scale);
            $out[] = [min($at, 600), $row[1], $row[2], $row[3]];
        }

        return self::spreadOut($out);
    }

    /**
     * No two stages may begin on the same number.
     *
     * Rounding fractions of a short season collides — a thirty-day kangkong
     * puts two stages on day five — and a table with two rows for one day
     * makes the second unreachable, so the crop would skip a stage entirely.
     */
    private static function spreadOut(array $rows): array
    {
        $last = -1;
        foreach ($rows as $i => $row) {
            if ($row[0] <= $last) {
                $rows[$i][0] = $last + 1;
            }
            $last = $rows[$i][0];
        }

        return $rows;
    }
}
