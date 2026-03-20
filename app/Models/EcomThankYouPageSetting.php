<?php

namespace App\Models;

class EcomThankYouPageSetting extends BaseModel
{
    protected $table = 'ecom_thank_you_page_settings';

    protected $fillable = [
        'usersId',
        // Main Header Section
        'mainHeading',
        'subHeading',
        'subHeadingText',
        // What's Next Section
        'whatsNextTitle',
        'whatsNextSteps',
        // Inspirational Message Section
        'inspirationalEmoji',
        'inspirationalTitle',
        'inspirationalMessage',
        // Bookmark Reminder Section
        'bookmarkTitle',
        'bookmarkMessage',
        // Action Buttons
        'copyLinkButtonText',
        'copyLinkSuccessText',
        'savePhotoButtonText',
        'savingText',
        'homeButtonText',
        // Footer
        'footerText',
        // Status messages
        'statusVerifiedText',
        'statusPendingText',
        // Meta
        'delete_status',
    ];

    protected $casts = [
        'whatsNextSteps' => 'array',
    ];

    /**
     * Scope a query to only include active records.
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Get the user that owns this setting.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get default steps array.
     */
    public static function getDefaultSteps()
    {
        return [
            ['text' => 'I-ve-verify namin ang payment mo <strong>within 24 hours</strong>.'],
            ['text' => 'Makakatanggap ka ng <strong>email confirmation</strong> with login details.'],
            ['text' => 'Simulan mo na ang <strong>pag-aaral</strong> at magsimulang kumita!'],
        ];
    }

    /**
     * Get or create settings for a user.
     * If no settings exist, returns default values.
     */
    public static function getForUser($userId)
    {
        $settings = self::active()->forUser($userId)->first();

        if (!$settings) {
            // Return default settings object (not persisted)
            $settings = new self([
                'usersId' => $userId,
                'mainHeading' => 'Salamat!',
                'subHeading' => 'Congratulations, Magsasaka!',
                'subHeadingText' => '',
                'whatsNextTitle' => 'Ano ang susunod?',
                'whatsNextSteps' => self::getDefaultSteps(),
                'inspirationalEmoji' => '🌾',
                'inspirationalTitle' => 'Ito ang simula ng pagbabago!',
                'inspirationalMessage' => 'Ginawa mo ang pinakamahalagang hakbang para baguhin ang iyong buhay sa pagsasaka. Maligayang pagdating sa komunidad ng mga matagumpay na magsasaka!',
                'bookmarkTitle' => 'I-save ang page na ito!',
                'bookmarkMessage' => 'Puwede mong balikan ang page na ito anytime para ma-check ang status ng order mo.',
                'copyLinkButtonText' => 'Copy Order Link',
                'copyLinkSuccessText' => 'Link Copied!',
                'savePhotoButtonText' => 'I-save bilang Photo',
                'savingText' => 'Saving...',
                'homeButtonText' => 'Bumalik sa Home',
                'footerText' => 'Secured by Ani-Senso Academy',
                'statusVerifiedText' => 'Payment Verified',
                'statusPendingText' => 'Pending Verification',
            ]);
        }

        // Ensure whatsNextSteps is an array
        if (empty($settings->whatsNextSteps)) {
            $settings->whatsNextSteps = self::getDefaultSteps();
        }

        return $settings;
    }
}
