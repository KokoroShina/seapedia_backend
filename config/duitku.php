<?php

return [
    'merchant_code' => env('DUITKU_MERCHANT_CODE'),
    'api_key'       => env('DUITKU_API_KEY'),
    'base_url'      => env('DUITKU_BASE_URL', 'https://sandbox.duitku.com/webapi/api/merchant'),
    'callback_url'  => env('DUITKU_CALLBACK_URL'),
    'return_url'    => env('DUITKU_RETURN_URL'),
];