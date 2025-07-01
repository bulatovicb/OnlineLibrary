<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\Rental;
use Illuminate\Http\Request;

class RentalController extends Controller
{
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
            ->whereDate('rented_at', '<=', now()->subDays($rentalPeriod));

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
