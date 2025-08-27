<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Reservation;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    /**
     * Creates a new reservation for a book by a student(waiting for confirmation - pending) and a librarian (automatic confirm - reserved).
     *
     * Validates the input and change the reservation status
     * with a 24-hour expiration.
     * Checks if the reservation is already confirmed.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'student_id' => 'nullable|exists:users,id',
        ]);

        $user = $request->user();

        try {
            $reservation = DB::transaction(function () use ($request, $user) {

                $book = Book::lockForUpdate()->findOrFail($request->book_id);

                $studentId = $user->isLibrarian()
                    ? ($request->student_id ?? null)
                    : $user->id;

                if ($studentId) {
                    $existingReservation = Reservation::where('book_id', $book->id)
                        ->where('student_id', $studentId)
                        ->whereIn('status', ['pending', 'reserved'])
                        ->first();

                    if ($existingReservation) {
                        throw new \Exception("This student already has an active reservation for this book.");
                    }
                }

                $confirmedReservations = Reservation::where('book_id', $book->id)
                    ->where('status', 'reserved')
                    ->count();

                if ($confirmedReservations >= $book->number_of_copies_available) {
                    throw new \Exception("No available copies for this book.");
                }

                if ($user->isLibrarian()) {
                    if (!$request->filled('student_id')) {
                        throw new \Exception("Librarian must provide a student ID.");
                    }

                    $reservation = Reservation::create([
                        'book_id' => $book->id,
                        'librarian_id' => $user->id,
                        'student_id' => $request->student_id,
                        'reserved_at' => now(),
                        'expires_at' => now()->addHours(24),
                        'status' => 'reserved'
                    ]);
                } else {
                    $reservation = Reservation::create([
                        'book_id' => $book->id,
                        'student_id' => $user->id,
                        'reserved_at' => now(),
                        'expires_at' => now()->addHours(24),
                        'status' => 'pending'
                    ]);
                }

                return $reservation;
            });

            return response()->json([
                'message' => $reservation->status === 'reserved'
                    ? 'Reservation created and automatically confirmed'
                    : 'Reservation created and waiting for confirmation',
                'reservation' => $reservation->load(['book:id,name', 'student:id,first_name,last_name'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Confirms an existing reservation and change status to reserved.
     *
     * Checks if the reservation is still in 'pending' status before confirming.
     * Checks if the reservation is already confirmed.
     * Updates the reservation status to 'reserved'.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm($id)
    {
        $librarian = Auth::user();

        try {
            $reservation = DB::transaction(function () use ($id, $librarian) {

                $reservation = Reservation::lockForUpdate()->findOrFail($id);

                if ($reservation->status !== 'pending') {
                    throw new \Exception('Only pending reservations can be confirmed. Current status: ' . $reservation->status);
                }

                $book = Book::lockForUpdate()->findOrFail($reservation->book_id);

                $confirmedReservations = Reservation::where('book_id', $book->id)
                    ->where('status', 'reserved')
                    ->count();

                if ($confirmedReservations >= $book->number_of_copies_available) {
                    throw new \Exception("No available copies for this book.");
                }

                $reservation->status = 'reserved';
                $reservation->librarian_id = $librarian->id;
                $reservation->save();

                return $reservation;
            });

            return response()->json([
                'message' => 'Reservation confirmed',
                'reservation' => $reservation,
                'librarian_id' => $librarian->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Rejects an existing reservation.
     *
     * Only reservations currently in 'pending' status can be rejected.
     * Updates the reservation status to 'rejected'.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject($id)
    {
        $reservation = Reservation::findOrFail($id);

        if ($reservation->status !== 'pending') {
            return response()->json([
                'message' => 'This rejection cannot be confirmed. Current status: ' . $reservation->status
            ], 422);
        }

        $reservation->status = 'rejected';
        $reservation->save();

        $librarian = Auth::user();

        return response()->json([
            'message' => 'Reservation rejected',
            'librarian_id' => $librarian->id
        ]);
    }

    /**
     * Students can cancel they're reservations.
     *
     * Checks if the user role is student.
     * Checks if the reservation belongs to logged in student.
     * Checks if the reservation has been already confirmed.
     * Changes status to "cancelled".
     * Returns JSON success message.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($id)
    {
        $user = Auth::user();

        $reservation = Reservation::findOrFail($id);

        if ($user->role_id !== 1) {
            return response()->json([
                'error' => 'Only students can cancel their reservations.'
            ], 403);
        }

        if ($reservation->student_id !== $user->id) {
            return response()->json([
                'error' => 'You can only cancel your own reservations.'
            ], 403);
        }

        if ($reservation->status === 'reserved') {
            return response()->json([
                'error' => 'You cannot cancel a reservation that has already been confirmed.'
            ], 422);
        }

        $reservation->status = 'cancelled';
        $reservation->save();

        return response()->json([
            'message' => 'Reservation successfully cancelled.',
            'reservation' => $reservation
        ]);
    }

    /**
     * Retrieves archived reservations.
     *
     * By default, returns reservations with status 'expired', 'rented', or 'rejected'.
     * Can filter by a specific status via query parameter.
     * Supports case-insensitive partial matching on name (ILIKE).
     * Supports pagination with per-page values of 20 (default), 50, or 100.
     * Librarians can view any user's archived reservations.
     * Students can view only their own archived reservations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function archived(Request $request)
    {
        $authUser = Auth::user();

        $validated = $request->validate([
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $search = $validated['search_value'] ?? null;
        $status = $validated['status'] ?? null;

        $query = Reservation::with('rental', 'book:id,name', 'student:id,first_name,last_name')
            ->where(function ($q) use ($authUser) {
                $q->whereHas('rental')
                    ->orWhereIn('status', ['expired', 'rejected', 'rented', 'cancelled'])
                    ->forStudent($authUser);
            });

        if ($status) {
            if ($status === 'with_rental') {
                $query->whereHas('rental');
            } else {
                $query->where('status', $status);
            }
        }

        if ($search) {
            $query->whereHas('book', function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%{$search}%"]);
            });
        }

        $reservations = $query->paginate($perPage);

        return response()->json([
            'message' => 'Archived reservations list',
            'reservations' => $reservations,
        ]);
    }

    /**
     * Retrieves active reservations.
     *
     * By default, returns reservations with status 'reserved', 'pending' or 'rejected'.
     * Can filter by a specific status via query parameter.
     * Supports case-insensitive partial matching on name (ILIKE).
     * Supports pagination with per-page values of 20 (default), 50, or 100.
     * Librarians can view any user's active reservations.
     * Students can view only their own active reservations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function active(Request $request)
    {
        $authUser = Auth::user();

        $validated = $request->validate([
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $search = $validated['search_value'] ?? null;
        $status = $validated['status'] ?? null;

        $query = Reservation::with(['book:id,name', 'student:id,first_name,last_name,username', 'librarian:id,first_name,last_name,username'])
            ->whereIn('status', ['reserved', 'rejected', 'pending'])
            ->forStudent($authUser);
        
        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('book', function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%{$search}%"]);
            });
        }

        $reservations = $query->paginate($perPage);

        return response()->json([
            'message' => 'Active reservations list',
            'reservations' => $reservations,
        ]);
    }

}
