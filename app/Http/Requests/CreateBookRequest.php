<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBookRequest extends FormRequest
{
    public const TYPES = ['front_cover', 'back_cover', 'artwork'];

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
    public static function rules(): array
    {
        $scriptTranslations = array_values(trans('book.scripts'));
        $bindingTranslations = array_values(trans('book.bindings'));

        return [
            'name' => 'required',
            'description' => 'required',
            'number_of_pages' => 'required|integer|min:1',
            'number_of_copies_available' => 'required|integer',
            'isbn' => 'required|unique:books,isbn',
            'language' => 'nullable',
            'script' => ['nullable', Rule::in($scriptTranslations)],
            'binding' => ['nullable', Rule::in($bindingTranslations)],
            'dimensions' => ['nullable', Rule::in(Book::DIMENSIONS)],
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
            'authors' => 'required|array',
            'authors.*' => 'exists:authors,id',
            'publisher_id' => 'required|exists:publishers,id',

            'images' => 'nullable|array',
            'images.*' => 'file|image|max:5120',
            'image_types' => 'nullable|array',
            'image_types.*' => ['required', Rule::in(self::TYPES)],
        ];
    }
}
