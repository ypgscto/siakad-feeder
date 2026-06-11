<?php

namespace App\Http\Middleware;

use App\Support\ApplicationUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceConfiguredApplicationUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        ApplicationUrl::apply();

        return $next($request);
    }
}
