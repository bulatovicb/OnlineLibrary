<?php

namespace App\Actions;

use App\Http\Requests\Category\UpdateCategoryIconRequest;
use App\Models\Category;

class UpdateCategoryIconAction
{
    public function execute(UpdateCategoryIconRequest $request, Category $category)
    {
        if ($request->hasFile('icon')) {

            $iconPath = $request->file('icon')->store('icons', 'public');
            $category->update(['icon' => $iconPath]);
        }

        return $category;

    }
}
