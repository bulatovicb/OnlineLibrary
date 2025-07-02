<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBookRequest;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
     /**
     * Displays book's data based on provided id.
     *
     * Accessible only by authenticated librarians.
     * Returns a JSON response with book data.
     * Automatically returns 404 if the author is not found.
     *
     * @param Book $book
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Book $book)
    {
        return response()->json(['book' => $book], 200);
    }

    /**
     * Displays front cover of the book.
     *
     * Accessible only by authenticated librarians.
     * Returns JSON error response if the front cover picture is not found.
     * Otherwise, returns the image path.
     *
     * @param Book $book
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function bookPicture(Book $book)
    {

        $frontCover = $book->images->firstWhere('type', 'front_cover');

        if (!$frontCover) {
            return response()->json(['error' => 'Picture not found'], 404);
        }

        return response()->json([
            'picture_url' => $frontCover->path
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
                Storage::disk('public')->delete($existingFrontCover->path);
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

    /**
     *  Creates new book.
     *
     *  Accessible only by authenticated librarians.
     *  Validates the provided book's data via CreateBookRequest.
     *  Handles image uploads if any and store the image.
     *  Attaches related models and eager load related data before returning response.
     *  Returns a JSON response with created book and its relations.
     *
     * @param CreateBookRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateBookRequest $request)
    {
        $book = Book::create($request->only([
            'name',
            'description',
            'number_of_pages',
            'number_of_copies_available',
            'isbn',
            'language',
            'script',
            'binding',
            'dimensions'
        ]));

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $imageTypes = $request->input('image_types', []);

            foreach ($images as $index => $image) {
                $path = $image->store('book_images', 'public');
                $type = $imageTypes[$index] ?? 'artwork';
                $book->images()->create([
                    'path' => $path,
                    'type' => $type,
                ]);
            }
        }

        $book->categories()->attach($request->categories);
        $book->genres()->attach($request->genres);
        $book->authors()->attach($request->authors);
        $book->publishers()->attach($request->publishers);
        $book->load(['images', 'authors', 'genres', 'categories', 'publisher' ]);

        return response()->json([
            'message' => 'Book created successfully',
            'book' => $book,
        ], 201);
    }

    /**
     * Deletes a book.
     *
     * Dispatch an event before deleting the book to delete all image files from storage related with book.
     * Deletes a book and all of its images.
     * Returns JSON response with success message.
     *
     * @param Book $book
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Book $book)
    {
        $book->images()->each(function ($image) {
            $image->delete();
        });

        $book->delete();
        return response()->json([
            'message' => 'Book deleted successfully.',
        ]);
    }

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
            'isbn' => 'sometimes|string|unique:books,isbn,' . $book->id,
            'language' => 'sometimes|string',
            'script' => ['nullable', Rule::in(Book::SCRIPTS)],
            'binding' => ['nullable', Rule::in(Book::BINDINGS)],
            'dimensions' => ['nullable', Rule::in(Book::DIMENSIONS)],
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
     * Returns a paginated list of books with optional search filtering.
     *
     * Accessible only by authenticated librarians.
     * Supports case-insensitive partial matching on first and last name (ILIKE).
     * Supports pagination with per-page values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'integer|nullable|in:20,50,100',
            'search_value' => 'string|nullable',
        ]);

        $search = $validated['search_value'] ?? null;
        $perPage = $validated['per_page'] ?? 20;

        $books = Book::with(['images', 'authors', 'genres', 'categories'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('name ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('description ILIKE ?', ["%{$search}%"]);
                });
            }
            )->paginate($perPage);

        return response()->json([
            'message' => 'Books retrieved successfully',
            'books' => $books]);
    }

}
