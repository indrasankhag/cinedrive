<?php
// === FIXED CONFIGURATION FILE ===
return [
    // ✅ CORRECTED: Use the database that has the videos table
    'db' => [
        'dsn'  => 'mysql:host=localhost;dbname=sinhbtve_sinhalamovies;charset=utf8mb4',
        'user' => 'sinhbtve_smovies_user',
        'pass' => 'Gaiya#@!$@!',
    ],

    // Telegram Bot Token (optional - only needed for Bot API fallback)
    'bot_token' => '8565192935:AAE5gYSV8b-JwreWxr1ahd779zVU4fQY3Sg',

    // Telegram API Credentials
    'api_id'   => 27813881,
    'api_hash' => 'd0b6bd1320810ff20ec7869bf7fed8f7',

    // Security secret for signed URLs
    'hmac_secret' => 'SinhalaMoviesCDN_SecretKey_9s8d7f6$%ASD123!@#',

    // Your domain
    'base_url' => 'https://sinhalamovies.web.lk',
];