<?php

namespace Mortogo321\LaravelThaiPromptPay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string generatePayload(string $identifier, ?float $amount = null)
 * @method static string generateQRCode(string $identifier, ?float $amount = null, int $size = 300)
 * @method static string generateQRCodeBinary(string $identifier, ?float $amount = null, int $size = 300)
 *
 * @see \Mortogo321\LaravelThaiPromptPay\PromptPayQR
 */
class PromptPay extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'promptpay';
    }
}
