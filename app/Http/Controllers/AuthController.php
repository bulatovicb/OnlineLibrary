<?php

namespace App\Http\Controllers;


use App\Http\Requests\LoginUserRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handles user login via email and password.
     * Validates credentials. checks if the provided password matches the stored hash.
     * Return JSON response with a generated Bearer token on success.
     *
     * @param LoginUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginUserRequest $request)
    {
        $result = $this->authService->login($request->email, $request->password);

        return response()->json([
            'message' => 'Logged in successfully.',
            'access_token' => $result['token'],
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Handles user logout.
     * Checks if the user is authenticated.
     * Deletes current access token and returns JSON response confirming successful logout.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $this->authService->logout($request);

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
