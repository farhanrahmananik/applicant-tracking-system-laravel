<?php

namespace App\Http\Requests\Offer;

use App\Models\Offer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionOfferStatusRequest extends FormRequest
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
            'to_status' => ['required', 'string', Rule::in(Offer::STATUSES)],
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
