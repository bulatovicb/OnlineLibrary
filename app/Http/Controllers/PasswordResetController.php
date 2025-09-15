<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendResetLinkRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\PasswordService;
use Illuminate\Support\Facades\Password;
use RuntimeException;


class PasswordResetController extends Controller
{

    public function __construct(private PasswordService $passwordService) {}

    /**
     * Handle a password reset link request.
     *
     * Validates the provided email address.
     * If valid, sends a password reset email containing password reset token.
     * If invalid, returns validation error response.
     *
     * @param SendResetLinkRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(SendResetLinkRequest $request)
    {
        $sent = $this->passwordService->sendResetLink($request->email);

        if (!$sent) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'message' => 'Password reset link sent successfully.'
        ], 200);
    }

    /**
     * Resets password using received token.
     *
     * Validates the received token.
     * Validates that password and confirmed password fields match.
     * Log in the user.
     *
     * @param ResetPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(ResetPasswordRequest $request)
    {
        $status = $this->passwordService->resetPassword(
            $request->only('email', 'password', 'password_confirmation', 'token')
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 200);
        }

        return response()->json(['message' => __($status)], 400);
    }

    /**
     * Change the authenticated user's password.
     *
     * Validates the current password and checks if it matches the stored hash.
     * If valid, updates the user's password with the new one.
     * Deletes all active tokens to force logout from all devices.
     * Returns a JSON response with a success message or error if validation fails.
     *
     * @param ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $this->passwordService->changePassword(
                $request->current_password,
                $request->new_password
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => 'Current password is incorrect.'
            ], 400);
        }

        return response()->json([
            'message' => 'Password changed successfully.'
        ]);
    }

}
