<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsCertificateTemplate extends Model
{
    protected $table = 'as_course_certificates';

    protected $fillable = [
        'asCoursesId',
        'certificateName',
        'paperSize',
        'orientation',
        'templateData',
        'backgroundColor',
        'backgroundImage',
        'isActive',
        'deleteStatus'
    ];

    protected $casts = [
        'asCoursesId' => 'integer',
        'templateData' => 'array', // Auto JSON encode/decode
        'isActive' => 'boolean',
        'deleteStatus' => 'integer'
    ];

    /**
     * Paper size constants (in pixels at 96 DPI for screen, scaled for print)
     */
    const PAPER_LETTER = 'letter'; // 8.5 x 11 inches
    const PAPER_A4 = 'a4';         // 8.27 x 11.69 inches

    const ORIENTATION_LANDSCAPE = 'landscape';
    const ORIENTATION_PORTRAIT = 'portrait';

    /**
     * Get paper dimensions in pixels (at 96 DPI for screen display)
     * Actual PDF export will use 300 DPI for print quality
     */
    public static function getPaperDimensions($paperSize, $orientation)
    {
        // Base dimensions at 96 DPI (screen)
        $dimensions = [
            'letter' => ['width' => 816, 'height' => 1056],  // 8.5" x 11"
            'a4' => ['width' => 794, 'height' => 1123],      // 8.27" x 11.69"
        ];

        $size = $dimensions[$paperSize] ?? $dimensions['letter'];

        if ($orientation === self::ORIENTATION_LANDSCAPE) {
            return [
                'width' => $size['height'],
                'height' => $size['width']
            ];
        }

        return $size;
    }

    /**
     * Available placeholder fields for dynamic data
     */
    public static function getPlaceholders()
    {
        return [
            '{{student_name}}' => 'Student\'s full name',
            '{{course_name}}' => 'Course title',
            '{{completion_date}}' => 'Date of completion',
            '{{certificate_id}}' => 'Unique certificate ID',
            '{{instructor_name}}' => 'Instructor/Admin name',
            '{{current_date}}' => 'Current date'
        ];
    }

    /**
     * Relationships
     */
    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    public function assets()
    {
        return $this->hasMany(AsCertificateAsset::class, 'asCoursesId', 'asCoursesId')
                    ->where('deleteStatus', 1);
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

    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Get or create certificate template for a course
     */
    public static function getOrCreate($courseId)
    {
        $template = self::active()->forCourse($courseId)->first();

        if (!$template) {
            $template = self::create([
                'asCoursesId' => $courseId,
                'certificateName' => 'Certificate of Completion',
                'paperSize' => self::PAPER_LETTER,
                'orientation' => self::ORIENTATION_LANDSCAPE,
                'templateData' => self::getDefaultTemplate(),
                'backgroundColor' => '#ffffff',
                'isActive' => false,
                'deleteStatus' => 1
            ]);
        }

        return $template;
    }

    /**
     * Get default template structure with sample elements
     */
    public static function getDefaultTemplate()
    {
        return [
            'version' => '1.0',
            'objects' => []
        ];
    }

    /**
     * Get canvas dimensions for current settings
     */
    public function getCanvasDimensions()
    {
        return self::getPaperDimensions($this->paperSize, $this->orientation);
    }

    /**
     * Replace placeholders with actual data
     */
    public function replacePlaceholders($text, $data)
    {
        $replacements = [
            '{{student_name}}' => $data['student_name'] ?? 'Student Name',
            '{{course_name}}' => $data['course_name'] ?? 'Course Name',
            '{{completion_date}}' => $data['completion_date'] ?? date('F j, Y'),
            '{{certificate_id}}' => $data['certificate_id'] ?? 'CERT-' . strtoupper(uniqid()),
            '{{instructor_name}}' => $data['instructor_name'] ?? 'Instructor',
            '{{current_date}}' => date('F j, Y')
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
