<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookOptionsController extends Controller
{
    /**
     * Return available static book options.
     *
     * @return JsonResponse
     */
    public function index() : JsonResponse
    {
        return response()->json([
            'scripts' => Book::SCRIPTS,
            'bindings' => Book::BINDINGS,
            'dimensions' => Book::DIMENSIONS,
        ]);
    }
}
