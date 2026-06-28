<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'applied',
        'screening',
        'shortlisted',
        'rejected',
        'withdrawn',
    ];

    public const ACTIVE_STATUSES = [
        'applied',
        'screening',
        'shortlisted',
    ];

    public const TERMINAL_STATUSES = [
        'rejected',
        'withdrawn',
    ];

    protected $fillable = [
        'candidate_id',
        'job_posting_id',
        'source',
        'applied_date',
        'current_status',
        'notes',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * @return BelongsTo<Candidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class)->withTrashed();
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class)->withTrashed();
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

    public function isActive(): bool
    {
        return in_array($this->current_status, self::ACTIVE_STATUSES, true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->current_status, self::TERMINAL_STATUSES, true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applied_date' => 'date',
        ];
    }
}
