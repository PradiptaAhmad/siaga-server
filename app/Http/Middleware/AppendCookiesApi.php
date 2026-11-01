<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppendCookiesApi
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token_prefix = config('sanctum.token_prefix');
        $request->headers->set('Authorization', 'Bearer '.$request->cookie($token_prefix.'token'));
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
