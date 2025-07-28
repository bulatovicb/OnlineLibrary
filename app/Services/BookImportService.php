<?php

namespace App\Services;

use App\Jobs\ImportBooksJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Bus\Batch;
use App\Mail\AllBooksImported;

class BookImportService
{
    public function import(string $query, int $copies = 1): void
    {
        ImportBooksJob::dispatch($query, $copies);
    }

    public function importBatch(array $queries, string $email, int $copies = 1): void
    {
        $jobs = collect($queries)->map(function ($query) use ($copies) {
            return new ImportBooksJob($query, $copies);
        });

        Bus::batch($jobs)
            ->then(function (Batch $batch) use ($email) {
                Mail::to($email)->send(new AllBooksImported());
            })
            ->dispatch();
    }
}

