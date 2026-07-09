<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RgAuthor extends Model
{
    protected $table = 'rg_authors';
    protected $guarded = ['id'];

    public function avatarUrl(): string
    {
        if (empty($this->avatar_path)) {
            return 'https://api.dicebear.com/7.x/notionists/svg?seed=' . urlencode($this->name);
        }
        if (preg_match('#^https?://#i', $this->avatar_path)) {
            return $this->avatar_path;
        }
        // mother app does not host rg-media files; route via frontend URL
        $base = rtrim(config('services.resort_guru.frontend_url') ?? '', '/');
        return $base . '/storage/' . ltrim($this->avatar_path, '/');
    }
}
