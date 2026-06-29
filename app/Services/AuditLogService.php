<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\Company;
use App\Models\Department;
use App\Models\InterviewFeedback;
use App\Models\InterviewSchedule;
use App\Models\JobPosting;
use App\Models\Offer;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AuditLogService
{
    /** @var array<class-string<Model>, array<int, string>> */
    private const SNAPSHOT_FIELDS = [
        Company::class => ['name', 'slug', 'city', 'country', 'is_active'],
        Department::class => ['company_id', 'name', 'slug', 'is_active'],
        JobPosting::class => [
            'company_id',
            'department_id',
            'title',
            'employment_type',
            'workplace_type',
            'openings',
            'status',
            'published_at',
            'closes_at',
        ],
        Candidate::class => [
            'first_name',
            'last_name',
            'source',
            'current_position',
            'availability',
            'status',
        ],
        CandidateResume::class => [
            'candidate_id',
            'original_name',
            'mime_type',
            'size_bytes',
            'extension',
            'is_primary',
        ],
        Application::class => [
            'candidate_id',
            'job_posting_id',
            'source',
            'applied_date',
            'current_status',
        ],
        InterviewSchedule::class => [
            'application_id',
            'interviewer_id',
            'type',
            'status',
            'scheduled_at',
            'duration_minutes',
        ],
        InterviewFeedback::class => [
            'interview_schedule_id',
            'recommendation',
            'rating',
            'submitted_by_id',
            'submitted_at',
        ],
        Offer::class => [
            'application_id',
            'offer_title',
            'currency',
            'employment_type',
            'expected_joining_date',
            'expiry_date',
            'status',
        ],
    ];

    public function __construct(
        private readonly Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function log(
        string $action,
        ?Model $auditable,
        string $summary,
        array $oldValues = [],
        array $newValues = [],
        ?User $actor = null,
    ): AuditLog {
        if (! in_array($action, AuditLog::ACTIONS, true)) {
            throw new InvalidArgumentException("Unsupported audit action [{$action}].");
        }

        $authenticatedUser = Auth::user();
        $actor ??= $authenticatedUser instanceof User ? $authenticatedUser : null;
        $oldValues = $this->sanitizeValues($oldValues);
        $newValues = $this->sanitizeValues($newValues);
        $ipAddress = $this->request->ip();

        return AuditLog::query()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'summary' => Str::limit(Str::squish($summary), 500, ''),
            'old_values' => $oldValues !== [] ? $oldValues : null,
            'new_values' => $newValues !== [] ? $newValues : null,
            'ip_address' => is_string($ipAddress) && filter_var($ipAddress, FILTER_VALIDATE_IP)
                ? $ipAddress
                : null,
            'user_agent' => $this->safeUserAgent(),
        ]);
    }

    public function created(Model $auditable, string $summary): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATED,
            $auditable,
            $summary,
            newValues: $this->snapshot($auditable),
        );
    }

    /**
     * @param  array<string, mixed>  $before
     */
    public function updated(Model $auditable, array $before, string $summary): AuditLog
    {
        [$oldValues, $newValues] = $this->changedValues($before, $this->snapshot($auditable));

        return $this->log(
            AuditLog::ACTION_UPDATED,
            $auditable,
            $summary,
            $oldValues,
            $newValues,
        );
    }

    /**
     * @param  array<string, mixed>|null  $before
     */
    public function deleted(Model $auditable, string $summary, ?array $before = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETED,
            $auditable,
            $summary,
            oldValues: $before ?? $this->snapshot($auditable),
        );
    }

    public function statusChanged(
        Model $auditable,
        string $field,
        string $from,
        string $to,
        string $summary,
    ): AuditLog {
        return $this->log(
            AuditLog::ACTION_STATUS_CHANGED,
            $auditable,
            $summary,
            [$field => $from],
            [$field => $to],
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function uploaded(Model $auditable, string $summary, array $metadata = []): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPLOADED,
            $auditable,
            $summary,
            newValues: $metadata !== [] ? $metadata : $this->snapshot($auditable),
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function downloaded(Model $auditable, string $summary, array $metadata = []): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DOWNLOADED,
            $auditable,
            $summary,
            newValues: $metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Model $model): array
    {
        $fields = self::SNAPSHOT_FIELDS[$model::class] ?? [];
        $attributes = $model->getAttributes();

        return $this->sanitizeValues(array_intersect_key($attributes, array_flip($fields)));
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function sanitizeValues(array $values): array
    {
        $sanitized = [];

        foreach (array_slice($values, 0, 50, true) as $key => $value) {
            $key = (string) $key;

            if ($this->isSensitiveKey($key)) {
                continue;
            }

            $sanitized[$key] = $this->normalizeValue($value, 0);
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function changedValues(array $before, array $after): array
    {
        $oldValues = [];
        $newValues = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            $oldValue = $before[$key] ?? null;
            $newValue = $after[$key] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $oldValues[$key] = $oldValue;
            $newValues[$key] = $newValue;
        }

        return [$oldValues, $newValues];
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = Str::lower(preg_replace('/[^a-z0-9]+/i', '_', $key) ?? $key);

        return preg_match(
            '/(^|_)(password|remember_token|token|secret|api_key|private_key|authorization|cookie|file_content|resume_content|cv_content|stored_path|contents)($|_)/',
            $key,
        ) === 1;
    }

    private function normalizeValue(mixed $value, int $depth): mixed
    {
        if ($depth >= 5) {
            return '[depth limited]';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (is_array($value)) {
            $normalized = [];

            foreach (array_slice($value, 0, 50, true) as $key => $item) {
                if (is_string($key) && $this->isSensitiveKey($key)) {
                    continue;
                }

                $normalized[$key] = $this->normalizeValue($item, $depth + 1);
            }

            return $normalized;
        }

        if (is_string($value)) {
            return Str::limit($value, 2000, '');
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return '[unsupported value]';
    }

    private function safeUserAgent(): ?string
    {
        $userAgent = $this->request->userAgent();

        return is_string($userAgent) && $userAgent !== ''
            ? Str::limit($userAgent, 1000, '')
            : null;
    }
}
