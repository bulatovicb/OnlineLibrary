<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
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
}
