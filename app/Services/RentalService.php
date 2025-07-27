<?php

namespace App\Services;

use App\Models\Book;
use App\Models\DiscardedBook;
use App\Models\Policy;
use App\Models\Rental;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RentalService
{
    public function createRental(array $data): Rental
    {
        $book = Book::findOrFail($data['book_id']);

        $this->validateUserRoles($data['student_id'], $data['librarian_id']);

        if ($book->number_of_copies_available === 0) {
            throw ValidationException::withMessages([
                'book_id' => ["All copies of the book {$book->id} have been rented out."]
            ]);
        }

        $rental = Rental::create([
            'book_id' => $book->id,
            'student_id' => $data['student_id'],
            'librarian_id' => $data['librarian_id'],
            'rented_at' => now(),
            'returned_at' => null,
        ]);

        $book->decrement('number_of_copies_available');

        return $rental;
    }

    protected function validateUserRoles(int $studentId, int $librarianId): void
    {
        $student = User::findOrFail($studentId);
        $librarian = User::findOrFail($librarianId);

        if ($student->role_id !== Role::STUDENT) {
            throw ValidationException::withMessages([
                'student_id' => ['Selected user is not a student.']
            ]);
        }

        if ($librarian->role_id !== Role::LIBRARIAN) {
            throw ValidationException::withMessages([
                'librarian_id' => ['Selected user is not a librarian.']
            ]);
        }
    }

    public function returnBook(int $rentalId, int $librarianId): array
    {
        $rental = Rental::find($rentalId);

        if (!$rental) {
            throw ValidationException::withMessages([
                'rental' => 'This book was not rented out, hence cannot be returned.'
            ]);
        }

        if ($rental->returned_at !== null) {
            throw ValidationException::withMessages([
                'rental' => 'This book has already been returned.'
            ]);
        }

        $book = $rental->book;

        DB::transaction(function () use ($rental, $book, $librarianId) {
            $rental->update([
                'returned_at' => now(),
                'librarian_id' => $librarianId,
            ]);

            $book->increment('number_of_copies_available');
        });

        $overdue = max(0, $rental->days_rented - $rental->rental_period);

        if ($overdue > 0) {
            Log::debug("Book ID {$book->id} returned with {$overdue} overdue days by student ID {$rental->student_id}.");
        }

        return [
            'librarian_id' => $rental->librarian_id,
            'student_id' => $rental->student_id,
            'overdue_days' => $overdue,
        ];
    }

    public function discardBook(int $bookId, int $librarianId)
    {
        $book = Book::find($bookId);

        if (!$book) {
            throw new \Exception('This book cannot be discarded as it does not exist in the inventory.');
        }

        if ($book->number_of_copies_available === 0) {
            $book->update(['is_active' => false]);

            throw new \Exception('This book has no copies left to discard.');
        }

        DB::beginTransaction();

        try {
            $discarded = DiscardedBook::create([
                'book_id' => $book->id,
                'librarian_id' => $librarianId,
                'discarded_at' => now(),
            ]);

            $book->decrement('number_of_copies_available');

            DB::commit();

            return $discarded;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActiveRentals(Request $request)
    {
        $request->validate([
            'per_page' => 'integer|nullable|in:20,50,100',
            'search_value' => 'string|nullable',
            'book_id' => 'integer|nullable|exists:books,id',
            'student_id' => 'nullable|integer|exists:users,id',
        ]);

        $query = Rental::with(['book', 'student', 'librarian'])
            ->whereNull('returned_at');

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->book_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('search_value')) {
            $search = $request->search_value;
            $query->whereHas('book', function ($q) use ($search) {
                $q->whereRaw("name ILIKE ?", ["%{$search}%"]);
            });
        }

        $perPage = $request->per_page ?? 20;
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($rental) {
            return [
                'id' => $rental->id,
                'book_title' => $rental->book->name,
                'rented_by' => [
                    'name' => $rental->student->first_name,
                    'last_name' => $rental->student->last_name,
                    'id' => $rental->student->id,
                ],
                'rental_date' => $rental->rented_at->toDateTimeString(),
                'active_days' => now()->diffInDays($rental->rented_at),
                'rented_out_by' => [
                    'name' => $rental->librarian->first_name,
                    'last_name' => $rental->librarian->last_name,
                    'id' => $rental->librarian->id,
                ],
            ];
        });

        return $paginated;
    }

    public function getReturnedRentals(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string',
            'book_id' => 'integer|nullable|exists:books,id',
            'student_id' => 'nullable|integer|exists:users,id',
        ]);

        $query = Rental::with(['book', 'librarian', 'student'])
            ->whereNotNull('returned_at');

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->book_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('search_value')) {
            $search = $request->search_value;
            $query->whereHas('book', function ($q) use ($search) {
                $q->whereRaw("name ILIKE ?", ["%{$search}%"]);
            });
        }

        $perPage = $request->per_page ?? 20;
        $returnedRentals = $query->paginate($perPage);

        $returnedRentals->getCollection()->transform(function ($rental) {
            return [
                'book_title' => $rental->book->name,
                'returned_by' => [
                    'name' => $rental->student->name,
                    'last_name' => $rental->student->last_name,
                    'id' => $rental->student->id,
                ],
                'rental_date' => $rental->rented_at->toDateTimeString(),
                'returned_at' => $rental->returned_at->toDateTimeString(),
                'rented_out_by' => [
                    'name' => $rental->librarian->name,
                    'last_name' => $rental->librarian->last_name,
                    'id' => $rental->librarian->id,
                ],
            ];
        });

        return $returnedRentals;
    }

    public function getOverdueRentals(Request $request)
    {
        $request->validate([
            'search_value' => 'nullable|string',
            'per_page' => 'nullable|integer|in:20,50,100',
            'book_id' => 'integer|nullable|exists:books,id',
            'student_id' => 'nullable|integer|exists:users,id',
        ]);

        $rentalPolicy = Policy::where('name', 'rental_period')->first();
        $rentalPeriod = $rentalPolicy->period;

        $query = Rental::with(['book', 'librarian', 'student'])
            ->whereNull('returned_at')
            ->whereDate('rented_at', '<=', now()->subDays($rentalPeriod));

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->book_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('search_value')) {
            $search = $request->search_value;

            $query->whereHas('book', function ($q) use ($search) {
                $q->whereRaw("name ILIKE ?", ["%{$search}%"]);
            });
        }

        $perPage = $request->per_page ?? 20;
        $overdueRentals = $query->paginate($perPage);

        $overdueRentals->getCollection()->transform(function ($rental) use ($rentalPeriod) {
            $daysRented = now()->diffInDays($rental->rented_at);
            $daysOverdue = $daysRented - $rentalPeriod;

            return [
                'book_title' => $rental->book->name,
                'rental_date' => $rental->rented_at ? $rental->rented_at->toDateTimeString() : null,
                'rented_by' => [
                    'name' => $rental->student->first_name,
                    'last_name' => $rental->student->last_name,
                    'id' => $rental->student->id,
                ],
                'total_rental_days' => $daysRented,
                'days_overdue' => $daysOverdue > 0 ? $daysOverdue : 0,
            ];
        });

        return $overdueRentals;
    }

    public function getRentalSummary(): array
    {
        $rentalPolicy = Policy::where('name', 'rental_period')->first();
        $rentalPeriod = $rentalPolicy->period;
        $now = now();

        $notOverdueCount = Rental::whereNull('returned_at')
            ->whereDate('rented_at', '>', $now->copy()->subDays($rentalPeriod))
            ->count();

        $overdueCount = Rental::whereNull('returned_at')
            ->whereDate('rented_at', '<=', $now->copy()->subDays($rentalPeriod))
            ->count();

        return [
            'active_rentals_not_overdue' => $notOverdueCount,
            'active_rentals_overdue' => $overdueCount,
        ];
    }
}
