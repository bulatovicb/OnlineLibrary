<?php

namespace App\Services;

use App\Models\Genre;
use Illuminate\Support\Facades\Validator;

class GenreService
{
    public function create(array $data): Genre
    {
        return Genre::create([
            'name' => $data['name'],
            'description' => $data['description']
        ]);
    }

    public function getGenres(array $data)
    {
        $validated = Validator::make($data, [
            'per_page' => 'integer|nullable|in:20,50,100',
            'search_value' => 'string|nullable',
        ])->validate();

        $search = $validated['search_value'] ?? null;
        $perPage = $validated['per_page'] ?? 20;

        $query = Genre::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%$search%"])
                    ->orWhereRaw('description ILIKE ?', ["%$search%"]);
            });
        }

        return $query->paginate($perPage);
    }

    public function update(Genre $genre, array $data)
    {
        $genre->update($data);

        return $genre;
    }
}
