<?php

return [
    'enabled' => env('DYNAMSOFT_ENABLED', false),
    'resources_path' => env('DYNAMSOFT_RESOURCES_PATH', '/vendor/dynamsoft/dwt'),
    'product_key' => env('DYNAMSOFT_PRODUCT_KEY'),
    'default_resolution' => (int) env('DYNAMSOFT_DEFAULT_RESOLUTION', 300),
    'default_pixel_type' => env('DYNAMSOFT_DEFAULT_PIXEL_TYPE', 'RGB'),
    'default_duplex' => filter_var(env('DYNAMSOFT_DEFAULT_DUPLEX', true), FILTER_VALIDATE_BOOL),
];
