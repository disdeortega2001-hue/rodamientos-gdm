<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class UsdRateService
{
    private const FRESH_HOURS = 6;

    private const RETRY_MINUTES = 30;

    /** Runs at most once per retry window; a failure keeps the last known rate. */
    public function refreshIfStale(): void
    {
        $bcvUrl = config('gdm.bcv_url');

        if (! $bcvUrl || Setting::get('usd_rate_auto', '1') !== '1') {
            return;
        }

        $last = (int) Setting::get('usd_rate_fetched_at', '0');
        $lastDay = $last > 0 ? now()->setTimestamp($last)->format('Y-m-d') : null;
        if ($lastDay === now()->format('Y-m-d') && now()->getTimestamp() - $last < self::FRESH_HOURS * 3600) {
            return;
        }

        if (! Cache::add('usd_rate_attempt', true, now()->addMinutes(self::RETRY_MINUTES))) {
            return;
        }

        $rate = $this->fromBcv($bcvUrl) ?? $this->fromFallback(config('gdm.bcv_fallback_url'));

        if ($rate !== null) {
            Setting::put('usd_rate', number_format($rate, 4, '.', ''));
            Setting::put('usd_rate_fetched_at', (string) now()->getTimestamp());
        }
    }

    private function fromBcv(string $url): ?float
    {
        try {
            $response = Http::timeout(4)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);

            if (! $response->successful() || ! preg_match('/id="dolar".*?<strong[^>]*>\s*([\d.,]+)\s*<\/strong>/s', $response->body(), $m)) {
                return null;
            }

            // BCV publishes tomorrow's rate in the afternoon; it applies only from its value date.
            if (preg_match('/Fecha Valor:.*?content="(\d{4}-\d{2}-\d{2})/s', $response->body(), $date) && $date[1] > now()->format('Y-m-d')) {
                return null;
            }

            return $this->valid((float) str_replace(['.', ','], ['', '.'], $m[1]));
        } catch (Throwable) {
            return null;
        }
    }

    private function fromFallback(?string $url): ?float
    {
        if (! $url) {
            return null;
        }

        try {
            $response = Http::timeout(4)->get($url);

            return $response->successful() ? $this->valid((float) $response->json('promedio')) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function valid(float $rate): ?float
    {
        return $rate > 0 ? $rate : null;
    }
}
