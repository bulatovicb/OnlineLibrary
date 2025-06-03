<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBookRequest;
use App\Models\Book;

class BookController extends Controller
{
    /**
     *  Creates new book.
     *
     *  Accessible only by authenticated librarians.
     *  Validates the provided book's data via CreateBookRequest.
     *  Handles image uploads if any and store the image.
     *  Attaches related models and eager load related data before returning response.
     *  Returns a JSON response with created book and its relations.
     *
     * @param CreateBookRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateBookRequest $request)
    {
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
        $book->load(['images', 'authors', 'genres']);

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
