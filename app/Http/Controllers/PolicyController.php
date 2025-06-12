<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    /**
     * Lists all polices.
     *
     * Accessible only to authenticated librarians.
     * Returns a JSON response with all policies.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(Policy::all());
    }

    /**
     * Update the period of policy.
     *
     * Accessible only to authenticated librarians.
     * Validates that provided period is not 0 or negative and auto-returns 422 error if invalid.
     * Returns a JSON response with updated policy.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Policy $policy)
    {
        $request->validate([
            'period' => 'required|integer|min:1',
        ]);

        $policy->update(['period' => $request->period]);

        return response()->json([
            'message' => 'Policy updated successfully.',
            'policy' => $policy
        ]);
    }
}
