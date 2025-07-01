<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\Rental;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    /**
     * Returns rented book list.
     *
     * Accessible only to authenticated librarians.
     * Supports case-insensitive partial matching on the book name field (ILIKE).
     * Supports pagination with per-page values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexRented(Request $request)
    {
        request()->validate([
            'per_page' => 'integer|nullable|in:20,50,100',
            'search_value' => 'string|nullable',
        ]);

        $query = Rental::with([
            'book',
            'student',
            'librarian'
        ])->whereNull('returned_at');

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

        return response()->json([
            'message' => "Success",
            'data' => $activeRentals,
        ]);
    }
  
    /**
     * Returns a paginated list of returned books.
     *
     * Accessible only to authenticated librarians.
     * Supports case-insensitive partial matching on the book name (using ILIKE for PostgreSQL).
     * Supports pagination with 'per_page' values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexReturned(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string'
        ]);

        $query = Rental::with([
            'book',
            'librarian',
            'student',
        ])->whereNotNull('returned_at');

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

        return response()->json([
            'message' => 'Returned Rentals',
            'data' => $returnedRentals,
        ]);
    }

    /**
     * Returns a paginated list of overdue books.
     *
     * Accessible only to authenticated librarians.
     * Supports case-insensitive partial matching on the book name (ILIKE).
     * Supports pagination with 'per_page' values of 20 (default), 50, or 100.
     * Overdue books are defined as books rented for longer than the allowed rental period in policy.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexOverdue(Request $request)
    {
        $request->validate([
            'search_value' => 'nullable|string',
            'per_page' => 'nullable|integer|in:20,50,100',
        ]);

        $rentalPolicy = Policy::where('name', 'rental_period')->first();
        $rentalPeriod = $rentalPolicy->period;

        $query = Rental::with(['book', 'librarian', 'student'])
            ->whereNull('returned_at')
            ->whereDate('rented_at', '<=', now()
                ->subDays($rentalPeriod));

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

        return response()->json([
            'message' => "Success",
            'data' => $overdueRentals,
        ]);
           
    }
}
