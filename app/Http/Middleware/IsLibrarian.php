<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsLibrarian
{

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || $user->role_id !== 2) {
            return response()->json(['error' => 'Unauthorized - Librarians only'], 403);
        }

        return $next($request);
    }
}
