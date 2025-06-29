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
