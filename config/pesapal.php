<?php

return [
    'base_url' => env('PESAPAL_BASE_URL', 'https://pay.pesapal.com/v3'),
    'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
    'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
    'notification_id' => env('PESAPAL_NOTIFICATION_ID', '34f2ce63-9c4c-430d-adb8-dbba55243d85'),
];