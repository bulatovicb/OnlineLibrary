<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Rental;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

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
                'error' => "You can’t rent out books if there are none in the library as they were all rented out"
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
}
