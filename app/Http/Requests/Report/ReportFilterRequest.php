<?php

namespace App\Http\Requests\Report;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\InterviewSchedule;
use App\Models\Offer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.view') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                Rule::when($this->filled('date_from'), 'after_or_equal:date_from'),
            ],
            'company_id' => [
                'nullable',
                'integer',
                Rule::exists('companies', 'id')->whereNull('deleted_at'),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->whereNull('deleted_at'),
            ],
            'job_posting_id' => [
                'nullable',
                'integer',
                Rule::exists('job_postings', 'id')->whereNull('deleted_at'),
            ],
            'application_status' => ['nullable', 'string', Rule::in(Application::STATUSES)],
            'pipeline_stage' => ['nullable', 'string', Rule::in(Application::PIPELINE_STAGES)],
            'candidate_status' => ['nullable', 'string', Rule::in(Candidate::STATUSES)],
            'candidate_source' => ['nullable', 'string', 'max:100'],
            'interview_status' => ['nullable', 'string', Rule::in(InterviewSchedule::STATUSES)],
            'interviewer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'offer_status' => ['nullable', 'string', Rule::in(Offer::STATUSES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['candidate_source'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }
}
