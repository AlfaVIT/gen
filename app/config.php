<?php

return [

    'site_name' => 'Родовое древо',
    'site_tagline' => 'Личный генеалогический архив',
    'debug' => false,

    'storage_dir' => __DIR__ . '/../storage',
    'db_file' => __DIR__ . '/../storage/app.sqlite',
    'uploads_dir' => __DIR__ . '/../uploads',

    'app_email' => 'no-reply@opensite.myjino.ru',
    'app_email_name' => 'Родовое древо',

    'email_mode' => 'mail',
    'email_log' => __DIR__ . '/../storage/mail.log',
    'smtp' => [
        'host' => '',
        'port' => 465,
        'user' => '',
        'pass' => '',
        'secure' => 'ssl',
    ],

    'pwd_min_len' => 8,

    'invite_days' => 7,
    'reset_hours' => 2,

    'upload_max_bytes' => 26214400,
    'embed_base64_max' => 8388608,
    'allowed_mime' => [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'mov',
        'video/x-m4v' => 'm4v',
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/ogg' => 'ogg',
        'audio/mp4' => 'm4a',
        'audio/aac' => 'aac',
        'audio/x-flac' => 'flac',
    ],

    'accent' => '#578c3e',
    'accent_2' => '#3c6b2a',
];
