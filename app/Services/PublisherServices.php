<?php

namespace App\Services;

use App\Models\Publisher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublisherServices
{
    public function create (array $data) : Publisher
    {
        if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
            $data['logo'] = $data['logo']->store('publisher/logo', 'public');
        }

        return Publisher::create([
            'name' => $data['name'],
            'logo' => $data['logo'] ?? null,
            'address' => $data['address'],
            'website' => $data['website'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'established_year' => $data['established_year']
        ]);
    }

    public function getPublishers(array $data)
    {
        $validated = Validator::make($data, [
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string'
        ])->validate();

        $search = $validated['search_value'] ?? null;
        $perPage = $validated['per_page'] ?? 20;

        $query = Publisher::query();

        if ($search) {
            $query->whereRaw('name ILIKE ?', ["%{$search}%"]);
        }

        return $query->paginate($perPage);
    }

    public function update(Publisher $publisher, array $data) : Publisher
    {
        $publisher->update($data);

        return $publisher;
    }

    public function updateLogo(Publisher $publisher, UploadedFile $logo) : Publisher
    {
        if ($publisher->logo && Storage::disk('public')->exists($publisher->logo)) {
            Storage::disk('public')->delete($publisher->logo);
        }

        $logoPath = $logo->store('publisher/logo', 'public');

        $publisher->logo = $logoPath;
        $publisher->save();

        return $publisher;
    }
}
