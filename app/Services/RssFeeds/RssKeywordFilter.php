<?php

namespace App\Services\RssFeeds;

/**
 * The keyword filters on sources and combined feeds.
 *
 * Keywords are separated by commas or new lines and matched against a story's
 * title and summary, ignoring case, as WHOLE words: "rice" must not let in
 * every story about prices. A trailing * matches the start of a word, so
 * "farm*" takes farm, farmer and farming. A phrase ("fuel price") matches with
 * any spacing between its words.
 *
 * A story passes when it has at least one "include" keyword (or there are
 * none) and no "exclude" keyword.
 */
class RssKeywordFilter
{
    /** @var string[] */
    private array $include;

    /** @var string[] */
    private array $exclude;

    public function __construct(?string $include, ?string $exclude)
    {
        $this->include = array_map([self::class, 'pattern'], self::words($include));
        $this->exclude = array_map([self::class, 'pattern'], self::words($exclude));
    }

    public function isEmpty(): bool
    {
        return ! $this->include && ! $this->exclude;
    }

    public function passes(?string $title, ?string $summary = null): bool
    {
        if ($this->isEmpty()) {
            return true;
        }
        $text = $title . "\n" . $summary;
        foreach ($this->exclude as $p) {
            if (preg_match($p, $text)) {
                return false;
            }
        }
        if (! $this->include) {
            return true;
        }
        foreach ($this->include as $p) {
            if (preg_match($p, $text)) {
                return true;
            }
        }

        return false;
    }

    /** @return string[] */
    public static function words(?string $text): array
    {
        $words = preg_split('/[,\n\r]+/', (string) $text) ?: [];
        $words = array_map(fn ($w) => trim(preg_replace('/\s+/u', ' ', trim($w, " \t\"'"))), $words);

        return array_values(array_unique(array_filter($words, fn ($w) => $w !== '' && $w !== '*')));
    }

    private static function pattern(string $word): string
    {
        $prefix = str_ends_with($word, '*');
        $word = rtrim($word, '*');
        $body = str_replace(' ', '\s+', preg_quote($word, '/'));

        return '/(?<![\p{L}\p{N}])' . $body . ($prefix ? '' : '(?![\p{L}\p{N}])') . '/iu';
    }
}
