<?php

namespace App\Http\Requests\Interview;

use App\Models\InterviewSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInterviewScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('interviews.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'application_id' => [
                'required',
                'integer',
                Rule::exists('applications', 'id')->whereNull('deleted_at'),
            ],
            'interviewer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
            'type' => ['required', 'string', Rule::in(InterviewSchedule::TYPES)],
            'status' => ['required', 'string', Rule::in(InterviewSchedule::STATUSES)],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizedOptionalFields());
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizedOptionalFields(): array
    {
        return collect(['location', 'meeting_link', 'notes'])
            ->mapWithKeys(fn (string $field): array => [
                $field => $this->filled($field)
                    ? trim((string) $this->input($field))
                    : null,
            ])
            ->all();
    }
}
