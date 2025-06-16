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

     /**
     * Returns a paginated list of categories with optional search filtering.
     *
     * Accessible only by authenticated librarians.
     * Supports case-insensitive partial matching on name and description (ILIKE).
     * Supports pagination with per-page values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string'
        ]);

        $search = $validated['search_value'] ?? null;
        $perPage = $validated['per_page'] ?? 20;

        $categories = Category::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('description ILIKE ?', ["%{$search}%"]);
            });
        })->paginate($perPage);

        return response()->json([
            'message' => 'Category list',
            'categories' => $categories,
        ]);
    }

     /**
     * Updates category's details.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided input attributes and returns error message if validator fails.
     * On success, updates the category's data and returns JSON response with success message.
     *
     * @param Request $request
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only(['name', 'description']);
        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

     /**
     * Updates category's icon.
     *
     * Accessible only by authenticated librarians.
     * Validates the uploaded image file.
     * If a valid image is provided, it is stored and the category's icon path is updated.
     * Returns a JSON response with a success message and the URL to the new icon.
     *
     * @param Request $request
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateIcon(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'icon' => 'required|image|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->hasFile('icon')) {

            $iconPath = $request->file('icon')->store('icons', 'public');
            $category->icon = $iconPath;
            $category->save();
        }

        return response()->json([
            'message' => 'Icon updated successfully',
            'icon_url' => $category->icon
        ]);
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
