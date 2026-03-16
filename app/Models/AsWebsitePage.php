<?php

namespace App\Models;

class AsWebsitePage extends BaseModel
{
    protected $table = 'as_website_pages';

    protected $fillable = [
        'pageName',
        'pageSlug',
        'pageIcon',
        'pageContent',
        'metaTitle',
        'metaDescription',
        'metaKeywords',
        'pageStatus',
        'pageOrder',
        'isSystemPage',
        'deleteStatus'
    ];

    protected $casts = [
        'isSystemPage' => 'boolean',
        'pageOrder' => 'integer'
    ];

    /**
     * Scope for active (non-deleted) pages
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 'active');
    }

    /**
     * Scope for published pages
     */
    public function scopePublished($query)
    {
        return $query->where('pageStatus', 'published');
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->pageStatus) {
            'published' => 'bg-success',
            'draft' => 'bg-warning text-dark',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->pageStatus) {
            'published' => 'Published',
            'draft' => 'Draft',
            default => ucfirst($this->pageStatus)
        };
    }
}
