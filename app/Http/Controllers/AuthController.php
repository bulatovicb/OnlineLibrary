<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handles user login via email and password.
     * Validates credentials. checks if the provided password matches the stored hash.
     * Return JSON response with a generated Bearer token on success.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

       $result = $this->authService->login($request->email, $request->password);

       return response()->json([
           $result
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
       $result = $this->authService->logout();

       return response()->json(
           $result
       );
    }
}
