<?php

namespace App\Services;

use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BookService
{
    public function create(array $data)
    {
        $book = Book::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'number_of_pages' => $data['number_of_pages'],
            'number_of_copies_available' => $data['number_of_copies_available'],
            'isbn' => $data['isbn'],
            'language' => $data['language'],
            'script' => $data['script'],
            'binding' => $data['binding'],
            'dimensions' => $data['dimensions'],
        ]);

        $book->categories()->attach($data['categories']);
        $book->genres()->attach($data['genres']);
        $book->authors()->attach($data['authors']);
        $book->publisher()->associate($data['publisher_id']);
        $book->save();

        if (!empty ($data['images'])) {
            foreach ($data['images'] as $index => $image) {
                $path = $image->store('book_images', 'public');
                $type = $imageTypes[$index] ?? 'artwork';

                $book->images()->create([
                    'path' => $path,
                    'type' => $type,
                ]);
            }
        }

        $book->load(['images', 'authors', 'genres', 'categories', 'publisher']);

        return $book;
    }

    public function update(Book $book, array $data)
    {
        $book->update($data);

        return $book;
    }
    public function updateCover(Book $book, UploadedFile $image)
    {
        $existingFrontCover = $book->images()->where('type', 'front_cover')->first();

        if ($existingFrontCover) {
            Storage::disk('public')->delete($existingFrontCover->path);
            $existingFrontCover->delete();
        }

        $path = $image->store('book_images', 'public');

        $book->images()->create([
            'path' => $path,
            'type' => 'front_cover'
        ]);

        return $path;
    }

    public function getBooks (array $data)
    {
        $validated = Validator::make($data, [
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string',
        ])->validate();

        $query = Book::with(['images', 'authors', 'genres', 'categories', 'publisher']);

        if (!empty($validated['search_value'])) {
            $search = $validated['search_value'];
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%$search%"])
                    ->orWhereRaw('description ILIKE ?', ["%$search%"]);
            });
        }

        $perPage = $validated['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

}
