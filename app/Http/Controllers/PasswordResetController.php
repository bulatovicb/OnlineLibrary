<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class PasswordResetController extends Controller
{
    /**
     * Handle a password reset link request.
     *
     * Validates the provided email address.
     * If valid, sends a password reset email containing password reset token.
     * If invalid, returns validation error response.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email')
            ],
        ],
            [
                'email.exists' => 'The provided email address does not exist in our records.',
            ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status)
            ], 200);
        } else {
            return response()->json([
                'message' => __($status)
            ], 400);
        }
    }

    /**
     * Resets password using received token.
     *
     * Validates the received token.
     * Validates that password and confirmed password fields match.
     * Log in the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email')
            ],
            'password' => 'required|string|min:8|confirmed',
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->password = Hash::make($password);
                $user->save();
                event(new PasswordReset($user));

                Auth::login($user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status)
            ], 200);
        } else {
            return response()->json([
                'message' => __($status)
            ], 400);
        }

    }

    /**
     * Change the authenticated user's password.
     *
     * Validates the current password and checks if it matches the stored hash.
     * If valid, updates the user's password with the new one.
     * Deletes all active tokens to force logout from all devices.
     * Returns a JSON response with a success message or error if validation fails.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'error' => 'Current password is incorrect.'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password changed successfully.'
        ]);
    }

}
