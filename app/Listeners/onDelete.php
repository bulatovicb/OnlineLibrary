<?php

namespace App\Listeners;

use App\Events\BookDeleting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class onDelete
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handles incoming BookDeleting event.
     *
     * Loops through all images associated with the book being deleted.
     *
     * @param BookDeleting $event
     * @return void
     */
    public function handle(BookDeleting $event): void
    {
        $event->book->images()->each(function ($image) {
            $image->delete();
        });
    }
}
