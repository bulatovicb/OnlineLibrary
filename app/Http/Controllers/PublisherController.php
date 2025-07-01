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
            'email' => 'sometimes|string|email',
            'phone' => 'nullable|string',
            'established_year' => 'nullable|integer|max:' . date('Y'),
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only([
            'name',
            'address',
            'website',
            'email',
            'phone',
            'established_year'
        ]);

        $publisher->update($data);

        return response()->json([
            'message' => 'Publisher updated successfully',
            'publisher' => $publisher
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
}
