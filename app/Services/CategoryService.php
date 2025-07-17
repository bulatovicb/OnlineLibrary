<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryService
{
    public function create(array $data, ?UploadedFile $icon = null): Category
    {
        $iconPath = null;

        if ($icon) {
            $iconPath = $icon->store('icons', 'public');
        }
        return Category::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'icon' => $iconPath,
        ]);
    }

    public function getCategories(array $data)
    {
        $validated = Validator::make($data, [
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string'
        ])->validate();

        $search = $validated['search_value'] ?? null;
        $perPage = $validated['per_page'] ?? 20;

        $query = Category::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('description ILIKE ?', ["%{$search}%"]);
            });

        }
        return $query->paginate($perPage);
    }

    public function update(Category $category, array $data)
    {
        $category->update($data);

        return $category;
    }

    public function updateIcon(Category $category, UploadedFile $icon): Category
    {
        if ($category->icon) {
            Storage::disk('public')->delete($category->icon);
        }

        $iconPath = $icon->store('icons', 'public');

        $category->icon = $iconPath;
        $category->save();

        return $category;
    }
}



