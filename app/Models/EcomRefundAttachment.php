<?php

namespace App\Models;

class EcomRefundAttachment extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_refund_attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'refundRequestId',
        'fileName',
        'filePath',
        'fileType',
        'mimeType',
        'fileSize',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'refundRequestId' => 'integer',
        'fileSize' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active attachments.
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the refund request that this attachment belongs to.
     */
    public function refundRequest()
    {
        return $this->belongsTo(EcomRefundRequest::class, 'refundRequestId');
    }

    /**
     * Get the full URL for the attachment.
     */
    public function getUrlAttribute()
    {
        return asset($this->filePath);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->fileSize;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Check if this is an image.
     */
    public function getIsImageAttribute()
    {
        return $this->fileType === 'image';
    }

    /**
     * Check if this is a video.
     */
    public function getIsVideoAttribute()
    {
        return $this->fileType === 'video';
    }

    /**
     * Get file extension from filename.
     */
    public function getExtensionAttribute()
    {
        return pathinfo($this->fileName, PATHINFO_EXTENSION);
    }
}
