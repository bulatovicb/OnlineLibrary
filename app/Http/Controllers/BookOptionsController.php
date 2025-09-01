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
            'scripts' => collect(Book::SCRIPTS)
                ->map(fn ($value) => [
                    'value' => $value,
                    'label' => __('book.scripts.' . $value),
                ])
                ->values(),

            'bindings' => collect(Book::BINDINGS)
                ->map(fn ($value) => [
                    'value' => $value,
                    'label' => __('book.bindings.' . $value),
                ])
                ->values(),

            'dimensions' => collect(Book::DIMENSIONS)
                ->map(fn ($value) => [
                    'value' => $value,
                    'label' => __('book.dimensions.' . $value),
                ])
                ->values(),
        ]);
    }
}
