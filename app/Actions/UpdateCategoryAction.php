<?php

namespace App\Actions;

use App\Models\Category;
use Illuminate\Http\Request;

class UpdateCategoryAction
{
    public function execute(Request $request, Category $category) : Category
    {
        $data = $request->only(['name', 'description']);
        $category->update($data);

        return $category;

    }
}
