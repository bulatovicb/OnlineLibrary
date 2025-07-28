<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendResetLinkRequest;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{

    protected $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Handle a password reset link request.
     *
     * Validates the provided email address and check if it belongs to a librarian.
     * If valid, sends a password reset email containing password reset token.
     * If invalid, returns validation error response.
     *
     * @param SendResetLinkRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(SendResetLinkRequest $request)
    {
        $status = $this->passwordResetService->sendResetLink($request->email);

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
     * Log in the librarian.
     *
     * @param ResetPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(ResetPasswordRequest $request)
    {
        $status = $this->passwordResetService->resetPassword($request);

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
}
