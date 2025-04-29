<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class IsLibrarian
{

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();


        if (!$user || $user-> role !== 'librarian') {
            return response()->json(['error' => 'Unauthorized - Librarians only'], 403);
        }

        return $next($request);
    }
}
