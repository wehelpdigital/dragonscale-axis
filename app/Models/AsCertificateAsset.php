<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsCertificateAsset extends Model
{
    protected $table = 'as_certificate_assets';

    protected $fillable = [
        'asCoursesId',
        'assetName',
        'assetPath',
        'assetType',
        'fileSize',
        'deleteStatus'
    ];

    protected $casts = [
        'asCoursesId' => 'integer',
        'fileSize' => 'integer',
        'deleteStatus' => 'integer'
    ];

    /**
     * Asset types
     */
    const TYPE_IMAGE = 'image';
    const TYPE_ICON = 'icon';
    const TYPE_SIGNATURE = 'signature';
    const TYPE_LOGO = 'logo';

    /**
     * Relationships
     */
    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('asCoursesId', $courseId);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('asCoursesId');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('assetType', $type);
    }

    /**
     * Get full URL for the asset
     */
    public function getUrlAttribute()
    {
        return asset($this->assetPath);
    }
}
