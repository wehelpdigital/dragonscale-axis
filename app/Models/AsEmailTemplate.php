<?php

namespace App\Models;

class AsEmailTemplate extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'as_email_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'groupKey',
        'templateKey',
        'templateName',
        'subject',
        'bodyHtml',
        'blocks',
        'availableTags',
        'isActive',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'isActive' => 'boolean',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
            // The editable form behind bodyHtml (see the email builder).
        'blocks' => 'array',
];

    /**
     * Scope to get only active rows (deleteStatus = 1).
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope by group key ('AniSystem' | 'AniSenso').
     */
    public function scopeForGroup($query, string $groupKey)
    {
        return $query->where('groupKey', $groupKey);
    }

    /**
     * Replace {{tag}} placeholders (with or without inner spaces) in the
     * given text using the supplied tag => value map.
     */
    public static function renderTags(?string $text, array $tags): string
    {
        $text = (string) $text;
        $replacements = [];
        foreach ($tags as $key => $value) {
            $replacements['{{' . $key . '}}'] = (string) $value;
            $replacements['{{ ' . $key . ' }}'] = (string) $value;
        }
        return strtr($text, $replacements);
    }
}
