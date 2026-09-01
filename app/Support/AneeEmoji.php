<?php

namespace App\Support;

/**
 * Anee's own faces, as the admin console needs to read them.
 *
 * A copy of the farmer app's list, and deliberately a copy. The shortcode
 * `:anee-happy:` is written into the database by one app and read back by
 * two, so both have to know the same names; fetching the list across would
 * mean an admin could not read a thread while the other app was redeploying.
 * Fifty-six names and fifty-six small drawings is a cheap thing to hold twice.
 *
 * Both halves are here: turning a shortcode into a picture, for reading a
 * thread, and describing the sheet to a model, for answering in one. The
 * console does both now — it can read a client's conversation and continue
 * it, and an Anee who did not know the faces existed would stop using them
 * mid-thread.
 *
 * If a face is ever added on the far side, add it here and drop its PNG into
 * public/images/anee/emoji. An unknown name is left as written rather than
 * turned into a broken picture.
 */
class AneeEmoji
{
    /** Every face on the sheet, and what it is. */
    public const FACES = [
        'happy' => 'beaming, mouth open',
        'smile' => 'a plain, easy smile',
        'wink' => 'winking',
        'love' => 'hearts for eyes',
        'starstruck' => 'stars for eyes, delighted',
        'amazed' => 'stars for eyes, mouth open',
        'teary' => 'welling up, frightened',
        'wince' => 'one eye shut, wincing',
        'angry' => 'genuinely cross',
        'gritted' => 'teeth gritted, exasperated',
        'worried' => 'worried',
        'blank' => 'no expression at all',
        'disappointed' => 'eyes shut, disappointed',
        'unamused' => 'not amused',
        'thinking' => 'hand to chin, thinking',
        'plain' => 'neutral, listening',
        'grin' => 'eyes shut, grinning',
        'sleepy' => 'wide-eyed and only just awake',
        'idea' => 'a lightbulb, just thought of something',
        'flat' => 'flat, unimpressed',
        'blushing' => 'blushing hard',
        'shocked' => 'eyes wide, mouth open',
        'content' => 'eyes shut, content',
        'sleeping' => 'asleep',
        'shy' => 'eyes shut, shy and pleased',
        'thumbsup' => 'thumbs up',
        'unsure' => 'unsure',
        'uneasy' => 'uneasy',
        'serious' => 'serious',
        'salute' => 'saluting',
        'leaf' => 'holding up a leaf, winking',
        'flower' => 'offering a flower',
        'concerned' => 'concerned',
        'oops' => 'hand to mouth, caught out',
        'alarmed' => 'alarmed',
        'crying' => 'crying',
        'glum' => 'glum',
        'laughing' => 'laughing till she cries',
        'kiss' => 'blowing a kiss',
        'pucker' => 'pursed lips',
        'facepalm' => 'hand over face',
        'weary' => 'worn out',
        'heart' => 'holding a heart',
        'smirk' => 'smirking',
        'relieved' => 'relieved',
        'yes' => 'a green tick — yes, that is right',
        'choose' => 'a tick and a cross — one or the other',
        'no' => 'a red cross — no, not that',
        'wave' => 'waving hello',
        'doubtful' => 'doubtful',
        'meh' => 'meh',
        'calm' => 'calm',
        'sad' => 'sad',
        'whisper' => 'hand beside mouth, letting you in on something',
        'delighted' => 'eyes shut, delighted',
        'cheer' => 'cheering',
    ];

    /**
     * The sheet as headings, for the paragraph the model is shown.
     *
     * Another twin of the farmer app's copy: she is choosing a face from
     * words alone, so the grouping is part of how she chooses.
     */
    public const GROUPS = [
        'Hello, and goodbye' => ['wave', 'salute'],
        'Pleased, impressed, celebrating with them' => [
            'happy', 'grin', 'cheer', 'delighted', 'starstruck', 'amazed',
            'laughing', 'thumbsup', 'love', 'heart', 'flower',
        ],
        'Warm and ordinary' => ['smile', 'wink', 'calm', 'relieved', 'leaf', 'content'],
        'Working something out' => ['thinking', 'idea', 'unsure', 'doubtful', 'whisper'],
        'Yes, no, or one of the two' => ['yes', 'no', 'choose'],
        'Bad news, worry, or bad luck of theirs' => [
            'concerned', 'worried', 'alarmed', 'shocked', 'sad', 'teary',
            'wince', 'weary', 'glum',
        ],
        'A mistake of your own' => ['oops', 'facepalm', 'blushing'],
        'Grave, and meant to be' => ['serious'],
    ];

    public static function has(string $name): bool
    {
        return isset(self::FACES[$name]);
    }

    public static function url(string $name): string
    {
        return asset('images/anee/emoji/' . $name . '.png');
    }

    /**
     * Move every face in one block to an edge of it.
     *
     * A face already leading the block stays leading; everything else goes to
     * the end, in the order written. A face mid-sentence is nearly always a
     * reaction to the clause it follows, and the end of that paragraph is the
     * nearest honest place to put it. The farmer app arranges them the same
     * way, so a thread reads the same in both.
     */
    private static function arrange(string $inner): string
    {
        if (! preg_match_all('/:anee-[a-z]{2,14}:/', $inner, $m, PREG_OFFSET_CAPTURE)) {
            return $inner;
        }

        $tokens = array_map(fn ($x) => $x[0], $m[0]);
        // "Leading" means nothing but whitespace and inline opening tags
        // stands before it — <strong> counts as nothing, a word does not.
        $before = substr($inner, 0, $m[0][0][1]);
        $leads = trim(strip_tags($before)) === '';

        $rest = preg_replace('/:anee-[a-z]{2,14}:/', '', $inner);
        // Tidy what the removals left: doubled spaces where a face sat
        // between two words, and a space in front of punctuation it stood
        // before.
        $rest = preg_replace('/[ \t]{2,}/', ' ', (string) $rest);
        $rest = preg_replace('/\s+([,.;:!?])/', '$1', (string) $rest);
        $rest = preg_replace('#<(strong|b|em|i|u|span)>\s*</\1>#i', '', (string) $rest);
        $rest = trim((string) $rest);

        $head = $leads ? array_shift($tokens) : null;

        return trim(($head ? $head . ' ' : '')
            . $rest
            . ($tokens ? ' ' . implode(' ', $tokens) : ''));
    }

    /** The same, over every block in a rendered answer. */
    public static function toEdges(string $html): string
    {
        if (! preg_match('#<(p|li|h[1-6])\b#i', $html)) {
            return self::arrange($html);
        }

        return (string) preg_replace_callback(
            '#(<(p|li|h[1-6])\b[^>]*>)(.*?)(</\2>)#si',
            fn ($m) => $m[1] . self::arrange($m[3]) . $m[4],
            $html
        );
    }

    /**
     * Swap every `:anee-name:` for its picture.
     *
     * Takes ALREADY-ESCAPED html. The shortcode is plain text and survives
     * escaping unchanged, so this runs last and nothing a model wrote can
     * reach the page as markup.
     */
    public static function render(string $html): string
    {
        $html = self::toEdges($html);

        return (string) preg_replace_callback(
            '/:anee-([a-z]{2,14}):/',
            function ($m) {
                if (! self::has($m[1])) {
                    return $m[0];
                }

                return '<span class="anee-emo"><img src="' . e(self::url($m[1]))
                    . '" alt="' . e(self::FACES[$m[1]]) . '" loading="lazy"></span>';
            },
            $html
        );
    }

    /**
     * A whole message, from what is in the database to what goes on screen.
     *
     * Escape first, then the little of Anee's formatting that carries
     * meaning — her bold — then the faces. In that order: escaping last would
     * eat the markup this adds, and arranging the faces after they are
     * pictures would mean moving <img> tags about.
     *
     * Her line breaks are left as the newlines they are. The bubble that
     * holds this is set to pre-wrap, so turning them into <br> here would
     * space every paragraph twice.
     */
    public static function body(?string $raw): string
    {
        $html = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', e((string) $raw));

        return self::render((string) $html);
    }

    /** The paragraph the model is given. */
    public static function promptLine(): string
    {
        $block = '';
        foreach (self::GROUPS as $heading => $names) {
            $block .= '  ' . $heading . ":\n";
            foreach ($names as $name) {
                $block .= '    :anee-' . $name . ': -- ' . self::FACES[$name] . "\n";
            }
        }

        return "--- Your face, and how a reply looks ---\n"
            . "You have a face and you may show it. Writing :anee-NAME: puts a\n"
            . "small picture of yourself wearing that expression into the reply.\n"
            . "Find the moment first, then take the face from it:\n"
            . $block
            . "\n"
            . "How to wear them:\n"
            . "  - AT THE START OR THE END OF A PARAGRAPH, never inside a\n"
            . "    sentence. A face is punctuation: one on the way in, or one on\n"
            . "    the way out. Mid-clause it splits the line it lands on and the\n"
            . "    eye stops on it halfway through what you are saying.\n"
            . "  - Two or three in a reply that has two or three beats to it: the\n"
            . "    reaction at the top, the turn at the end of a paragraph, the\n"
            . "    warning at the end of the last. One is plenty for a short\n"
            . "    answer. None at all is fine for a grave one.\n"
            . "  - Never the same face twice in one reply, and do not open every\n"
            . "    reply with the one you opened the last one with. :anee-smile: is\n"
            . "    not a default -- it is what you wear when nothing stronger fits.\n"
            . "  - Match the face to what you are actually saying. Celebrating a\n"
            . "    harvest is :anee-cheer: or :anee-starstruck:, not a polite smile;\n"
            . "    bad news is :anee-concerned: or :anee-sad:; your own mistake is\n"
            . "    :anee-oops:.\n"
            . "  - Ordinary emoji are welcome too where they carry meaning rather\n"
            . "    than decorate: a crop, the weather, a pest, water, money.\n"
            . "\n"
            . "Lay a reply out so it can be read on a phone in a field:\n"
            . "  - Short paragraphs with a blank line between them.\n"
            . "  - **Bold** the thing that matters most -- a rate, a date, a warning.\n"
            . "  - Bullets or numbered steps when there is more than one thing to do.\n"
            . "  - A line of three dashes (---) on its own draws a divider, for when\n"
            . "    the answer turns from what is wrong to what to do about it.\n"
            . "\n"
            . "The one rule that does not bend: a face is never INSTEAD of saying\n"
            . "the thing. Bad news is still said plainly and early, a diagnosis is\n"
            . "still a diagnosis, and a reply about a loss or a mistake of your own\n"
            . "is better with a plain word than a picture.";
    }
}
