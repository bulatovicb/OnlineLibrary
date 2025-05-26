<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Shows a category.
     *
     * Accessible only by authenticated librarians.
     * Returns a JSON response with category data.
     * Automatically returns 404 if the category is not found.
     *
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Category $category)
    {
        return response()->json([
            'category' => $category,
        ], 200);
    }

    /**
     * Shows a category icon.
     *
     * Accessible only by authenticated librarians.
     * Returns JSON error response if the category icon is not found.
     * Otherwise, returns the icon path.
     *
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoryIcon(Category $category)
    {
        if (!$category->icon) {
            return response()->json([
                'message' => 'Category icon not found',
            ], 404);
        }

        return response()->json([
            'icon_url' => $category->icon,
        ]);
    }
}
