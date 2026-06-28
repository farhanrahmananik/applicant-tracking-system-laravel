<?php

namespace App\Http\Requests\JobPosting;

use App\Models\JobPosting;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('job-postings.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->whereNull('deleted_at'),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('company_id', $this->integer('company_id'))
                        ->whereNull('deleted_at'),
                ),
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('job_postings', 'slug')->where(
                    fn (Builder $query): Builder => $query->where('company_id', $this->integer('company_id')),
                ),
            ],
            'employment_type' => ['required', 'string', Rule::in(JobPosting::EMPLOYMENT_TYPES)],
            'workplace_type' => ['required', 'string', Rule::in(JobPosting::WORKPLACE_TYPES)],
            'location' => ['nullable', 'string', 'max:255'],
            'openings' => ['required', 'integer', 'min:1'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($this->filled('salary_min'), ['gte:salary_min']),
            ],
            'currency' => ['nullable', 'string', 'max:3'],
            'experience_level' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(JobPosting::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->filled('slug')) {
            $data['slug'] = Str::slug((string) $this->input('slug'));
        }

        if ($this->filled('currency')) {
            $data['currency'] = Str::upper(trim((string) $this->input('currency')));
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }
}
