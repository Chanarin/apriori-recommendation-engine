<?php

return [

    'proxies' => '*',

    'headers' => [
        \Illuminate\Http\Request::HEADER_CLIENT_IP    => null,
        \Illuminate\Http\Request::HEADER_CLIENT_HOST  => 'X_FORWARDED_HOST',
        \Illuminate\Http\Request::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
        \Illuminate\Http\Request::HEADER_CLIENT_PORT  => 'X_FORWARDED_PORT',
    ],

];