<?php

namespace App\Http\Controllers;

use App\Events\BookDeleting;
use App\Models\Book;

class BookController extends Controller
{
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
        event(new BookDeleting($book));
        $book->delete();
        return response()->json([
            'message' => 'Book deleted successfully.',
        ]);
    }
}
