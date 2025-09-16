<?php

namespace App\Http\Requests\Genre;

use Illuminate\Foundation\Http\FormRequest;

class IndexGenreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'integer|nullable|in:20,50,100',
            'search_value' => 'string|nullable',
        ];
    }
}
