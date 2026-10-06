<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyScraperToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.scraper.token');

        abort_unless(
            $expected !== '' && hash_equals($expected, (string) $request->bearerToken()),
            401,
            'Unauthorized.'
        );

        return $next($request);
    }
}