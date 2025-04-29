<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function create(Request $request){


        if (!Auth::check() || Auth::user()->role !== 'librarian') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'jmbg' => 'required|regex:/^\d{13}$/|unique:users,jmbg',
            'role' => 'required|in:student,librarian',
            'profile_picture' => 'nullable|image|max:5120',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $profilePicturePath = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'username' => $request->username,
            'jmbg' => $request->jmbg,
            'role' => $request->role,
            'profile_picture' => $profilePicturePath,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user
        ], 201);
    }
    public function show($username)
    {
        $user=User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'username' => $user->username,
            'jmbg' => $user->jmbg,
            'role' => $user->role,
            'profile_picture_url' => $user->profile_picture
                ? route('user.profilePicture', ['username' => $user->username])
                : null,
        ]);



    }
    public function profilePicture($username)
    {
        $user=User::where('username', $username)->first();
        if (!$user || !$user->profile_picture) {
            return response()->json(['error' => 'Profile picture not found'], 404);
        }
        return response()->file(storage_path('app/public/' . $user->profile_picture));
    }
}
