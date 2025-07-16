<?php

namespace App\Services;

use App\Models\Author;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthorService
{
    private function deletePictureIfExists(Author $author): void
    {
        if ($author->picture && Storage::disk('public')->exists($author->picture)) {
            Storage::disk('public')->delete($author->picture);
        }
    }

    public function create(array $data): Author
    {
        if (isset($data['picture']) && $data['picture'] instanceof UploadedFile) {
            $data['picture'] = $data['picture']->store('picture', 'public');
        }

        return Author::create($data);
    }

    public function getAuthors(array $data)
    {
        $validated = Validator::make($data, [
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string',
        ])->validate();

        $query = Author::query();

        if (!empty($validated['search_value'])) {
            $search = $validated['search_value'];
            $query->where(function ($q) use ($search) {
                $q->whereRaw('first_name ILIKE ?', ["%$search%"])
                    ->orWhereRaw('last_name ILIKE ?', ["%$search%"]);
            });
        }

        $perPage = $validated['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    public function update(Author $author, array $data)
    {
        $author->update($data);

        return $author;
    }

    public function updatePicture(Author $author, UploadedFile $picture): Author
    {
        $this->deletePictureIfExists($author);

        $author->picture = $picture->store('picture', 'public');
        $author->save();

        return $author;
    }

    public function delete(Author $author)
    {
        $this->deletePictureIfExists($author);

        $author->delete();
    }
}

