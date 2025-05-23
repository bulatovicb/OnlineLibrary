<?php

namespace App\Http\Controllers;


use App\Models\Book;


class BookController extends Controller
{
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
}
