<?php

namespace App\Http\Controllers;

use App\Http\Requests\Genre\CreateGenreRequest;
use App\Http\Requests\Genre\IndexGenreRequest;
use App\Http\Requests\Genre\UpdateGenreRequest;
use App\Models\Genre;
use App\Services\GenreService;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function __construct(GenreService $createGenreService)
    {
        $this->GenreService = $createGenreService;
    }
    /**
     * Returns a paginated list of genres with optional search filtering.
     *
     * Supports case-insensitive partial matching on the name and description fields (ILIKE).
     * Supports pagination with per-page values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexGenreRequest $request)
    {
        $genres = $this->GenreService->getGenres($request);

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
    public function create(CreateGenreRequest $request)
    {
        $genre = $this->GenreService->createGenre($request);

        return response()->json([
            'message' => 'Genre created successfully.',
            'genre' => $genre,
        ], 201);
    }

    /**
     * Shows a genre.
     *
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
    public function update(UpdateGenreRequest $request, Genre $genre)
    {
        $genre = $this->GenreService->updateGenre($request, $genre);

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
