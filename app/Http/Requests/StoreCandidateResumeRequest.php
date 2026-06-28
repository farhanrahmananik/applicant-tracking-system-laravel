<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('candidate-resumes.upload') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'resume' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:10240',
            ],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resume.required' => 'Select a resume or CV to upload.',
            'resume.file' => 'The selected resume must be a valid file.',
            'resume.mimes' => 'Resume files must be PDF, DOC, or DOCX.',
            'resume.max' => 'Resume files may not be larger than 10 MB.',
        ];
    }
}
