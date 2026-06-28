<?php

namespace App\Models;

use Database\Factories\InterviewFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewFeedback extends Model
{
    /** @use HasFactory<InterviewFeedbackFactory> */
    use HasFactory;

    public const RECOMMENDATIONS = [
        'strong_hire',
        'hire',
        'maybe',
        'no_hire',
    ];

    protected $table = 'interview_feedback';

    protected $fillable = [
        'interview_schedule_id',
        'summary',
        'strengths',
        'weaknesses',
        'recommendation',
        'rating',
        'submitted_by_id',
        'submitted_at',
    ];

    /**
     * @return BelongsTo<InterviewSchedule, $this>
     */
    public function interviewSchedule(): BelongsTo
    {
        return $this->belongsTo(InterviewSchedule::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }
}
