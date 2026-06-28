<?php

namespace App\Http\Requests\Application;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('applications.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'candidate_id' => [
                'required',
                'integer',
                Rule::exists('candidates', 'id')->whereNull('deleted_at'),
            ],
            'job_posting_id' => [
                'required',
                'integer',
                Rule::exists('job_postings', 'id')->whereNull('deleted_at'),
            ],
            'source' => ['nullable', 'string', 'max:100'],
            'applied_date' => ['required', 'date', 'before_or_equal:today'],
            'current_status' => ['required', 'string', Rule::in(Application::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source' => $this->filled('source')
                ? trim((string) $this->input('source'))
                : null,
            'notes' => $this->filled('notes')
                ? trim((string) $this->input('notes'))
                : null,
        ]);
    }
}
