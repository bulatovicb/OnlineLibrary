<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    /**
     * Creates new books.
     *
     * Accessible only by authenticated librarians.
     * Checks if the user is authorised.
     * Validates the provided data and creates a new book if validation passes.
     * Returns a JSON response with the book data and a success message.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        if (!Auth::check() || !Auth::user()->isLibrarian()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'description' => 'required',
            'number_of_pages' => 'required',
            'number_of_copies_available' => 'required',
            'isbn' => 'required',
            'language' => 'required',
            'script' => ['required', Rule::in(Book::SCRIPTS)],
            'binding' => ['required', Rule::in(Book::BINDINGS)],
            'dimensions' => ['required', Rule::in(Book::DIMENSIONS)],
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
            'authors' => 'required|array',
            'authors.*' => 'exists:authors,id',
            'publishers' => 'nullable|array',
            'publishers.*' => 'exists:publishers,id',
            'images' => 'nullable|array',
            'images.*.path' => 'required|string',
            'images.*.type' => ['required', Rule::in(['front_cover', 'back_cover', 'artwork'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $book = Book::create($request->only([
            'name',
            'description',
            'number_of_pages',
            'number_of_copies_available',
            'isbn',
            'language',
            'script',
            'binding',
            'dimensions'
        ]));

        $book->categories()->attach($request->categories);
        $book->genres()->attach($request->genres);
        $book->authors()->attach($request->authors);
        $book->publishers()->attach($request->publishers);

        if ($request->has('images')) {
            foreach ($request->images as $image) {
                $book->images()->create([
                    'path' => $image['path'],
                    'type' => $image['type'],
                ]);
            }
        }


        return response()->json([
            'message' => 'Book created successfully',
            'book' => $book,
            'authors' => $book->authors,
        ], 201);

    }

    /**
     * Displays book's data based on provided id.
     *
     * Accessible only by authenticated librarians.
     * Returns a JSON response with book data.
     * Automatically returns 404 if the author is not found.
     * 
     * @param Book $book
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Book $book)
    {
        return response()->json(['book' => $book], 200);
    }
}
