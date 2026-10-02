<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadUserAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->user()?->loadMissing(['roles.permissions', 'student', 'teacher', 'guardian']);

        return $next($request);
    }
}
