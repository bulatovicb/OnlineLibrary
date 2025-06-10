<?php

namespace App\Http\Controllers;

use App\Jobs\ImportBooksJob;
use Illuminate\Http\Request;

class BookImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->input('query');

        ImportBooksJob::dispatch($query);

        return response()->json([
            'message' => 'Books imported successfully.',
            'status' => 200,
            'query' => $query,
        ]);
    }
}
