<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsLibrarian
{
    /**
     * Middleware to ensure the authenticated user is librarian.
     * Returns a JSON error response if the user is not authenticated or is not librarian.
     *
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\JsonResponse|mixed
     */

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->isLibrarian()) {
            return response()->json(['error' => 'Unauthorized - Librarians only'], 403);
        }

        return $next($request);
    }
}
