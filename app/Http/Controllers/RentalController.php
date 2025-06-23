<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use Illuminate\Http\Request;

class RentalController extends Controller
{
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
}
