<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use App\Services\GenreService;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    private GenreService $genreService;

    public function __construct(genreService $genreService)
    {
        $this->genreService = $genreService;
    }

    /**
     * Creates new Genre.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided Genre's data.
     * Returns a JSON response with created genre.
     *
     * @param CreateGenreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateGenreRequest $request)
    {
        $validated = $request->validated();

        $genre = $this->genreService->create($validated);

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
        $genres = $this->genreService->getGenres($request->all());

        return response()->json([
            'message' => "Success",
            'genres' => $genres
        ]);
    }

    /**
     * Updates the genre's details.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided input attributes and returns an error message if validation fails.
     * On success, updates the genre's data and returns a JSON response with a success message.
     *
     * @param UpdateGenreRequest $request
     * @param Genre $genre
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateGenreRequest $request, Genre $genre)
    {
        $updatedGenre = $this->genreService->update($genre, $request->all());

        return response()->json([
            'message' => 'Genre updated successfully.',
            'genre' => $updatedGenre,
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
