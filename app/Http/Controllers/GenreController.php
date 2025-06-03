<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class GenreController extends Controller
{
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

}
