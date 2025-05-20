<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LibrarianCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $librarian;

    /**
     * Triggers when new Librarian is created.
     *
     * Fires after checking if created user's role is librarian.
     *
     * @param User $librarian
     */
    public function __construct(User $librarian)
    {
        $this->librarian = $librarian;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
