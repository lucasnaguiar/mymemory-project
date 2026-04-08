<?php

namespace App\Http\Middleware;

use App\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return ApiResponse::error('Forbidden. Admin access required.', 403, 'forbidden');
        }

        return $next($request);
    }
}
