<?php

namespace App\Actions;
use App\Models\Category;
use Illuminate\Http\Request;

class IndexCategoryAction
{
    public function execute(Request $request)
    {
        $search = $validated['search_value'] ?? null;
        $perPage = $validated['per_page'] ?? 20;

        return Category::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('description ILIKE ?', ["%{$search}%"]);
            });
        })->paginate($perPage);
    }
}
