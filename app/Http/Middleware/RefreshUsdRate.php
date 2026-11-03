<?php

namespace App\Http\Middleware;

use App\Services\UsdRateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RefreshUsdRate
{
    public function __construct(private UsdRateService $rates) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->rates->refreshIfStale();
        } catch (Throwable) {
            // The page matters more than a fresh rate.
        }

        return $next($request);
    }
}
