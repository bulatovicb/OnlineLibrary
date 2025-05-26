<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
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
