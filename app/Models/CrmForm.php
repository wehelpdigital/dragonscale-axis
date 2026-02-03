<?php

namespace App\Models;

use Illuminate\Support\Str;

class CrmForm extends BaseModel
{
    protected $table = 'crm_forms';

    protected $fillable = [
        'usersId',
        'formName',
        'formSlug',
        'apiKey',
        'apiEnabled',
        'formDescription',
        'formStatus',
        'formSettings',
        'formElements',
        'triggerFlow',
        'submitCount',
        'viewCount',
        'delete_status',
    ];

    protected $casts = [
        'usersId' => 'integer',
        'formSettings' => 'array',
        'formElements' => 'array',
        'triggerFlow' => 'array',
        'submitCount' => 'integer',
        'viewCount' => 'integer',
        'apiEnabled' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($form) {
            if (empty($form->formSlug)) {
                $form->formSlug = static::generateUniqueSlug($form->formName);
            }
        });
    }

    /**
     * Generate a unique slug for the form
     */
    public static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('formSlug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Scope to get only active forms (delete_status = 'active')
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope to get forms for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope to get only published forms
     */
    public function scopePublished($query)
    {
        return $query->where('formStatus', 'active');
    }

    /**
     * Get the user that owns the form
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get the submissions for this form
     */
    public function submissions()
    {
        return $this->hasMany(CrmFormSubmission::class, 'formId');
    }


    /**
     * Get the public URL for the form
     */
    public function getPublicUrlAttribute()
    {
        return url('/f/' . $this->formSlug);
    }

    /**
     * Get the API URL for the form
     */
    public function getApiUrlAttribute()
    {
        return url('/api/forms/' . $this->formSlug . '/submit');
    }

    /**
     * Generate a new API key
     */
    public function generateApiKey()
    {
        $this->apiKey = bin2hex(random_bytes(32));
        $this->save();
        return $this->apiKey;
    }

    /**
     * Get input field elements (excludes layout elements)
     */
    public function getInputElements()
    {
        $skipTypes = ['heading', 'paragraph', 'divider', 'submit_button', 'image', 'video'];
        return collect($this->formElements ?? [])->filter(function ($element) use ($skipTypes) {
            return isset($element['id']) && !in_array($element['type'], $skipTypes);
        })->values()->toArray();
    }

    /**
     * Check if form is active
     */
    public function isActive()
    {
        return $this->formStatus === 'active';
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('viewCount');
    }

    /**
     * Increment submit count
     */
    public function incrementSubmissions()
    {
        $this->increment('submitCount');
    }

    /**
     * Get default form settings
     */
    public static function getDefaultSettings()
    {
        return [
            'successMessage' => 'Thank you for your submission!',
            'redirectUrl' => '',
            'formWidth' => 'medium', // small, medium, large
        ];
    }
}
