<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePublisherRequest;
use App\Http\Requests\UpdatePublisherRequest;
use App\Models\Publisher;
use App\Services\PublisherServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublisherController extends Controller
{
    private PublisherServices $publisherServices;

    public function __construct(PublisherServices $publisherServices)
    {
        $this->publisherServices = $publisherServices;
    }

    /**
     * Creates new publisher.
     *
     * Accessible only by authenticated librarians.
     * Validates the incoming request data and creates a new publisher if validation passes.
     * Returns a JSON response containing the created publisher data and a success message.
     *
     * @param CreatePublisherRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreatePublisherRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo');
        }

        $publisher = $this->publisherServices->create($data);

        return response()->json([
            'message' => 'Publisher created successfully',
            'publisher' => $publisher
        ], 201);
    }

    /**
     * Display the publisher's details.
     *
     * Accessible only by authenticated librarians.
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
            'publisher' => $publisher
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
     * Accessible only by authenticated librarians.
     * Supports case-insensitive partial matching on name (ILIKE).
     * Supports pagination with per-page values of 20 (default), 50, or 100.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $publishers = $this->publisherServices->getPublishers($request->all());

        return response()->json([
            'message' => 'Publishers list',
            'publishers' => $publishers
        ]);
    }

    /**
     * Updates publisher's details.
     *
     * Accessible only by authenticated librarians.
     * Validates the provided input attributes and returns error message if validator fails.
     * On success, updates the publisher's data and returns JSON response with success message.
     *
     * @param UpdatePublisherRequest $request
     * @param Publisher $publisher
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePublisherRequest $request, Publisher $publisher)
    {
        $data = $request->only([
            'name',
            'address',
            'website',
            'email',
            'phone',
            'established_year'
        ]);

        $publisher->update($data);

        $updatedPublisher = $this->publisherServices->update($publisher, $request->all());

        return response()->json([
            'message' => 'Publisher updated successfully',
            'publisher' => $updatedPublisher
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
            $publisher = $this->publisherServices->updateLogo($publisher, $request->file('logo'));
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
