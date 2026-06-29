<?php

namespace App\Http\Requests\Pipeline;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionApplicationStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pipeline.manage') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'to_stage' => ['required', 'string', Rule::in(Application::PIPELINE_STAGES)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => $this->filled('note')
                ? trim((string) $this->input('note'))
                : null,
        ]);
    }
}
