<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MergeListsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'listA' => ['nullable', 'string', 'required_without:listB'],
            'listB' => ['nullable', 'string', 'required_without:listA'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'listA' => $this->normalizeInput($this->input('listA')),
            'listB' => $this->normalizeInput($this->input('listB')),
        ]);
    }

    private function normalizeInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
