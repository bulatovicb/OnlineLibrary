<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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
            'name' => 'required|string|unique:categories,name|max:500',
            'description' => 'required|string|max:500',
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

     /**
     * Deletes category.
     *
     * Accessible only by authenticated librarians.
     * If the category has an associated icon, the file will be deleted from storage.
     * Returns a JSON response with success message.
     *
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Category $category)
    {

        if ($category->icon && Storage::disk('public')->exists($category->icon)) {
            Storage::disk('public')->delete($category->icon);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted'
        ]);
    }
}
