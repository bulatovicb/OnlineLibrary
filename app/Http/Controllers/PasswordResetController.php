<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

/**
 *
 */
class PasswordResetController extends Controller
{
    /**
     * Sends a password reset link to the librarian's email.
     * This endpoint is publicly accessible, but only librarians will receive the email.
     * If the provided email does not belong to librarian, a 403 error is returned.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->isLibrarian()) {
            return response()->json(['error' => 'Unauthorized - Librarians only'], 403);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent.'], 200);
        } else {
            // Handle the error and return it
            return response()->json(['error' => 'Password reset link could not be sent.', 'status' => $status], 500);
        }
    }

    /**
     * Resets the librarian's password using a valid token.
     * Accepts a token, email, new password and confirmation.
     * If the token is valid and the user is librarian, sets the new password and invalidates token.
     * Returns a success or error message depending on the outcome.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->isLibrarian()) {
            return response()->json(['error' => 'Invalid librarian email.'], 403);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        

        return response()->json(['message' => 'Password reset successfully.'], 200);
    }
}
