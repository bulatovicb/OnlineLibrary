<?php

namespace App\Services;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use RuntimeException;

class PasswordService
{

    public function sendResetLink(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        $token = Password::createToken($user);

        Mail::to($user->email)->send(new ResetPasswordMail($user, $token));

        return true;
    }


    public function resetPassword(array $credentials): string
    {
        return Password::reset(
            $credentials,
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
                event(new PasswordReset($user));
                Auth::login($user);
            }
        );
    }


    public function changePassword(string $current, string $new): void
    {
        $user = Auth::user();

        if (!Hash::check($current, $user->password)) {
            throw new RuntimeException('Current password is incorrect.');
        }

        $user->password = Hash::make($new);
        $user->save();

        $user->tokens()->delete();
    }
}
