<?php

namespace App\Jobs;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ImportBooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $query;

    /**
     * Create a new job instance.
     */
    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q' => $this->query,
        ]);

        if ($response->successful()) {
            $books = $response->json()['items'] ?? [];
            foreach ($books as $bookData) {
                $volumeInfo = $bookData['volumeInfo'];
                Book::create([
                    'name' => $volumeInfo['title'] ?? 'No title',
                    'description' => $volumeInfo['description'] ?? 'No description',
                    'number_of_pages' => $volumeInfo['pageCount'] ?? 0,

                ]);
            }
        }

    }
}
