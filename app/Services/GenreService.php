<?php

namespace App\Services;

use App\Http\Requests\Genre\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreService
{
    public function createGenre($request)
    {
        return Genre::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);
    }

    public function getGenres(Request $request)
    {
        $query = Genre::query();

        if ($request->filled('search_value')) {
            $search = $request->search_value;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%$search%"])
                    ->orWhereRaw('description ILIKE ?', ["%$search%"]);
            });
        }

        return $query->paginate($request->per_page ?? 20);
    }

    public function updateGenre(UpdateGenreRequest $request, Genre $genre)
    {
        $data = $request->only(['name', 'description']);
        $genre->update($data);

        return $genre;

    }
}
