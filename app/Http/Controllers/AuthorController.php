<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
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
}
