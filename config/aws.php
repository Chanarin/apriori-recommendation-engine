<?php

return [
    'credentials' => [
        'key'    => env('AWS_PUBLIC_KEY'),
        'secret' => env('AWS_SECRET_KEY'),
    ],
    'region'  => env('AWS_REGION'),
    'version' => 'latest',
];
