<?php

namespace App\Models;

use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'draft',
        'sent',
        'accepted',
        'declined',
        'withdrawn',
        'expired',
    ];

    public const ACTIVE_STATUSES = [
        'draft',
        'sent',
    ];

    public const BLOCKING_STATUSES = [
        'draft',
        'sent',
        'accepted',
    ];

    public const TERMINAL_STATUSES = [
        'accepted',
        'declined',
        'withdrawn',
        'expired',
    ];

    protected $fillable = [
        'application_id',
        'offer_title',
        'salary_amount',
        'currency',
        'employment_type',
        'expected_joining_date',
        'expiry_date',
        'status',
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
     * @return HasMany<OfferStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OfferStatusHistory::class)
            ->latest('changed_at')
            ->latest('id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salary_amount' => 'decimal:2',
            'expected_joining_date' => 'date',
            'expiry_date' => 'date',
        ];
    }
}
