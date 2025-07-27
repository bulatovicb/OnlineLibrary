<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\Rental;
use App\Services\RentalService;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    protected RentalService $rentalService;

    public function __construct(RentalService $rentalService)
    {
        $this->rentalService = $rentalService;
    }

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
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'student_id' => 'required|exists:users,id',
            'librarian_id' => 'required|exists:users,id',
        ]);

        try {
            $rental = $this->rentalService->createRental($validated);

            return response()->json([
                'message' => 'Rental created successfully',
                'rental' => $rental
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
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
        try {
            $result = $this->rentalService->returnBook($id, $request->user()->id);

            return response()->json([
                'message' => 'Book returned',
                ...$result
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
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
        $librarian = $request->user();

        try {
            $discarded = $this->rentalService->discardBook($id, $librarian->id);

            return response()->json([
                'message' => 'Book discarded successfully.',
                'discarded_at' => $discarded->discarded_at,
                'librarian' => $discarded->librarian_id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Returns a list of currently active (not returned) book rentals.
     *
     * Accepts optional request filters such as:
     * - per_page: number of results per page (allowed values: 20, 50, 100)
     * - search_value: text search on book name
     * - book_id: filter by book ID
     * - student_id: filter by student ID
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexRented(Request $request)
    {
        $activeRentals = $this->rentalService->getActiveRentals($request);

        return response()->json([
            'message' => 'Success',
            'data' => $activeRentals,
        ]);
    }

    /**
     * Returns a paginated list of returned books.
     *
     * Accessible only to authenticated librarians.
     * Supports filtering by book ID to view returned rentals for a specific book.
     * Supports filtering by student ID to view returned rentals for a specific student.
     * Supports case-insensitive partial matching on the book name (using ILIKE for PostgreSQL).
     * Supports pagination with 'per_page' values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexReturned(Request $request)
    {
        $returnedRentals = $this->rentalService->getReturnedRentals($request);

        return response()->json([
            'message' => 'Returned Rentals',
            'data' => $returnedRentals,
        ]);
    }

    /**
     * Returns a paginated list of overdue books.
     *
     * Accessible only to authenticated librarians.
     * Supports filtering by book ID to view overdue rentals for a specific book.
     * Supports filtering by student ID to view overdue rentals for a specific student.
     * Supports case-insensitive partial matching on the book name (ILIKE).
     * Supports pagination with 'per_page' values of 20 (default), 50, or 100.
     * Overdue books are defined as books rented for longer than the allowed rental period in policy.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexOverdue(Request $request)
    {
        $overdueRentals = $this->rentalService->getOverdueRentals($request);

        return response()->json([
            'message' => 'Success',
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
        $summary = $this->rentalService->getRentalSummary();

        return response()->json([
            'message' => 'Rental Summary retrieved successfully',
            'data' => $summary,
        ]);
    }
}
