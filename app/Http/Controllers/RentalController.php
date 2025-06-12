<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Rental;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RentalController extends Controller
{
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

        if ($book->number_of_copies_available <= 0) {
            return response()->json([
                'error' => "Book is out of stock"
            ]);
        }

        $rental = Rental::create([
            'book_id' => $book->id,
            'student_id' => $request->student_id,
            'librarian_id'=> $request->librarian_id,
            'rented_at' => now(),
            'returned_at' => null,
        ]);

        $book->decrement('number_of_copies_available');

        return response()->json([
            'message' => 'Rental created successfully',
            'rental' => $rental
        ], 201);
    }

    public function show($id)
    {
        $rental = Rental::findOrFail($id);

        return response()->json([
            'days_rented' => $rental->days_rented,
            'is_overdue' => $rental->is_overdue,
            'message' => $rental->is_overdue ? 'This rental is overdue!' : 'Rental period is still valid.'
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
