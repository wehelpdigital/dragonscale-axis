<?php

namespace App\Models;

class AsTestimonial extends BaseModel
{
    protected $table = 'as_testimonials';

    protected $fillable = [
        'usersId',
        'name',
        'location',
        'role',
        'testimonial',
        'rating',
        'image',
        'isActive',
        'testimonialOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'isActive' => 'boolean',
        'rating' => 'integer',
        'testimonialOrder' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 'active');
    }

    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }
}
