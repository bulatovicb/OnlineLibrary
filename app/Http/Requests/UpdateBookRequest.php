<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
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
        $bookId = $this->route('book')?->id;

        return [
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'number_of_pages' => 'sometimes|integer',
            'number_of_copies_available' => 'sometimes|integer',
            'isbn' => [
                'sometimes',
                'string',
                Rule::unique('books', 'isbn')->ignore($bookId),
            ],
            'language' => 'sometimes|string',
            'script' => ['nullable', Rule::in(Book::SCRIPTS)],
            'binding' => ['nullable', Rule::in(Book::BINDINGS)],
            'dimensions' => ['nullable', Rule::in(Book::DIMENSIONS)],
        ];
    }
}
