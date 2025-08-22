<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\DiscardedBook;
use App\Models\Policy;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RentalController extends Controller
{
    /**
     * Store a newly created rental in storage.
     *
     * Validates the request to ensure the book, student, and librarian exist.
     * Checks if the book is available for rent.
     * Ensures that the student and librarian have the correct roles.
     * Checks if there is a reservation connected with this book if number of copies is 1.
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
        ]);

        $book = Book::findOrFail($request->book_id);

        try {
            $this->validateUserRoles($request->student_id);
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

        $activeReservationsCount = Reservation::where('book_id', $book->id)
            ->where('status', 'reserved')
            ->where('expires_at', '>', now())
            ->count();

        if ($activeReservationsCount >= $book->number_of_copies_available) {
            $studentReservation = Reservation::where('book_id', $book->id)
                ->where('student_id', $request->student_id)
                ->where('status', 'reserved')
                ->where('expires_at', '>', now())
                ->first();

            if (!$studentReservation) {
                return response()->json([
                    'error' => 'No available copies for this book, it is reserved by other students.'
                ], 422);
            }
        }

        $librarian = Auth::user();

        if ($request->filled('reservation_id')) {
            $reservation = Reservation::find($request->reservation_id);
            if ($reservation) {
                $reservation->status = 'rented';
                $reservation->save();
            }
        }

        $rental = Rental::create([
            'book_id' => $book->id,
            'student_id' => $request->student_id,
            'librarian_id' => $librarian->id,
            'reservation_id' => $request->reservation_id,
            'rented_at' => now(),
            'returned_at' => null,
        ]);

        $book->decrement('number_of_copies_available');

        return response()->json([
            'message' => 'Rental created successfully',
            'rental' => $rental
        ], 201);
    }

    public function validateUserRoles(int $studentId)
    {
        $student = User::findOrFail($studentId);

        if ($student->role_id !== Role::STUDENT) {
            throw new \Exception("Selected user is not a student");
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
     *  Librarians can view any user's rentals.
     *  Students can view only their own rentals.
     *
     * @param Rental $rental
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Rental $rental)
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($authUser->role_id === Role::STUDENT && $rental->student_id !== $authUser->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $isReturned = !is_null($rental->returned_at);
        $isOverdue = $rental->is_overdue;

        $message = $isReturned
            ? 'This book has been returned.'
            : ($isOverdue ? 'This rental is overdue!' : 'Rental period is still valid.');


        return response()->json([
            'rented_at' => $rental->rented_at,
            'days_rented' => $rental->days_rented,
            'message' => $message,
            'student' => [
                'first_name' => $rental->student->first_name,
                'last_name' => $rental->student->last_name,
                'id' => $rental->student->id,
            ],
            'librarian' => [
                'first_name' => $rental->librarian->first_name,
                'last_name' => $rental->librarian->last_name,
                'id' => $rental->librarian->id,
            ]
        ]);
    }

    /**
     * Handles the return of a rented book.
     *
     * Validates the existence of the rental record.
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

        if ($rental->returned_at !== null) {
            return response()->json([
                'error' => 'Already returned',
            ], 422);
        }

        $librarian = Auth::user();

        $book = $rental->book;

        $rental->update([
            'returned_at' => now(),
            'librarian_id' => $librarian->id
        ]);

        $book->increment('number_of_copies_available');

        $overdue = max(0, $rental->days_rented - $rental->rental_period);

        if ($overdue > 0) {
            Log::debug("Book ID {$book->id} returned with {$overdue} overdue days by student ID {$rental->student_id}.");
        }

        return response()->json([
            'message' => 'Book returned',
            'librarian_id' => $rental->librarian_id,
            'student_id' => $rental->student_id,
            'overdue_days' => $overdue,
        ]);
    }

    /**
     *Discards a book.
     *
     * Accessible only by authenticated librarians.
     * Validates the existence of the book.
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

        $librarian = Auth::user();

        if ($book->number_of_copies_available == 0) {
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
            'discarded_at' => now()
        ]);

        $book->decrement('number_of_copies_available');

        return response()->json([
            'message' => 'Book discarded successfully.',
            'discarded_at' => $discardedBook->discarded_at,
            'librarian' => $librarian->id,
        ]);
    }

    /**
     * Returns a paginated list of rented books.
     *
     * Supports filtering by book ID to view rented rentals for a specific book.
     * Supports filtering by student ID to view rented rentals for a specific student.
     * Supports case-insensitive partial matching on the book name (using ILIKE for PostgreSQL).
     * Supports pagination with 'per_page' values of 20 (default), 50, or 100.
     * Librarians can view any user's rentals.
     * Students can view only their own rentals.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexRented(Request $request)
    {
        $authUser = Auth::user();

        request()->validate([
            'per_page' => 'integer|nullable|in:20,50,100',
            'search_value' => 'string|nullable',
            'book_id' => 'integer|nullable|exists:books,id',
            'student_id' => 'nullable|integer|exists:users,id',
        ]);

        $query = Rental::with([
            'book',
            'student',
            'librarian'
        ])->whereNull('returned_at');

        if ($authUser->role_id === Role::STUDENT) {
            $query->where('student_id', $authUser->id);
        } else {
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }
        }

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
        $activeRentals = $query->paginate($perPage);

        $activeRentals->getCollection()->transform(function ($rental) {
            return [
                'book_id' => $rental->book->id,
                'book_title' => $rental->book->name,
                'rented_by' => [
                    'first_name' => $rental->student->first_name,
                    'last_name' => $rental->student->last_name,
                    'id' => $rental->student->id,
                ],
                'rental_date' => $rental->rented_at->toDateTimeString(),
                'active_days' => now()->diffInDays($rental->rented_at),
                'rented_out_by' => [
                    'first_name' => $rental->librarian->first_name,
                    'last_name' => $rental->librarian->last_name,
                    'id' => $rental->librarian->id,
                ],
                'rental_id' => $rental->id,
            ];
        });

        return response()->json([
            'message' => "Success",
            'data' => $activeRentals,
        ]);
    }

    /**
     * Returns a paginated list of returned books.
     *
     * Supports filtering by book ID to view returned rentals for a specific book.
     * Supports filtering by student ID to view returned rentals for a specific student.
     * Supports case-insensitive partial matching on the book name (using ILIKE for PostgreSQL).
     * Supports pagination with 'per_page' values of 20 (default), 50, or 100.
     * Librarians can view any user's rentals.
     * Students can view only their own rentals.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexReturned(Request $request)
    {
        $authUser = Auth::user();

        $request->validate([
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string',
            'book_id' => 'integer|nullable|exists:books,id',
            'student_id' => 'nullable|integer|exists:users,id',
        ]);

        $query = Rental::with([
            'book',
            'librarian',
            'student',
        ])->whereNotNull('returned_at');

        if ($authUser->role_id === Role::STUDENT) {
            $query->where('student_id', $authUser->id);
        } else {
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }
        }

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
                    'first_name' => $rental->student->first_name,
                    'last_name' => $rental->student->last_name,
                    'id' => $rental->student->id,
                ],
                'rental_date' => $rental->rented_at->toDateTimeString(),
                'returned_at' => $rental->returned_at->toDateTimeString(),
                'rented_out_by' => [
                    'first_name' => $rental->librarian->first_name,
                    'last_name' => $rental->librarian->last_name,
                    'id' => $rental->librarian->id,
                ],
                'rental_id' => $rental->id,
            ];
        });

        return response()->json([
            'message' => 'Returned Rentals',
            'data' => $returnedRentals,
        ]);
    }

    /**
     * Returns a paginated list of overdue books.
     *
     * Supports filtering by book ID to view overdue rentals for a specific book.
     * Supports filtering by student ID to view overdue rentals for a specific student.
     * Supports case-insensitive partial matching on the book name (ILIKE).
     * Supports pagination with 'per_page' values of 20 (default), 50, or 100.
     * Overdue books are defined as books rented for longer than the allowed rental period in policy.
     * Librarians can view any user's rentals.
     * Students can view only their own rentals.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexOverdue(Request $request)
    {
        $authUser = Auth::user();

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
            ->whereDate('rented_at', '<=', now()
                ->subDays($rentalPeriod));

        if ($authUser->role_id === Role::STUDENT) {
            $query->where('student_id', $authUser->id);
        } else {
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }
        }

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
                'rented_out_by' => [
                    'first_name' => $rental->librarian->first_name,
                    'last_name' => $rental->librarian->last_name,
                    'id' => $rental->librarian->id,
                ],
                'rental_id' => $rental->id,
                'total_rental_days' => $daysRented,
                'days_overdue' => $daysOverdue > 0 ? $daysOverdue : 0,
            ];
        });

        return response()->json([
            'message' => "Success",
            'data' => $overdueRentals,
        ]);

    }

    /**
     * Returns a summary of currently active rentals.
     *
     * Provides a count of books that are currently rented out and not overdue,
     * and a count of those that are overdue based on the rental policy period.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function rentalSummary()
    {
        $rentalPolicy = Policy::where('name', 'rental_period')->first();
        $rentalPeriod = $rentalPolicy->period;
        $now = now();

        $overdueCount = Rental::whereNull('returned_at')
            ->whereDate('rented_at', '<=', $now->copy()->subDays($rentalPeriod))
            ->count();

        $totalRentals = Rental::count();

        $totalReservations = Reservation::count();

        return response()->json([
            'message' => "Rental Summary retrieved successfully",
            'data' => [
                'total_rentals' => $totalRentals,
                'total_reservations' => $totalReservations,
                'active_rentals_overdue' => $overdueCount,
            ]
        ]);
    }
}
