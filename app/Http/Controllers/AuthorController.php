<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


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

        if (!Auth::check() || !Auth::user()->isLibrarian()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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
}
