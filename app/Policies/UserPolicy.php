<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function view(User $authUser, User $user): bool
    {
        if ($authUser->role_id === Role::STUDENT) {
            return $authUser->id === $user->id;
        }

        return true;
    }
}
