<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RgContentBlock extends Model
{
    protected $table = 'rg_content_blocks';

    protected $fillable = [
        'owner_type', 'owner_id', 'sort_order', 'block_type', 'payload_json',
    ];

    public const ALLOWED_TYPES = [
        'heading', 'rich_text', 'image', 'gallery', 'video', 'faq',
        'cta', 'two_column', 'listing_slot', 'quote', 'divider', 'custom_html',
    ];

    public function payload(): array
    {
        return $this->payload_json ? (json_decode($this->payload_json, true) ?: []) : [];
    }
}
