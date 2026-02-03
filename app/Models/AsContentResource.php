<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsContentResource extends Model
{
    protected $table = 'as_content_resources';

    protected $fillable = [
        'contentId',
        'fileName',
        'fileUrl',
        'resourceOrder',
        'deleteStatus'
    ];

    protected $casts = [
        'deleteStatus' => 'boolean',
        'resourceOrder' => 'integer'
    ];

    /**
     * Get the content that owns this resource
     */
    public function content()
    {
        return $this->belongsTo(AsTopicContent::class, 'contentId', 'id');
    }

    /**
     * Scope for active resources
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', true);
    }

    /**
     * Scope for ordered resources
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('resourceOrder');
    }

    /**
     * Get the file extension
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->fileUrl, PATHINFO_EXTENSION);
    }

    /**
     * Get file icon based on extension
     */
    public function getFileIconAttribute()
    {
        $extension = strtolower($this->file_extension);

        $icons = [
            'pdf' => 'bx bxs-file-pdf text-danger',
            'doc' => 'bx bxs-file-doc text-primary',
            'docx' => 'bx bxs-file-doc text-primary',
            'xls' => 'bx bxs-file text-success',
            'xlsx' => 'bx bxs-file text-success',
            'ppt' => 'bx bxs-file text-warning',
            'pptx' => 'bx bxs-file text-warning',
            'zip' => 'bx bxs-file-archive text-secondary',
            'rar' => 'bx bxs-file-archive text-secondary',
        ];

        return $icons[$extension] ?? 'bx bxs-file text-muted';
    }
}
