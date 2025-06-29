<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use Illuminate\Http\Request;
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
            'email' => 'required|string|email|unique:publishers',
            'phone' => 'nullable|string',
            'established_year' => 'required|integer|max:' . date('Y'),
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
}
