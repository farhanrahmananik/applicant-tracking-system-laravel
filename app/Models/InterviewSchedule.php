<?php

namespace App\Models;

use Database\Factories\InterviewScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterviewSchedule extends Model
{
    /** @use HasFactory<InterviewScheduleFactory> */
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'phone',
        'video',
        'onsite',
        'technical',
        'hr',
    ];

    public const STATUSES = [
        'scheduled',
        'rescheduled',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'application_id',
        'interviewer_id',
        'type',
        'status',
        'scheduled_at',
        'duration_minutes',
        'location',
        'meeting_link',
        'notes',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }
}
