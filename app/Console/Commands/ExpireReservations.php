<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class ExpireReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire reservations after their expiration time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Reservation::where('status', 'reserved')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
