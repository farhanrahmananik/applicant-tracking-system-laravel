<?php

namespace App\Models;

use Database\Factories\CandidateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    /** @use HasFactory<CandidateFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'new',
        'active',
        'inactive',
        'archived',
    ];

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'location',
        'source',
        'experience_years',
        'skills',
        'current_position',
        'expected_salary',
        'availability',
        'status',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CandidateResume, $this>
     */
    public function resumes(): HasMany
    {
        return $this->hasMany(CandidateResume::class)
            ->orderByDesc('is_primary')
            ->orderByDesc('uploaded_at');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.($this->last_name ?? ''));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'experience_years' => 'decimal:1',
            'expected_salary' => 'decimal:2',
        ];
    }
}
