<?php

return [
    // SHA-256 of the one-time code for /instalar, which stops working once an administrator exists.
    'install_code_hash' => env('INSTALL_CODE_HASH'),

    // Optional alternative for hosts where gdm:setup runs from a command line.
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),

    // Sources for the official exchange rate. Without a URL the rate is only set by hand.
    'bcv_url' => env('BCV_RATE_URL'),
    'bcv_fallback_url' => env('BCV_FALLBACK_URL'),
];
