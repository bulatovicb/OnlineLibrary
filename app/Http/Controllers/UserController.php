<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    /**
     * Creates new user.
     * Accessible only by authenticated librarians.
     * Checks if the user is authorised.
     * Validates the provided profile data and creates a new user if validation passes.
     * Returns a JSON response with the user data and a success message.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {

        if (!Auth::check() || !Auth::user()->isLibrarian()) {
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

    /**
     * Shows user profile data based on provided username.
     * Accessible only by authenticated librarians.
     * Returns error if user is not found.
     * Returns a JSON response containing users first name, last name, email, jmbg, role and profile picture (if available).
     *
     * @param $username
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Returns the profile picture of a user based on the provided username.
     * Accessible only by authenticated librarians.
     * Returns JSON error response if the user or the profile picture is not found.
     * Otherwise, returns the image file.
     *
     * @param $username
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function profilePicture($username)
    {
        $user = User::where('username', $username)->first();

        if (!$user || !$user->profile_picture) {
            return response()->json(['error' => 'Profile picture not found'], 404);
        }
        return response()->file(storage_path('app/public/' . $user->profile_picture));
    }

    /**
     * Updates the authenticated user's profile data.
     * Validates the provided input attributes and returns error message if validator fails.
     * On success, updates the user's data and returns JSON response with success message.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string',
            'last_name' => 'sometimes|required|string',
            'email' => 'sometimes|required|string|email|unique:users,email, ' . $user->id,
            'username' => 'sometimes|required|string|unique:users,username,' . $user->id,
            'jmbg' => 'sometimes|required|regex:/^\d{13}$/'
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

    /**
     * Updates the authenticated user's profile picture.
     * Validates the uploaded image file.
     * If a valid image is provided, it is stored and the user's profile picture path is updated.
     * Returns a JSON response with a success message and the URL to the new profile picture.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfilePicture(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'profile_picture' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->profile_picture = $path;
            $user->save();
        }

        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'profile_picture_url' => $user->profile_picture
                ? route('user.profilePicture', ['username' => $user->username])
                : null,
        ]);
    }

    /**
     * Returns a paginated list of users filtered by role and search.
     * Accessible only by authenticated librarians.
     * Validates the incoming request to ensure that valid role ID is provided.
     * Supports case-insensitive partial search.
     * Returns a paginated list of users matching the given role and optional search filter.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Deletes  selected users based on provided user IDs.
     * Accessible only by authenticated librarians.
     * Accepts a single ID or an array od users IDs.
     * Returns JSON response with success message.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $selectedUsers = $request->input('users_id');

        if (!is_array($selectedUsers)) {
            $selectedUsers = [$selectedUsers];
        }
        $usersToDelete = User::whereIn('id', $selectedUsers)->get();

        foreach ($usersToDelete as $user) {
            $user->delete();
        }

        return response()->json([
            'message' => 'Users deleted successfully.',

        ]);
    }
}
