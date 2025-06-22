<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\DiscardedBook;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class DiscardBookController extends Controller
{
    /**
     *Discards a book.
     *
     * Accessible only by authenticated librarians.
     * Validates the existence of the book and the librarian.
     * Records the discard event with timestamp and librarian ID for audit purposes.
     * Decrements the available copies count of the book.
     * If no copies remain after discarding, marks the book as inactive.
     * Returns validation errors if the book cannot be discarded or does not exist.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function discard(Request $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'error' => 'This book cannot be discarded as it does not exist in the inventory.'
            ], 422);
        }

        $request->validate([
            'librarian_id' => 'required|exists:users,id',
        ]);

        $librarian = User::find($request->librarian_id);

        if ($librarian->role_id !== Role::LIBRARIAN) {
            return response()->json([
                'error' => 'Only librarians can discard books.'
            ], 403);
        }

        if ($book->number_of_copies_available <= 0) {
            $book->update([
                'is_active' => false,
            ]);
            return response()->json([
                'error' => 'This book have no copies to discard.'
            ], 422);
        }

        $discardedBook = DiscardedBook::create([
            'book_id' => $book->id,
            'librarian_id' => $librarian->id,
            'discarded_at' => now()]);

        $book->decrement('number_of_copies_available');

        return response()->json([
            'message' => 'Book discarded successfully.',
            'discarded_at' => $discardedBook->discarded_at,
            'librarian' => $librarian->id,
        ]);
    }
}
