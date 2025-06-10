<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AllBooksImported extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the message envelope.
     * Subject for received email.
     *
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'All Books Imported',
        );
    }

    /**
     * Get the message content definition.
     * Returns the email content using the all-books-imported template
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.all-books-imported',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }


}
