<?php

namespace App\Services;

use App\Http\Requests\Reservation\CreateReservationRequest;
use App\Models\Book;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function createReservation(array $data, $user): Reservation
    {
        return DB::transaction(function () use ($data, $user) {

            $book = Book::lockForUpdate()->findOrFail($data['book_id']);

            $studentId = $user->isLibrarian()
                ? ($data['book_id'] ?? null)
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
                if (empty($data['student_id'])) {
                    throw new \Exception("Librarian must provide a student ID.");
                }

                $reservation = Reservation::create([
                    'book_id' => $book->id,
                    'librarian_id' => $user->id,
                    'student_id' => $data['student_id'],
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

    }

    public function confirmReservation($id, $librarian): Reservation
    {
        return DB::transaction(function () use ($id, $librarian) {

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
    }
}
