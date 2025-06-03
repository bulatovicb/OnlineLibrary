<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GenreController extends Controller
{
    /**
     * Creates a new genre for books.
     *
     * Validates the provided genre data and creates a new genre if validation passes.
     * Returns a JSON response with created genre.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string|max:500|unique:genres,name',
            'description' => 'required|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $genre = Genre::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Genre created successfully.',
            'genre' => $genre,
        ], 201);
    }

     /**
     * Shows a genre.
     *
     * Accessible only by authenticated librarians.
     * Returns a JSON response with the genre data.
     * Automatically returns 404 if the genre is not found.
     *
     * @param Genre $genre
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Genre $genre)
    {
        return response()->json([
            'genre' => $genre
        ], 200);
    }
}
