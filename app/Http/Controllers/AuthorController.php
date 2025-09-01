<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class AuthorController extends Controller
{
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

        $picturePath = null;
        if ($request->hasFile('picture')) {
            $picturePath = $request->file('picture')->store('picture', 'public');
        }

        $author = Author::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'biography' => $request->biography,
            'picture' => $picturePath,
        ]);

        return response()->json([
            'message' => 'Author created successfully',
            'author' => $author
        ], 201);
    }

    /**
     * Display author's profile data based on provided id.
     *
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
     *  Supports case-insensitive partial matching on first and last name (ILIKE).
     *  Supports pagination with per-page values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'per_page' => 'nullable|integer|in:20,50,100',
                'search_value' => 'nullable|string',
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $query = Author::query();

        if ($request->filled('search_value')) {
            $search = $request->search_value;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('first_name ILIKE ?', ["%$search%"])
                    ->orWhereRaw('last_name ILIKE ?', ["%$search%"]);
            });
        }

        $perPage = $request->per_page ?? 20;
        $authors = $query->paginate($perPage);

        return response()->json([
            'message' => 'Authors retrieved successfully',
            'data' => $authors
        ]);
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

        $data = $request->only(['first_name', 'last_name', 'biography']);
        $author->update($data);

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
        $validator = Validator::make($request->all(), [
            'picture' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        if ($request->hasFile('picture')) {
            $picturePath = $request->file('picture')->store('picture', 'public');
            $author->picture = $picturePath;
            $author->save();
        }

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
        if ($author->picture && Storage::disk('public')->exists($author->picture)) {
            Storage::disk('public')->delete($author->picture);
        }
        $author->delete();

        return response()->json([
            'message' => 'Author deleted successfully',
        ]);
    }
}
