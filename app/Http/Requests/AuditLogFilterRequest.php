<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->routeIs('audit-logs.export')
            ? 'export-audit-logs'
            : 'view-audit-logs';

        return $this->user()?->can($permission) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                Rule::when($this->filled('date_from'), 'after_or_equal:date_from'),
            ],
            'actor_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'action' => ['nullable', 'string', 'max:50'],
            'auditable_type' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['search', 'action', 'auditable_type'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }
}
