<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;

class RentalController extends Controller
{
    public function validateUserRoles(int $studentId, int $librarianId)
    {
        $student = User::findOrFail($studentId);
        $librarian = User::findOrFail($librarianId);

        if ($student->role_id !== Role::STUDENT) {
            throw new \Exception("Selected user is not a student");
        }

        if ($librarian->role_id !== Role::LIBRARIAN){
            throw new \Exception("Selected user is not a librarian");
        }
    }
}
