<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;

class ReservationPolicy
{
    /**
     * Determine whether the user can view the reservation.
     *
     * @param User $user
     * @param Reservation $reservation
     * @return bool
     */
    public function view(User $user, Reservation $reservation): bool
    {
        if ($user->role_id === Role::STUDENT) {
            return $reservation->student_id === $user->id;
        }

        return true;
    }

}
