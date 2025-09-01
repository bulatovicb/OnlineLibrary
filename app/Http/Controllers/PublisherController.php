<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublisherController extends Controller
{
    /**
     * Creates new publisher.
     *
     * Accessible only by authenticated librarians.
     * Validates the incoming request data and creates a new publisher if validation passes.
     * Returns a JSON response containing the created publisher data and a success message.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'logo' => 'nullable|image|max:5120',
            'address' => 'nullable|string',
            'website' => 'nullable|string',
            'email' => 'nullable|string|email|unique:publishers',
            'phone' => 'nullable|string',
            'established_year' => 'nullable|integer|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('publisher/logo', 'public');
        }

        $publisher = Publisher::create([
            'name' => $request->name,
            'logo' => $logoPath,
            'address' => $request->address,
            'website' => $request->website,
            'email' => $request->email,
            'phone' => $request->phone,
            'established_year' => $request->established_year
        ]);

        return response()->json([
            'message' => 'Publisher created successfully',
            'publisher_name' => $publisher->name,
            'publisher_id' => $publisher->id,
        ], 201);
    }

    /**
     * Display the publisher's details.
     *
     * Returns a JSON response containing publisher data.
     * Automatically returns a 404 response if the publisher is not found.
     *
     * @param Publisher $publisher
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Publisher $publisher)
    {
        return response()->json([
            'message' => 'Publisher retrieved successfully',
            'publisher_name' => $publisher->name,
            'publisher_id' => $publisher->id
        ], 200);
    }

    /**
     * Retrieve the logo URL of the publisher.
     *
     * Accessible only by authenticated librarians.
     * Returns a 404 JSON response if the logo is not available.
     * Otherwise, returns the logo URL in a JSON response.
     *
     * @param Publisher $publisher
     * @return \Illuminate\Http\JsonResponse
     */
    public function publisherLogo(Publisher $publisher)
    {
        $logo = $publisher->logo;

        if (!$logo) {
            return response()->json([
                'message' => 'Publisher logo not found'
            ], 404);
        }

        return response()->json([
            'logo_url' => $logo
        ]);
    }

    /**
     * Returns a paginated list of publishers with optional search filtering.
     *
     * Supports case-insensitive partial matching on name (ILIKE).
     * Supports pagination with per-page values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|in:20,50,100',
            'search_value' => 'nullable|string'
        ]);

        $search = $validated['search_value'] ?? null;
        $perPage = $validated['per_page'] ?? 20;

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

        return response()->json([
            'message' => 'Publishers list',
            'publishers' => $publishers,
        ]);
    }

    /**
     * Updates publisher's details.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided input attributes and returns error message if validator fails.
     * On success, updates the publisher's data and returns JSON response with success message.
     *
     * @param Request $request
     * @param Publisher $publisher
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Publisher $publisher)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'address' => 'nullable|string',
            'website' => 'nullable|string',
            'email' => 'nullable|string|email',
            'phone' => 'nullable|string',
            'established_year' => 'nullable|integer|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only([
            'name',
        ]);

        $publisher->update($data);

        return response()->json([
            'message' => 'Publisher updated successfully',
            'publisher_name' => $publisher->name,
        ]);
    }

    /**
     * Updates publisher's logo.
     *
     * Accessible only by authenticated librarians.
     * Validates the uploaded image file.
     * If a valid image is provided, it is stored and the publisher's logo path is updated.
     * Returns a JSON response with a success message and the URL to the new logo.
     *
     * @param Request $request
     * @param Publisher $publisher
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLogo(Request $request, Publisher $publisher)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('publisher/logo', 'public');
            $publisher->logo = $logoPath;
            $publisher->save();
        }

        return response()->json([
            'message' => 'Publisher logo updated successfully',
            'logo _url' => $publisher->logo
        ]);
    }

    /**
     * Deletes publisher.
     *
     * Accessible only by authenticated librarians.
     * If the publisher has an associated logo, the file will be deleted from storage.
     * Returns a JSON response with success message.
     *
     * @param Publisher $publisher
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Publisher $publisher)
    {
        if ($publisher->logo && Storage::disk('public')->exists($publisher->logo)) {
            Storage::disk('public')->delete($publisher->logo);
        }

        $publisher->delete();

        return response()->json([
            'message' => 'Publisher deleted successfully'
        ]);
    }
}
