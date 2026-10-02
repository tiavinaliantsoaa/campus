<?php

return [

    'name' => env('CAMPUS_NAME', 'ESCM Business School'),

    'short_name' => env('CAMPUS_SHORT_NAME', 'ESCM'),

    'city' => env('CAMPUS_CITY', 'Antananarivo'),

    'country' => env('CAMPUS_COUNTRY', 'Madagascar'),

    'tagline' => 'Un campus, une équipe.',

    'currency' => 'Ar',

    'admin_email' => env('CAMPUS_ADMIN_EMAIL', 'direction@escm.mg'),

    'admin_name' => env('CAMPUS_ADMIN_NAME', 'Administration ESCM'),

    'admin_password' => env('CAMPUS_ADMIN_PASSWORD'),

    'seed_demo' => (bool) env('SEED_DEMO', false),

];
