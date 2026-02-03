<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsQuestionnaireAnswer extends Model
{
    protected $table = 'as_questionnaire_answers';

    protected $fillable = [
        'questionId',
        'answerText',
        'isCorrect',
        'answerOrder',
        'deleteStatus'
    ];

    protected $casts = [
        'isCorrect' => 'boolean',
        'deleteStatus' => 'boolean',
        'answerOrder' => 'integer'
    ];

    /**
     * Get the question this answer belongs to
     */
    public function question()
    {
        return $this->belongsTo(AsQuestionnaireQuestion::class, 'questionId', 'id');
    }

    /**
     * Scope for active answers
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', true);
    }

    /**
     * Scope for ordered answers
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('answerOrder');
    }

    /**
     * Scope for correct answers
     */
    public function scopeCorrect($query)
    {
        return $query->where('isCorrect', true);
    }
}
