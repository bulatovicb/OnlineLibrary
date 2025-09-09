<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        $resetUrl = "http://localhost:3000/reset-password?token={$this->token}&email={$this->user->email}";

        return $this->subject('Reset your password')
            ->view('emails.reset-password')
            ->with([
                'resetUrl' => $resetUrl,
                'user' => $this->user,
            ]);
    }
}
