<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthService
{
    public function login(string $email, string $password) : array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            abort(401, 'Invalid email or password');
        }

        $token = $user->createToken($email)->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function logout(Request $request) : void
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
    }
}
