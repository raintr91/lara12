<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ContentTypeVerify
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isJson()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'The Content-type value is invalid.',
        ], 406);
    }
}
