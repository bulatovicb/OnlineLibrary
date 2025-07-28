<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportBooksBatchRequest;
use App\Http\Requests\ImportBooksRequest;
use App\Services\BookImportService;

class BookImportController extends Controller
{

    protected BookImportService $bookImportService;

    public function __construct(BookImportService $bookImportService)
    {
        $this->bookImportService = $bookImportService;
    }

    /**
     * Imports books based on a search query and set their availability.
     *
     * Accessible only by authenticated librarians.
     * Validates the incoming request
     * Extracts the search query and number of copies
     * Dispatches a background job to handle the book import process asynchronously.
     * Returns a JSON response confirming the import was initiated.
     *
     * @param ImportBooksRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(ImportBooksRequest $request)
    {
        $query = $request->input('query');
        $copies = $request->input('number_of_copies_available') ?? 1;

        $this->bookImportService->import($query, $copies);

        return response()->json([
            'message' => 'Books imported successfully.',
            'status' => 200,
            'query' => $query,
            'copiesAvailable' => $copies,
        ]);
    }

    /**
     * Imports multiple books in batch based on multiple search queries.
     *
     * Validates the incoming request parameters.
     * Gets the number of copies or default to 1 if not provided.
     * Creates a collection of ImportBooksJob instances for each query.
     * Dispatch all jobs as a batch and send email notification when complete.
     * Returns a JSON response confirming the batch import was started.
     * @param ImportBooksBatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importBatch(ImportBooksBatchRequest $request)
    {
        $validated = $request->validated();

        $copies = $validated['number_of_copies_available'] ?? 1;

        $this->bookImportService->importBatch(
            $validated['queries'],
            $validated['email'],
            $copies
        );

        return response()->json([
            'message' => 'Batch import started. You will be notified via email.',
            'status' => 200
        ]);
    }
}
