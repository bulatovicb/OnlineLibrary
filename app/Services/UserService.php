<?php

namespace App\Services;

use App\Events\LibrarianCreated;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function createUser($request)
    {
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

        if ($role->id === Role::LIBRARIAN) {
            event(new LibrarianCreated($user));
        }

        return $user;
    }

    public function updateProfilePicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();

        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $user->profile_picture = $path;
        $user->save();

        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'profile_picture' => $user->profile_picture,
        ]);
    }

    public function getUsersByRole($request)
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
                $q->whereRaw('first_name ILIKE ?', ["%$search%"])
                    ->orWhereRaw('last_name ILIKE ?', ["%$search%"])
                    ->orWhereRaw('email ILIKE ?', ["%$search%"])
                    ->orWhereRaw('username ILIKE ?', ["%$search%"]);
            });
        }

        $per_page = $request->per_page ?? 20;
        return $query->paginate($per_page);
    }

}
