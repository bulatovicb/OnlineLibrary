<?php

namespace App\Actions;

use App\Models\Category;
use Illuminate\Http\Request;

class StoreCategoryAction
{
    public function execute(Request $request) : Category
    {
        $iconPath = null;

        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('icons', 'public');
        }

        return Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'icon' => $iconPath,
        ]);
    }
}
