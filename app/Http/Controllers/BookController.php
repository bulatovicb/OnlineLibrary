<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{

    /**
     * Updates the book's data.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided input attributes and returns error message if validator fails.
     * On success, updates the book's data and returns JSON response with success message.
     *
     * @param Request $request
     * @param Book $book
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Book $book)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'number_of_pages' => 'sometimes|integer',
            'number_of_copies_available' => 'sometimes|integer',
            'isbn' => 'sometimes|string|unique:books,isbn' . $book->id,
            'language' => 'sometimes|string',
            'script' => 'sometimes|string',
            'binding' => 'sometimes|string',
            'dimensions' => 'sometimes|array',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $data = $validator->validated();
        $book->update($data);

        return response()->json([
            'message' => 'Book updated successfully.',
            'book' => $book,
        ]);

    }

    /**
     * Updates the front cover image of the given book.
     *
     * Accessible only by authenticated librarians.
     * Validates the upload image and deletes any existing front cover image.
     * Saves new front cover image in storage.
     * Return a JSON response with a success message
     *
     * @param Request $request
     * @param Book $book
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCover(Request $request, Book $book)
    {
        $validator = Validator::make($request->all(), [
            'front_cover' => 'required|image|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        if ($request->hasFile('front_cover')) {
            $coverFile = $request->file('front_cover');
            $cover_url = $coverFile->store('book_images', 'public');
            $existingFrontCover = $book->images()->where('type', 'front_cover')->first();

            if ($existingFrontCover) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($existingFrontCover->path);
                $existingFrontCover->delete();
            }
            
            $book->images()->create([
                'path' => $cover_url,
                'type' => 'front_cover'
            ]);

        }

        return response()->json([
            'message' => 'Front cover updated successfully.',
            'picture_url' => $cover_url
        ]);
    }


}
