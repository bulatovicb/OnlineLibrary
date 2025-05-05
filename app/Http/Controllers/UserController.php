<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function create(Request $request)
    {


        if (!Auth::check() || Auth::user()->role_id !== 2) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'jmbg' => 'required|regex:/^\d{13}$/|unique:users,jmbg',
            'role_id' => 'required|exists:roles,id',
            'profile_picture' => 'nullable|image|max:5120',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $role = Role::findOrFail($request->role_id);

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
            'role_id' => $role->id,
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
        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'username' => $user->username,
            'jmbg' => $user->jmbg,
            'role' => $user->role->name,
            'profile_picture_url' => $user->profile_picture
                ? route('user.profilePicture', ['username' => $user->username])
                : null,
        ]);


    }

    public function profilePicture($username)
    {
        $user = User::where('username', $username)->first();
        if (!$user || !$user->profile_picture) {
            return response()->json(['error' => 'Profile picture not found'], 404);
        }
        return response()->file(storage_path('app/public/' . $user->profile_picture));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'email' => 'sometimes|string|email|unique:users,email, ' . $user->id,
            'username' => 'sometimes|string|unique:users,username,' . $user->id,
            'jmbg' => 'sometimes|regex:/^\d{13}$/'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $data = $request->only(['first_name', 'last_name', 'email', 'username', 'jmbg']);
        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user
        ]);
    }

    public function updateProfilePicture(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'nullable|image|max:5120',
        ]);
        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        }
        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $user->profile_picture = $path;
        $user->save();
        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'profile_picture_url' => route('user.profilePicture', ['username' => $user->username])
        ]);
    }

    public function index(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string',
        ]);
        $query = User::where('role_id', $request->role_id);

        if ($request->filled('search_value')) {
            $search = strtolower($request->search_value);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(first_name) LIKE ?', ["%$search%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%$search%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%$search%"])
                    ->orWhereRaw('LOWER(username) LIKE ?', ["%$search%"]);
            });
        }

        $per_page = $request->per_page ?? 20;
        $users = $query->paginate($per_page);

        return response()->json([
            'message' => 'Users retrieved successfully.',
            'data' => $users
        ]);
    }
}
