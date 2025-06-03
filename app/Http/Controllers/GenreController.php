<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;


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
