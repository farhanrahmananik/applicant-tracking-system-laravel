<?php

namespace App\Http\Requests\InterviewFeedback;

use App\Models\InterviewFeedback;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInterviewFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('interview-feedback.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:2000'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'weaknesses' => ['nullable', 'string', 'max:5000'],
            'recommendation' => [
                'required',
                'string',
                Rule::in(InterviewFeedback::RECOMMENDATIONS),
            ],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizedFields());
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizedFields(): array
    {
        return collect(['summary', 'strengths', 'weaknesses'])
            ->mapWithKeys(fn (string $field): array => [
                $field => $this->filled($field)
                    ? trim((string) $this->input($field))
                    : null,
            ])
            ->all();
    }
}
