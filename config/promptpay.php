<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default QR Code Size
    |--------------------------------------------------------------------------
    |
    | This value determines the default size (in pixels) for generated
    | PromptPay QR codes. You can override this when calling the
    | generateQRCode method.
    |
    */
    'qr_size' => env('PROMPTPAY_QR_SIZE', 300),

    /*
    |--------------------------------------------------------------------------
    | Default Merchant Identifier
    |--------------------------------------------------------------------------
    |
    | You can set a default phone number or national ID for your merchant
    | account. This will be used if no identifier is provided when
    | generating QR codes.
    |
    */
    'default_identifier' => env('PROMPTPAY_DEFAULT_IDENTIFIER', null),
];
