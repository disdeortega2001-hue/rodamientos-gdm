<?php

namespace App\Support;

class AppKey
{
    /** On hosts without a secrets step, derives a stable key from the database password the platform provisions. */
    public static function resolve(?string $key, ?string $databasePassword, ?string $environment): ?string
    {
        if ($key !== null && $key !== '') {
            return $key;
        }

        if ($environment !== 'production' || $databasePassword === null || $databasePassword === '') {
            return null;
        }

        return 'base64:'.base64_encode(hash('sha256', 'gdm-app-key|'.$databasePassword, true));
    }
}
