<?php

namespace App\Http\Controllers;

use App\Events\LibrarianCreated;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Creates new user.
     * Accessible only by authenticated librarians.
     * Checks if the user is authorised.
     * Validates the provided profile data and creates a new user if validation passes.
     * If the user is Librarian, triggers the LibrarianCreated event after it is created.
     * Returns a JSON response with the user data and a success message.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateUserRequest $request)
    {
        $user = $this->userService->createUser($request);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user
        ], 201);
    }

    /**
     *  Shows user profile data based on provided username.
     *  Librarians can view any user's profile.
     *  Students can view only their own profile.
     *  Returns error if user is not found.
     *  Returns a JSON response.
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return response()->json([$user]);
    }

    /**
     *  Returns the profile picture of a user based on the provided username.
     *  Librarians can view any user's profile picture.
     *  Students can view only their own profile picture.
     *  Returns JSON error response if the user or the profile picture is not found.
     *  Otherwise, returns the image file.
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function profilePicture(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $this->authorize('view', $user);

        if (!$user->profile_picture) {
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
    public function update(UpdateUserRequest $request, User $user)
    {
        $user = $this->userService->updateUser($user, $request);

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
        return $this->userService->updateProfilePicture($request);
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
        try {
            $users = $this->userService->getUsersByRole($request);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No users found.',
                'data' => []
            ], 404);
        }

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
    public function destroy(Request $request, UserService $userService)
    {
        if (!is_array($request->input('users_id'))) {
            $request->merge([
                'users_id' => [$request->input('users_id')]
            ]);
        }

        $request->validate([
            'users_id' => 'required|array',
            'users_id.*' => 'integer|exists:users,id',
        ]);

        $userIds = $request->input('users_id');

        $userService->deleteUsers($userIds);

        return response()->json([
            'message' => 'User(s) deleted successfully.',
        ]);
    }
}
