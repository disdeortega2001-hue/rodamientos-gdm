<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeDecimalComma
{
    private const FIELDS = ['price', 'amount', 'unit_cost', 'usd_rate'];

    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::FIELDS as $field) {
            $value = $request->input($field);
            if (is_string($value) && str_contains($value, ',')) {
                $request->merge([$field => str_replace(['.', ','], ['', '.'], trim($value))]);
            }
        }

        return $next($request);
    }
}
