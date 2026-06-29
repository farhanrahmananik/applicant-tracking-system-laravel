<?php

namespace App\Http\Requests\Offer;

use App\Models\JobPosting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('offers.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'offer_title' => ['required', 'string', 'max:255'],
            'salary_amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'employment_type' => [
                'required',
                'string',
                Rule::in(JobPosting::EMPLOYMENT_TYPES),
            ],
            'expiry_date' => ['required', 'date', 'after_or_equal:today'],
            'expected_joining_date' => ['nullable', 'date', 'after_or_equal:expiry_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'offer_title' => $this->filled('offer_title')
                ? trim((string) $this->input('offer_title'))
                : null,
            'currency' => $this->filled('currency')
                ? Str::upper(trim((string) $this->input('currency')))
                : null,
            'notes' => $this->filled('notes')
                ? trim((string) $this->input('notes'))
                : null,
        ]);
    }
}
