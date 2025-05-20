<?php

namespace App\Listeners;

use App\Events\LibrarianCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Password;

class SendLibrarianSetPasswordEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the LibrarianCreated event.
     *
     * Sends a password reset email containing password reset token.
     *
     * @param LibrarianCreated $event
     * @return void
     */
    public function handle(LibrarianCreated $event): void
    {
        $librarian = $event->librarian;

        $token=Password::broker()->createToken($librarian);

        $librarian->sendPasswordResetNotification($token);
    }
}
