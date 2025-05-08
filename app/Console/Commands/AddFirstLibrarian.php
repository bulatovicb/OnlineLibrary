<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AddFirstLibrarian extends Command
{

    protected $signature = 'app:add-first-librarian';
    protected $description = 'Add an initial administrator to manage the system.';

    public function handle()
    {
        $firstName = $this->ask('Enter your first name?');
        $lastName = $this->ask('Enter your last name?');
        $jmbg = $this->ask('Enter your jmbg?');
        $email = $this->ask('Enter your email?');
        $username = $this->ask('Enter your username?');
        $password = $this->secret('Enter your password?');
        $confirmPassword = $this->secret('Confirm password?');

        $validator = Validator::make([
            'email' => $email,
            'jmbg' => $jmbg,
            'username' => $username,
            'password' => $password,
            'confirm_password' => $confirmPassword
        ], [
            'email' => 'required|email|unique:users,email',
            'jmbg' => 'required|unique:users,jmbg|regex:/^\d{13}$/',
            'username' => 'required|unique:users,username',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            $this->error('Validation Error');
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $role = Role::where('name', 'librarian')->first();

        if (!$role) {
            $this->error('Librarian role does not exist!');
            return 1;
        }

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'jmbg' => $jmbg,
            'email' => $email,
            'username' => $username,
            'password' => Hash::make($password),
            'role_id' => $role->id,
        ]);

        $this->info("Librarian {$user->first_name} {$user->last_name} created successfully.");
        return 0;
    }
}
