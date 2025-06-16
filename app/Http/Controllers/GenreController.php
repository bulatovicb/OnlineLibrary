<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GenreController extends Controller
{
    /**
     * Returns a paginated list of genres with optional search filtering.
     *
     * Accessible only to authenticated librarians.
     * Supports case-insensitive partial matching on the name and description fields (ILIKE).
     * Supports pagination with per-page values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        request()->validate([
            'per_page' => 'integer|nullable|in:20,50,100',
            'search_value' => 'string|nullable',
        ]);

        $query = Genre::query();

        if ($request->filled('search_value')) {
            $search = $request->search_value;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%$search%"])
                    ->orWhereRaw('description ILIKE ?', ["%$search%"]);
            });
        }

        $perPage = $request->per_page ?? 20;
        $genres = $query->paginate($perPage);

        return response()->json([
            'message' => "Success",
            'genres' => $genres
        ]);
    }

    /**
     * Creates new book.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided book's data via CreateBookRequest.
     * Handles image uploads if any and store the image.
     * Attaches related models and eager load related data before returning response.
     * Returns a JSON response with created book and its relations.
     *
     * @param CreateBookRequest $request
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
  
     /**
     * Updates the genre's details.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided input attributes and returns an error message if validation fails.
     * On success, updates the genre's data and returns a JSON response with a success message.
     *
     * @param Request $request
     * @param Genre $genre
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Genre $genre)
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'sometimes|string|max:500',
            'description' => 'nullable|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only(['name', 'description']);
        $genre->update($data);

        return response()->json([
            'message' => 'Genre updated successfully.',
            'genre' => $genre,
        ]);
    }
  
     /**
     * Deletes a genre.
     *
     * Accessible only by authenticated librarians.
     * Returns a JSON response with a success message.
     *
     * @param Genre $genre
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Genre $genre)
    {
        $genre->delete();
        return response()->json([
            'message' => 'Genre deleted successfully.',
        ]);
    }
}
