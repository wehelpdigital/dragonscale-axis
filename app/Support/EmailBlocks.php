<?php

namespace App\Support;

/**
 * Turns an email template's blocks into the HTML that gets sent.
 *
 * Email is not the web: no stylesheet survives the trip, so everything here is
 * inline styles on tables and paragraphs — the shapes that still render the
 * same in Outlook, Gmail and a phone's mail app. The builder drags blocks; the
 * ugliness of making them safe lives here, once.
 *
 * THE FRAME IS anee.io's, NOT OURS. Every template anee.io ships is built by
 * its App\Support\EmailSkin: the dark green masthead with the logo and one of
 * Anee's faces, the card, the footer — with the words marked off between two
 * comments. The builder only ever writes the words: wrap() lifts the frame off
 * the template's own body and puts the new words inside it, so a layout built
 * here looks like every other anee.io email, and a template never built with
 * blocks keeps its masthead when somebody starts. Each block below draws the
 * same shapes EmailSkin draws (the same panel, the same gold button), which is
 * what keeps the daily digest's shipped body and its blocks one thing.
 *
 * Merge fields pass straight through as {{tags}} — the sending app fills them
 * in, because only it knows whose email this is.
 */
class EmailBlocks
{
    /** What the builder can drag onto an email. */
    public const KINDS = [
        'heading' => 'Heading',
        'text' => 'Paragraph',
        'activities' => 'The day\'s activities',
        'button' => 'Button',
        'tips' => 'Bullet list',
        'callout' => 'Highlighted box',
        'note' => 'Small print',
        'divider' => 'Divider',
        'spacer' => 'Space',
    ];

    /** Fields the sender fills in. Shown in the builder as chips to insert. */
    public const MERGE_FIELDS = [
        '{{recipient_name}}' => 'Who it is addressed to',
        '{{schedule_title}}' => 'The schedule name',
        '{{today_date}}' => "Today's date",
        '{{tomorrow_date}}' => "Tomorrow's date",
        '{{today_count}}' => 'How many activities today',
        '{{tomorrow_count}}' => 'How many activities tomorrow',
        '{{app_name}}' => 'anee.io',
    ];

    /* Where anee.io's EmailSkin marks off the words inside its frame. */
    private const WORDS = '~(<!-- ===== The words start here\..*?-->)(.*?)(<!-- ===== The words end here\. ===== -->)~s';

    private const FONT = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    /**
     * @param  array<int, array<string, mixed>>|null  $blocks
     */
    public static function render(?array $blocks): string
    {
        $out = '';
        foreach ($blocks ?? [] as $b) {
            $out .= self::one(is_array($b) ? $b : []);
        }

        return $out;
    }

    private static function one(array $b): string
    {
        $kind = (string) ($b['kind'] ?? '');
        $text = trim((string) ($b['text'] ?? ''));
        $e = fn ($v) => e((string) $v);

        switch ($kind) {
            case 'heading':
                return $text === '' ? '' : '<h2 class="ae-ink" style="margin:0 0 12px;font-size:20px;line-height:1.3;'
                    . 'font-weight:800;color:#1f2a17;">' . $e($text) . '</h2>';

            case 'text':
                if ($text === '') {
                    return '';
                }
                $paras = preg_split('~\n\s*\n~', $text) ?: [];

                // No colour of its own: it inherits the card's, which is
                // what lets a dark-mode mail app repaint it.
                return implode('', array_map(
                    fn ($p) => '<p style="margin:0 0 16px;">' . nl2br($e(trim($p))) . '</p>',
                    array_filter($paras, fn ($p) => trim($p) !== '')
                ));

            case 'note':
                return $text === '' ? '' : '<p class="ae-muted" style="margin:18px 0 16px;font-size:13.5px;line-height:1.55;color:#5f6b55;">'
                    . nl2br($e($text)) . '</p>';

            case 'tips':
                $items = array_values(array_filter(
                    array_map('trim', (array) ($b['items'] ?? [])),
                    fn ($i) => $i !== ''
                ));

                return $items ? '<ul style="margin:0 0 16px 20px;padding:0;">'
                    . implode('', array_map(fn ($i) => '<li style="margin:0 0 6px;">' . $e($i) . '</li>', $items))
                    . '</ul>' : '';

            case 'callout':
                if ($text === '') {
                    return '';
                }
                $title = trim((string) ($b['title'] ?? ''));

                return self::panel(
                    ($title !== '' ? '<strong style="display:block;margin-bottom:3px;">' . $e($title) . '</strong>' : '')
                    . nl2br($e($text))
                );

            case 'button':
                $url = trim((string) ($b['url'] ?? ''));
                $label = trim((string) ($b['text'] ?? 'Open'));
                if ($url === '') {
                    return '';
                }

                return self::button($label, $url);

            case 'activities':
                // A placeholder the sender swaps for the real list — it is the
                // only block whose content the template cannot know.
                return '{{activities_list}}';

            case 'divider':
                return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;">'
                    . '<tr><td class="ae-line" style="border-top:1px solid #e3eadb;height:1px;line-height:1px;font-size:0;">&nbsp;</td></tr></table>';

            case 'spacer':
                return '<div style="height:18px;line-height:18px;font-size:0;">&nbsp;</div>';
        }

        return '';
    }

    /** The gold heads-up panel — the same one anee.io's EmailSkin::panel(…, 'gold') draws. */
    private static function panel(string $inner): string
    {
        return '<table role="presentation" class="ae-panel ae-panel-gold" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#fdf6dc" '
            . 'style="background:#fdf6dc;border:1px solid #efd98a;border-radius:14px;border-collapse:separate;margin:18px 0;">'
            . '<tr><td style="padding:16px 18px;font-size:15px;line-height:1.6;">' . $inner . '</td></tr>'
            . '</table>';
    }

    /** The gold pill — the same one anee.io's EmailSkin::button() draws. */
    private static function button(string $label, string $url): string
    {
        $l = e($label);
        $u = e($url);

        return '<table role="presentation" class="ae-btn" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 8px;">'
            . '<tr><td align="center" bgcolor="#E8BE1C" style="background:#E8BE1C;border-radius:999px;mso-padding-alt:0;">'
            . '<a href="' . $u . '" target="_blank" style="display:inline-block;padding:15px 30px;font-family:' . self::FONT . ';'
            . 'font-size:16px;line-height:20px;font-weight:800;color:#2B3A1C;text-decoration:none;border-radius:999px;mso-padding-alt:0;">'
            . '<!--[if mso]><i style="letter-spacing:30px;mso-font-width:-100%;mso-text-raise:22pt;">&nbsp;</i><![endif]-->'
            . '<span style="mso-text-raise:11pt;color:#2B3A1C;">' . $l . '</span>'
            . '<!--[if mso]><i style="letter-spacing:30px;mso-font-width:-100%;">&nbsp;</i><![endif]-->'
            . '</a></td></tr></table>';
    }

    /** True when this HTML carries anee.io's frame with the words marked off. */
    public static function hasFrame(?string $html): bool
    {
        return is_string($html) && preg_match(self::WORDS, $html) === 1;
    }

    /**
     * Put the words in the frame.
     *
     * $frame is the template's own current body (or the anee.io default for
     * it): its masthead, card and footer are kept and only the words between
     * the markers change. Without a frame to borrow — a template from before
     * the anee.io design — the words get a plain card of their own.
     */
    public static function wrap(string $inner, string $title = '', ?string $frame = null): string
    {
        if (self::hasFrame($frame)) {
            return preg_replace_callback(
                self::WORDS,
                fn ($m) => $m[1] . "\n" . $inner . "\n" . $m[3],
                (string) $frame,
                1
            );
        }

        $font = self::FONT;

        return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#eef2e8;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#eef2e8" style="background:#eef2e8;">'
            . '<tr><td align="center" style="padding:28px 12px 32px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">'
            . '<tr><td bgcolor="#2B3A1C" style="background:#2B3A1C;border-radius:18px 18px 0 0;padding:22px 32px;">'
            . '<img src="https://anee.io/images/logo-white.png?v=anee" width="138" height="21" alt="anee.io" style="display:block;border:0;"></td></tr>'
            . '<tr><td bgcolor="#ffffff" style="background:#ffffff;border-radius:0 0 18px 18px;padding:30px 32px 18px;'
            . 'font-family:' . $font . ';font-size:16px;line-height:1.6;color:#1f2a17;">' . $inner . '</td></tr>'
            . '<tr><td align="center" style="padding:18px 24px 0;font-family:' . $font . ';font-size:12px;color:#6f7a64;">'
            . e($title) . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /**
     * The plain-text twin of an email (the same rules as anee.io's
     * EmailSkin::toText): no stylesheet, no preview padding, no Outlook
     * comments — the words, the breaks, and every link written out.
     */
    public static function toText(string $html): string
    {
        $t = $html;
        $t = preg_replace('~<(head|style|script|title)\b[^>]*>.*?</\1>~is', '', $t) ?? $t;
        $t = preg_replace('~<div class="ae-pre".*?</div>~is', '', $t) ?? $t;
        $t = preg_replace('~<!--.*?-->~s', '', $t) ?? $t;
        $t = preg_replace_callback('~<a\b[^>]*href="([^"]*)"[^>]*>(.*?)</a>~is', function ($m) {
            $href = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $label = trim(preg_replace('~\s+~', ' ', strip_tags($m[2])) ?? '');
            if ($label === '' || $label === $href || str_starts_with($href, 'mailto:') && $label === substr($href, 7)) {
                return $label;
            }

            return $label . ' (' . $href . ')';
        }, $t) ?? $t;
        $t = preg_replace('~<img\b[^>]*>~i', '', $t) ?? $t;
        $t = preg_replace('~<br\s*/?>~i', "\n", $t) ?? $t;
        $t = preg_replace('~<li\b[^>]*>~i', "\n- ", $t) ?? $t;
        $t = preg_replace('~</(p|h[1-6]|table|ul|ol|blockquote)>~i', "\n\n", $t) ?? $t;
        $t = preg_replace('~</(div|tr)>~i', "\n", $t) ?? $t;
        $t = preg_replace('~</td>~i', ' ', $t) ?? $t;
        $t = strip_tags($t);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = str_replace(["\u{00A0}", "\u{2007}", "\u{FEFF}", "\u{034F}"], [' ', '', '', ''], $t);
        $lines = array_map(fn ($l) => trim(preg_replace('~[ \t]+~', ' ', $l) ?? $l), explode("\n", str_replace("\r", '', $t)));
        $t = preg_replace("~\n{3,}~", "\n\n", implode("\n", $lines)) ?? '';

        return trim($t) . "\n";
    }
}
