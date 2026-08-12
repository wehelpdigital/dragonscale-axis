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
        '{{app_name}}' => 'AniSystem',
    ];

    private const TEXT = 'margin:0 0 14px;font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#374151;';

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
                return $text === '' ? '' : '<h2 style="margin:0 0 12px;font-family:Helvetica,Arial,sans-serif;'
                    . 'font-size:19px;line-height:1.3;color:#111827;">' . $e($text) . '</h2>';

            case 'text':
                if ($text === '') {
                    return '';
                }
                $paras = preg_split('~\n\s*\n~', $text) ?: [];

                return implode('', array_map(
                    fn ($p) => '<p style="' . self::TEXT . '">' . nl2br($e(trim($p))) . '</p>',
                    array_filter($paras, fn ($p) => trim($p) !== '')
                ));

            case 'tips':
                $items = array_values(array_filter(
                    array_map('trim', (array) ($b['items'] ?? [])),
                    fn ($i) => $i !== ''
                ));

                return $items ? '<ul style="margin:0 0 14px 18px;padding:0;' . self::TEXT . '">'
                    . implode('', array_map(fn ($i) => '<li style="margin:0 0 6px;">' . $e($i) . '</li>', $items))
                    . '</ul>' : '';

            case 'callout':
                if ($text === '') {
                    return '';
                }

                return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">'
                    . '<tr><td style="background:#f0fdf4;border-left:4px solid #22c55e;padding:12px 14px;'
                    . 'font-family:Helvetica,Arial,sans-serif;font-size:14px;line-height:1.55;color:#14532d;">'
                    . (trim((string) ($b['title'] ?? '')) !== ''
                        ? '<strong style="display:block;margin-bottom:3px;">' . $e($b['title']) . '</strong>' : '')
                    . nl2br($e($text)) . '</td></tr></table>';

            case 'button':
                $url = trim((string) ($b['url'] ?? ''));
                $label = trim((string) ($b['text'] ?? 'Open'));
                if ($url === '') {
                    return '';
                }

                return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 18px;"><tr>'
                    . '<td style="background:#16a34a;border-radius:8px;">'
                    . '<a href="' . $e($url) . '" style="display:inline-block;padding:11px 20px;'
                    . 'font-family:Helvetica,Arial,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;'
                    . 'text-decoration:none;">' . $e($label) . '</a></td></tr></table>';

            case 'activities':
                // A placeholder the sender swaps for the real list — it is the
                // only block whose content the template cannot know.
                return '{{activities_list}}';

            case 'divider':
                return '<hr style="border:0;border-top:1px solid #e5e7eb;margin:18px 0;">';

            case 'spacer':
                return '<div style="height:18px;line-height:18px;">&nbsp;</div>';
        }

        return '';
    }

    /** Wrap the body in the shell every one of these emails shares. */
    public static function wrap(string $inner, string $title = ''): string
    {
        return '<!doctype html><html><body style="margin:0;padding:0;background:#f3f4f6;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;padding:26px 24px;">'
            . '<tr><td>' . $inner . '</td></tr></table>'
            . '<p style="margin:14px 0 0;font-family:Helvetica,Arial,sans-serif;font-size:12px;color:#9ca3af;">'
            . e($title) . '</p>'
            . '</td></tr></table></body></html>';
    }
}
