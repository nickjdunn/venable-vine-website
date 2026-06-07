<?php

return [
    'site_name' => 'Venable & Vine',
    'base_url' => '', // e.g. https://venableandvine.com (no trailing slash)
    'session_lifetime' => 7200,
    'upload_max_bytes' => 5 * 1024 * 1024,
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'smtp' => [
        'enabled' => false,
        'host' => 'mail.example.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'from_email' => 'noreply@example.com',
        'from_name' => 'Venable & Vine',
    ],
];
