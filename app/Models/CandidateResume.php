<?php

namespace App\Models;

use Database\Factories\CandidateResumeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateResume extends Model
{
    /** @use HasFactory<CandidateResumeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'uploaded_by_id',
        'original_name',
        'stored_path',
        'disk',
        'mime_type',
        'size_bytes',
        'extension',
        'is_primary',
        'uploaded_at',
    ];

    /**
     * @return BelongsTo<Candidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }
}
