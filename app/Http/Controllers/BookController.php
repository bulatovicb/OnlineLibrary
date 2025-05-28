<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    /**
     * Creates new book.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided book's data and creates a new book if validation passes.
     * Handles image uploads if any and store the image.
     * Attach related models and eager load related data before returning response.
     * Returns a JSON response with created book and its relations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'description' => 'required',
            'number_of_pages' => 'required',
            'number_of_copies_available' => 'required',
            'isbn' => 'required',
            'language' => 'nullable',
            'script' => ['nullable', Rule::in(Book::SCRIPTS)],
            'binding' => ['nullable', Rule::in(Book::BINDINGS)],
            'dimensions' => ['nullable', Rule::in(Book::DIMENSIONS)],
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
            'authors' => 'required|array',
            'authors.*' => 'exists:authors,id',
            'publishers' => 'nullable|array',
            'publishers.*' => 'exists:publishers,id',
        ], Image::validationRules());

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

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $imageTypes = $request->input('image_types', []);

            foreach ($images as $index => $image) {

                $path = $image->store('book_images', 'public');
                $type = $imageTypes[$index] ?? 'artwork';
                $book->images()->create([
                    'path' => $path,
                    'type' => $type,
                ]);
            }
        }

        $book->categories()->attach($request->categories);
        $book->genres()->attach($request->genres);
        $book->authors()->attach($request->authors);
        $book->publishers()->attach($request->publishers);
        $book->load(['images', 'authors']);

        return response()->json([
            'message' => 'Book created successfully',
            'book' => $book,
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

    /**
     * Displays front cover of the book.
     *
     * Accessible only by authenticated librarians.
     * Returns JSON error response if the front cover picture is not found.
     * Otherwise, returns the image path.
     *
     * @param Book $book
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function bookPicture(Book $book)
    {

        $frontCover = $book->images->firstWhere('type', 'front_cover');

        if (!$frontCover) {
            return response()->json(['error' => 'Picture not found'], 404);
        }

        return response()->json([
            'picture_url' => $frontCover->path
        ]);


    }
    
    /**
     * Deletes a book.
     *
     * Dispatch an event before deleting the book to delete all image files from storage related with book.
     * Deletes a book and all of its images.
     * Returns JSON response with success message.
     *
     * @param Book $book
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Book $book)
    {
        $book->images()->each(function ($image) {
            $image->delete();
        });

        $book->delete();
        return response()->json([
            'message' => 'Book deleted successfully.',
        ]);
    }
}
