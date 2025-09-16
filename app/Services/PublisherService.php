<?php

namespace App\Services;

use App\Http\Requests\Publisher\CreatePublisherRequest;
use App\Http\Requests\Publisher\UpdatePublisherLogoRequest;
use App\Http\Requests\Publisher\UpdatePublisherRequest;
use App\Models\Publisher;
use Illuminate\Http\Request;

class PublisherService
{
    public function create(CreatePublisherRequest $request)
    {
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('publisher/logo', 'public');
        }

        return Publisher::create([
            'name' => $request->name,
            'logo' => $logoPath,
            'address' => $request->address,
            'website' => $request->website,
            'email' => $request->email,
            'phone' => $request->phone,
            'established_year' => $request->established_year
        ]);
    }

    public function publisherLogo(Publisher $publisher)
    {
        $logo = $publisher->logo;

        if (!$logo) {
            return response()->json([
                'message' => 'Publisher logo not found'
            ], 404);
        }

        return $logo;
    }

    public function getPublishers(Request $request)
    {
        $search = $request->input('search_value');
        $perPage = $request->input('per_page', 20);

        $publishers = Publisher::when($search, function ($query, $search) {
            $query->whereRaw('name ILIKE ?', ["%{$search}%"]);
        })->paginate($perPage);

        $formatted = $publishers->map(function ($publisher) {
            return [
                'publisher_name' => $publisher->name,
                'publisher_id' => $publisher->id,
            ];
        });

        $publishers->setCollection($formatted);

        return $publishers;
    }

    public function update(UpdatePublisherRequest $request, Publisher $publisher)
    {
        $data = $request->only([
            'name',
        ]);

        $publisher->update($data);

        return $publisher;
    }

    public function updateLogo(UpdatePublisherLogoRequest $request, Publisher $publisher)
    {

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('publisher/logo', 'public');
            $publisher->logo = $logoPath;
            $publisher->save();
        }

        return $publisher;
    }
}
