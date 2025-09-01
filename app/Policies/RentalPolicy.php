<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\Role;
use App\Models\User;

class RentalPolicy
{
    /**
     * Determine whether the user can view the rental.
     *
     * @param  \App\Models\User   $user
     * @param  \App\Models\Rental $rental
     * @return bool
     */
    public function view(User $user, Rental $rental): bool
    {
        if ($user->role_id === Role::STUDENT) {
            return $rental->student_id === $user->id;
        }

        return true;
    }

}
