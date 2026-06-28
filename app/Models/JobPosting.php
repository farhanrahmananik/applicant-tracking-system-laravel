<?php

namespace App\Models;

use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory, SoftDeletes;

    public const EMPLOYMENT_TYPES = [
        'full_time',
        'part_time',
        'contract',
        'internship',
        'temporary',
    ];

    public const WORKPLACE_TYPES = [
        'onsite',
        'remote',
        'hybrid',
    ];

    public const STATUSES = [
        'draft',
        'open',
        'paused',
        'closed',
    ];

    protected $fillable = [
        'company_id',
        'department_id',
        'title',
        'slug',
        'employment_type',
        'workplace_type',
        'location',
        'openings',
        'salary_min',
        'salary_max',
        'currency',
        'experience_level',
        'description',
        'requirements',
        'responsibilities',
        'benefits',
        'status',
        'published_at',
        'closes_at',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class)->withTrashed();
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'openings' => 'integer',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'published_at' => 'datetime',
            'closes_at' => 'date',
        ];
    }
}
