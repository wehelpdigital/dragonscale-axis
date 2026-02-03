<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsQuestionnaire extends Model
{
    protected $table = 'as_questionnaires';

    protected $fillable = [
        'asCoursesId',
        'title',
        'description',
        'itemOrder',
        'deleteStatus'
    ];

    protected $casts = [
        'deleteStatus' => 'boolean',
        'itemOrder' => 'integer'
    ];

    /**
     * Get the course this questionnaire belongs to
     */
    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    /**
     * Get active questions ordered by questionOrder
     */
    public function questions()
    {
        return $this->hasMany(AsQuestionnaireQuestion::class, 'questionnaireId', 'id')
                    ->where('deleteStatus', true)
                    ->orderBy('questionOrder');
    }

    /**
     * Get all questions including deleted
     */
    public function allQuestions()
    {
        return $this->hasMany(AsQuestionnaireQuestion::class, 'questionnaireId', 'id');
    }

    /**
     * Scope for active questionnaires
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', true);
    }

    /**
     * Scope for ordered questionnaires
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('itemOrder');
    }

    /**
     * Get question count
     */
    public function getQuestionCountAttribute()
    {
        return $this->questions()->count();
    }
}
