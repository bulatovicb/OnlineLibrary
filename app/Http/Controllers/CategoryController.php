<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Creates new category for books.
     *
     * Validates the provided category's data and creates a new category if validation passes.
     * Handles icon uploads if any and store the icon.
     * Returns a JSON response with created category.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'icon' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        $iconPath = null;

        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('icons', 'public');
        }

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'icon' => $iconPath,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category

        ], 201);


    }
}
