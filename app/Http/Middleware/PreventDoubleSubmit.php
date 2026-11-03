<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PreventDoubleSubmit
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->input('form_token');

        if (is_string($token) && $token !== '' && ! Cache::add('form_token:'.hash('sha256', $token), true, now()->addDay())) {
            return back()->with('status', 'Esa acción ya se había registrado.');
        }

        return $next($request);
    }
}
