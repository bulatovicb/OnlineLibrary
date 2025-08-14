<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    /**
     * Creates a new reservation for a book by a student.
     *
     * Validates the input and sets the reservation status to 'reserved'
     * with a 24-hour expiration.
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

        $reservation = Reservation::create([
            'book_id' => $request->book_id,
            'student_id' => $request->student_id,
            'librarian_id' => Auth::id(),
            'reserved_at' => now(),
            'expires_at' => now()->addHours(24),
            'status' => 'reserved'
        ]);

        return response()->json([
            'message' => 'Book reserved',
            'reservation' => $reservation
        ], 201);
    }

    /**
     * Confirms an existing reservation and converts it into a rental.
     *
     * Checks if the reservation is still in 'reserved' status before confirming.
     * Updates the reservation status to 'rented' and returns the rental ID.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm($id)
    {
        $reservation = Reservation::findOrFail($id);

        if ($reservation->status !== 'reserved') {
            return response()->json([
                'message' => 'This reservation cannot be confirmed. Current status: ' . $reservation->status
            ], 422);
        }

        $rental = Rental::create([
            'book_id' => $reservation->book_id,
            'student_id' => $reservation->student_id,
            'librarian_id' => Auth::id(),
            'rented_at' => now(),
            'returned_at' => null,
        ]);

        $reservation->status = 'rented';
        $reservation->save();

        return response()->json([
            'message' => 'Reservation confirmed and rented',
            'reservation_id' => $reservation->id,
            'rental_id' => $rental->id
        ], 201);
    }

    /**
     * Rejects an existing reservation.
     *
     * Only reservations currently in 'reserved' status can be rejected.
     * Updates the reservation status to 'rejected'.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject($id)
    {
        $reservation = Reservation::findOrFail($id);

        if ($reservation->status !== 'reserved') {
            return response()->json([
                'message' => 'This rejection cannot be confirmed. Current status: ' . $reservation->status
            ], 422);
        }

        $reservation->status = 'rejected';
        $reservation->save();

        return response()->json([
            'message' => 'Reservation rejected'
        ]);
    }

    /**
     * Retrieves archived reservations.
     *
     * By default, returns reservations with status 'expired', 'rented', or 'rejected'.
     * Can filter by a specific status via query parameter.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function archive(Request $request)
    {
        $status = $request->query('status');

        $query = Reservation::whereIn('status', ['expired', 'rented', 'rejected']);

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json($query->get());
    }

    /**
     * Retrieves active reservations.
     *
     * By default, returns reservations with status 'reserved' or 'rejected'.
     * Can filter by a specific status via query parameter.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function active(Request $request)
    {
        $status = $request->query('status');

        $query = Reservation::whereIn('status', ['reserved', 'rejected']);

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json($query->get());
    }

}
