<?php

namespace App\Http\Controllers;

use App\Jobs\ImportBooksJob;
use Illuminate\Http\Request;

class BookImportController extends Controller
{
    /**
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
}
