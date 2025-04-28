<?php

namespace App\Http\Controllers;

use App\Models\Librarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $librarian = Librarian::where('email', request('email'))->first();

        if (!$librarian || !Hash::check(request('password'), $librarian->password)) {
            return response()->json(['error' => 'Invalid email or password'], 401);
        }


    $token = $librarian->createToken(request('email'))->plainTextToken;
        return response()->json([
            'message' => 'Logged in successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {

        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
