<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class PasswordResetController extends Controller
{
    /**
     * Handle a password reset link request.
     *
     * Validates the provided email address and check if it belongs to a librarian.
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
                Rule::exists('users', 'email')->where(function ($query) {
                    $query->where('role_id', Role::LIBRARIAN);
                }),
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
}
