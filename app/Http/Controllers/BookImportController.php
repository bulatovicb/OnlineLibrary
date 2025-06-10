<?php

namespace App\Http\Controllers;

use App\Jobs\ImportBooksJob;
use Illuminate\Http\Request;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use App\Mail\AllBooksImported;

class BookImportController extends Controller
{
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
