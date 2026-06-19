<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()  || !$request->user() ->tokenCan($role)) 
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk role ini',
                ],403);
            }

            return $next($request);
    }
}
