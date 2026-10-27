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
            'type' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'string', 'max:50'],
            'category' => ['sometimes', 'string', 'max:180'],
            'tag' => ['sometimes', 'string', 'max:180'],
            'group' => ['sometimes', 'string', 'max:100'],
            'role' => ['sometimes', 'string', 'max:100'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'license_number' => ['sometimes', 'string', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
            'geographical_scope' => ['sometimes', 'string', 'max:100'],
            'discipline_id' => ['sometimes', 'integer', 'min:1'],
            'qualification_id' => ['sometimes', 'integer', 'min:1'],
            'expert_group_id' => ['sometimes', 'integer', 'min:1'],
            'board_term_id' => ['sometimes', 'integer', 'min:1'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'sort' => ['sometimes', Rule::in(['latest', 'oldest', 'title'])],
            'trash' => ['sometimes', 'boolean'],
        ];
    }
}
