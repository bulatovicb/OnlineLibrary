<?php

namespace App\Http\Controllers;

use App\Jobs\ImportBooksJob;
use App\Mail\AllBooksImported;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

class BookImportController extends Controller
{
    /**
     * Imports books based on a search query and set their availability.
     *
     * Accessible only by authenticated librarians.
     * Validates the incoming request
     * Extracts the search query and number of copies
     * Dispatches a background job to handle the book import process asynchronously.
     * Returns a JSON response confirming the import was initiated.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'number_of_copies_available' => 'nullable|integer|min:1',
        ]);

        $query = $request->input('query');
        $copies = $request->input('number_of_copies_available') ?? 1;

        ImportBooksJob::dispatch($query, $copies);

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
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function importBatch(Request $request)
    {
        $validated = $request->validate([
            'queries' => 'required|array|min:1',
            'queries.*' => 'string|min:2',
            'email' => 'required|email',
            'number_of_copies_available' => 'nullable|integer|min:1',
        ]);

        $copies = $validated['number_of_copies_available'] ?? 1;

        $jobs = collect($validated['queries'])->map(function ($query) use ($copies) {
            return new ImportBooksJob($query, $copies);
        });

        Bus::batch($jobs)->then(function (Batch $batch) use ($validated) {
            Mail::to($validated['email'])->send(new AllBooksImported());
        })->dispatch();

        return response()->json([
            'message' => 'Batch import started. You will be notified via email.',
            'status' => 200
        ]);
    }
}
