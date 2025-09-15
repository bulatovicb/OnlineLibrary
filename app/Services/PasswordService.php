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

    public function sendResetLink(string $email): void
    {
        \Log::info('sendResetLink email', [
            'raw'     => $email,
            'trimmed' => trim($email),
            'lower'   => strtolower(trim($email)),
        ]);

        // 2️⃣  Očisti email da ukloniš razmake i ujednačiš case
        $email = strtolower(trim($email));

        // 3️⃣  Upit sada radi i ako su slova velika/mala pomiješana
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new RuntimeException('User not found');
        }

        $token = Password::createToken($user);
        Mail::to($user->email)->send(new ResetPasswordMail($user, $token));
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
