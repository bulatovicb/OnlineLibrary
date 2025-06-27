<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Rental;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RentalController extends Controller
{
     /**
     * Store a newly created rental in storage.
     *
     * Validates the request to ensure the book, student, and librarian exist.
     * Checks if the book is available for rent.
     * Ensures that the student and librarian have the correct roles.
     * Decrements the available book copies upon successful rental creation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'student_id' => 'required|exists:users,id',
            'librarian_id' => 'required|exists:users,id',
        ]);

        $book = Book::findOrFail($request->book_id);

        try {
            $this->validateUserRoles($request->student_id, $request->librarian_id);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], 422);
        }

        if ($book->number_of_copies_available == 0) {
            return response()->json([
                'error' => "All copies of the book $book->id have been rented out"
            ], 422);
        }

        $rental = Rental::create([
            'book_id' => $book->id,
            'student_id' => $request->student_id,
            'librarian_id' => $request->librarian_id,
            'rented_at' => now(),
            'returned_at' => null,
        ]);

        $book->decrement('number_of_copies_available');

        return response()->json([
            'message' => 'Rental created successfully',
            'rental' => $rental
        ], 201);
    }

    public function validateUserRoles(int $studentId, int $librarianId)
    {
        $student = User::findOrFail($studentId);
        $librarian = User::findOrFail($librarianId);

        if ($student->role_id !== Role::STUDENT) {
            throw new \Exception("Selected user is not a student");
        }

        if ($librarian->role_id !== Role::LIBRARIAN) {
            throw new \Exception("Selected user is not a librarian");
        }
    }

    /**
     * Display the specified rental details.
     *
     * Returns:
     * - number of days the book has been rented,
     * - whether the rental is overdue,
     * - a message indicating rental status.
     *
     * @param Rental $rental
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Rental $rental)
    {
        return response()->json([
            'days_rented' => $rental->days_rented,
            'is_overdue' => $rental->is_overdue,
            'message' => $rental->is_overdue ? 'This rental is overdue!' : 'Rental period is still valid.'
        ]);
    }
  
    /**
     * Handles the return of a rented book.
     *
     * Validates the existence of the rental record.
     * Ensures that the provided librarian ID exists and holds the correct role.
     * Checks if the book has already been returned.
     * If all conditions are met, the book is marked as returned, the number of available copies is incremented,
     * and any overdue days are calculated.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnBook(Request $request, $id)
    {
        $rental = Rental::find($id);

        if (!$rental) {
            return response()->json([
                'error' => 'This book was not rented out, hence cannot be returned.'
            ], 422);
        }

        $request->validate([
            'librarian_id' => 'required|exists:users,id',
        ]);

        try {
            $this->validateUserRoles($request->librarian_id);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], 422);
        }

        if ($rental->returned_at !== null) {
            return response()->json([
                'error' => 'Already returned',
            ], 422);
        }

        $book = $rental->book;

        $rental->update([
            'returned_at' => now(),
            'librarian_id' => $request->librarian_id
        ]);

        $book->increment('number_of_copies_available');

        $overdue = max(0, $rental->days_rented - $rental->rental_period);

        if ($overdue > 0) {
            Log::debug("Book ID {$book->id} returned with {$overdue} overdue days by student ID {$rental->student_id}.");
        }

        return response()->json([
            'message' => 'Book returned',
            'overdue_days' => $overdue,
        ]);
    }
}
