<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_STATUS_CHANGED = 'status_changed';

    public const ACTION_UPLOADED = 'uploaded';

    public const ACTION_DOWNLOADED = 'downloaded';

    public const ACTIONS = [
        self::ACTION_CREATED,
        self::ACTION_UPDATED,
        self::ACTION_DELETED,
        self::ACTION_STATUS_CHANGED,
        self::ACTION_UPLOADED,
        self::ACTION_DOWNLOADED,
    ];

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'action',
        'auditable_type',
        'auditable_id',
        'summary',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getEntityTypeAttribute(): string
    {
        return $this->auditable_type !== null
            ? class_basename($this->auditable_type)
            : 'System';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
