<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsQuestionnaireQuestion extends Model
{
    protected $table = 'as_questionnaire_questions';

    protected $fillable = [
        'questionnaireId',
        'questionTitle',
        'questionText',
        'questionPhoto',
        'questionVideo',
        'questionType',
        'questionOrder',
        'deleteStatus'
    ];

    protected $casts = [
        'deleteStatus' => 'boolean',
        'questionOrder' => 'integer'
    ];

    /**
     * Get the questionnaire this question belongs to
     */
    public function questionnaire()
    {
        return $this->belongsTo(AsQuestionnaire::class, 'questionnaireId', 'id');
    }

    /**
     * Get active answers ordered by answerOrder
     */
    public function answers()
    {
        return $this->hasMany(AsQuestionnaireAnswer::class, 'questionId', 'id')
                    ->where('deleteStatus', true)
                    ->orderBy('answerOrder');
    }

    /**
     * Get all answers including deleted
     */
    public function allAnswers()
    {
        return $this->hasMany(AsQuestionnaireAnswer::class, 'questionId', 'id');
    }

    /**
     * Scope for active questions
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', true);
    }

    /**
     * Scope for ordered questions
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('questionOrder');
    }

    /**
     * Check if question type is single choice (radio)
     */
    public function getIsSingleChoiceAttribute()
    {
        return $this->questionType === 'single';
    }

    /**
     * Check if question type is multiple choice (checkbox)
     */
    public function getIsMultipleChoiceAttribute()
    {
        return $this->questionType === 'multiple';
    }

    /**
     * Get correct answers
     */
    public function getCorrectAnswersAttribute()
    {
        return $this->answers()->where('isCorrect', true)->get();
    }

    /**
     * Extract YouTube video ID from URL
     */
    public function getYoutubeIdAttribute()
    {
        if (!$this->questionVideo) return null;

        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i',
            $this->questionVideo, $matches);

        return $matches[1] ?? null;
    }
}
