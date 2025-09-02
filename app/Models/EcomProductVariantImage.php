<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductVariantImage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_variants_images';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ecomVariantsId',
        'imageName',
        'imageLink',
        'imageOrder',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'imageOrder' => 'integer',
        'deleteStatus' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the variant that owns the image.
     */
    public function variant()
    {
        return $this->belongsTo(EcomProductVariant::class, 'ecomVariantsId', 'id');
    }

    /**
     * Scope to get only active images (deleteStatus = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Scope to filter by variant ID
     */
    public function scopeByVariant($query, $variantId)
    {
        return $query->where('ecomVariantsId', $variantId);
    }
}
