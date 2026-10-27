<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\PublicQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('q'))) {
            $this->merge(['q' => app(PublicQuery::class)->normalize($this->input('q'))]);
        }
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:150'],
            'type' => ['sometimes', Rule::in(['news', 'announcement', 'resolution'])],
            'page' => ['sometimes', 'integer', 'between:1,10000'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ];
    }
}
