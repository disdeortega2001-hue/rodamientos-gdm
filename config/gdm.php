<?php

return [
    // Used once by gdm:setup on hosts without a command line; set as secrets, never in files.
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),
];
