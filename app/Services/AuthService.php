<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $user = User::where('email', request('email'))->first();

        if (!$user || !Hash::check(request('password'), $user->password)) {
            return [
                'error' => 'Invalid email or password',
                'success' => false,
                'code' => 401,
            ];
        }

        $user->increment("login_count");
        $user->last_login_at = $user->current_login_at;
        $user->current_login_at = now();
        $user->save();

        $token = $user->createToken(request('email'))->plainTextToken;

        return [
            'message' => 'Logged in successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role->name,
                'jmbg' => $user->jmbg,
                'email' => $user->email,
                'username' => $user->username,
                'profile_picture' => $user->profile_picture,
                'login_count' => $user->login_count,
                'last_login_at' => $user->last_login_at,
            ]
        ];
    }

    public function logout()
    {
        if (!Auth::check()) {
            return ['error' => 'Unauthorized'];
        }

        Auth::user()->currentAccessToken()->delete();

        return[
            'message' => 'Logged out successfully.',
        ];
    }
}
