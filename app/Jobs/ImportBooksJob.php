<?php

namespace App\Jobs;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class ImportBooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $query;
    protected $copiesAvailable;

    /**
     * @param $query
     * @param $copiesAvailable
     */
    public function __construct($query, $copiesAvailable)
    {
        $this->query = $query;
        $this->copiesAvailable = $copiesAvailable;
    }

    /**
     * 
     * @return void
     */
    public function handle(): void
    {
        $key = 'google-books-api:' . $this->query;

        if (!RateLimiter::tooManyAttempts($key, 5)) {
            RateLimiter::hit($key, 60);

            $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
                'q' => $this->query,
            ]);

            if ($response->successful()) {
                $books = $response->json()['items'] ?? [];
                foreach ($books as $bookData) {
                    $volumeInfo = $bookData['volumeInfo'] ?? [];
                    Book::updateOrCreate([
                        'name' => $volumeInfo['title'] ?? 'No title',
                        'description' => $volumeInfo['description'] ?? 'No description',
                        'number_of_pages' => $volumeInfo['pageCount'] ?? 0,
                        'number_of_copies_available' => $this->copiesAvailable,
                        'isbn' => $volumeInfo['industryIdentifiers'][0]['identifier'] ?? uniqid(),
                        'language' => $volumeInfo['language'] ?? 'unknown',
                        'script' => 'Latin',
                        'binding' => 'Paperback',
                        'dimensions' => 'N/A',
                    ]);
                }
            }

        }
    }

}
