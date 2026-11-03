<?php

return [
    // SHA-256 of the one-time code for /instalar, which stops working once an administrator exists.
    'install_code_hash' => env('INSTALL_CODE_HASH'),

    // Optional alternative for hosts where gdm:setup runs from a command line.
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),
];
