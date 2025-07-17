<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Creates new category for books.
     *
     * Validates the provided category's data and creates a new category if validation passes.
     * Handles icon uploads if any and store the icon.
     * Returns a JSON response with created category.
     *
     * @param CreateCategoryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateCategoryRequest $request)
    {

        $validated = $request->validated();
        $icon = $request->file('icon');

        $category = $this->categoryService->create($validated, $icon);

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
        $categories = $this->categoryService->getCategories($request->all());

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
     * @param UpdateCategoryRequest $request
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $updatedCategory = $this->categoryService->update($category, $request->validated());

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $updatedCategory
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

        $category = $this->categoryService->updateIcon($category, $request->file('icon'));
        
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
