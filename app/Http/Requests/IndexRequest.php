<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
            'q' => ['sometimes', 'nullable', 'string', 'max:150'],
            'type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'category' => ['sometimes', 'nullable', 'string', 'max:180'],
            'tag' => ['sometimes', 'nullable', 'string', 'max:180'],
            'group' => ['sometimes', 'nullable', 'string', 'max:100'],
            'role' => ['sometimes', 'nullable', 'string', 'max:100'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'license_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'geographical_scope' => ['sometimes', 'nullable', 'string', 'max:100'],
            'discipline_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'qualification_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'expert_group_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'board_term_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'sort' => ['sometimes', 'nullable', Rule::in(['latest', 'oldest', 'title'])],
            'trash' => ['sometimes', 'boolean'],
        ];
    }
}
