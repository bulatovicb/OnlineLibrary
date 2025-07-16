<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{

    private BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
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
    public function create(CreateBookRequest $request)
    {
        $book = $this->bookService->create($request->validated());

        return response()->json([
            'message' => 'Book created successfully',
            'book' => $book,
        ], 201);
    }

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

       $path = $this->bookService->updateCover($book, $request->file('front_cover'));

        return response()->json([
            'message' => 'Front cover updated successfully.',
            'picture_url' => $path
        ]);
    }

    /**
     * Updates the book's data.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided input attributes and returns error message if validator fails.
     * On success, updates the book's data and returns JSON response with success message.
     *
     * @param UpdateBookRequest $request
     * @param Book $book
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        $updatedBook = $this->bookService->update($book, $request->validated());

        return response()->json([
            'message' => 'Book updated successfully.',
            'book' => $updatedBook,
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
        $books = $this->bookService->getBooks($request->all());

        return response()->json([
            'message' => 'Books retrieved successfully',
            'books' => $books
        ]);
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

}
