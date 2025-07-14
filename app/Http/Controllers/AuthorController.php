<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Services\AuthorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class AuthorController extends Controller
{
    private AuthorService $authorService;

    public function __construct(AuthorService $authorService)
    {
        $this->authorService = $authorService;
    }

    /**
     * Creates new author.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided profile data and creates a new user if validation passes.
     * Returns a JSON response with the author data and a success message.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'biography' => 'nullable|string',
            'picture' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $author = $this->authorService->create($request->all());

        return response()->json([
            'message' => 'Author created successfully',
            'author' => $author
        ], 201);
    }

    /**
     * Display author's profile data based on provided id.
     *
     * Accessible only by authenticated librarians.
     * Returns a JSON response with authors data.
     * Automatically returns 404 if the author is not found.
     *
     * @param Author $author
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Author $author)
    {
        return response()->json(['author' => $author], 200);
    }

    /**
     * Returns the picture of an author based on the provided id.
     *
     * Accessible only by authenticated librarians.
     * Returns JSON error response if the author or the picture is not found.
     * Otherwise, returns the image file.
     *
     * @param Author $author
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function authorsPicture(Author $author)
    {
        if (!$author->picture) {
            return response()->json(['error' => 'Picture not found'], 404);
        }

        return response()->file(storage_path('app/public/' . $author->picture));

    }

    /**
     *  Return a paginated list of authors with optional search filtering.
     *
     *  Accessible only by authenticated librarians.
     *  Supports case-insensitive partial matching on first and last name (ILIKE).
     *  Supports pagination with per-page values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $authors = $this->authorService->getAuthors($request->all());

            return response()->json([
                'message' => 'Authors retrieved successfully',
                'data' => $authors
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Updates the author's data.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided input attributes and returns error message if validator fails.
     * On success, updates the author's data and returns JSON response with success message.
     *
     * @param Request $request
     * @param Author $author
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Author $author)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'biography' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $author = $this->authorService->update($author, $request->all());

        return response()->json([
            'message' => 'Author updated successfully',
            'author' => $author
        ]);
    }

    /**
     * Updates the author's picture.
     *
     * Accessible only by authenticated librarians.
     * Validates the uploaded image file.
     * If a valid image is provided, it is stored and the author's picture path is updated.
     * Returns a JSON response with a success message and the URL to the new picture.
     *
     * @param Request $request
     * @param Author $author
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePicture(Request $request, Author $author)
    {
        $validated = $request->validate([
            'picture' => 'required|image|max:5120',
        ]);

        $author = $this->authorService->updatePicture($author, $validated['picture']);

        return response()->json([
            'message' => 'Picture updated successfully',
            'picture_url' => $author->picture
        ]);
    }

    /**
     * Deletes author.
     *
     * Accessible only by authenticated librarians.
     * Returns a JSON response with success message.
     *
     * @param Author $author
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Author $author)
    {
        $this->authorService->delete($author);

        return response()->json([
            'message' => 'Author deleted successfully',
        ]);
    }
}
