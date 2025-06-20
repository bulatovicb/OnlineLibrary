<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RentalController extends Controller
{
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
            $this->validateUserRoles($rental->student_id, $request->librarian_id);
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

        return response()->json([
            'message' => 'Book returned',
            'overdue_days' => $overdue,
        ]);
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


}
