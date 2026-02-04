<?php

namespace Mortogo321\LaravelThaiPromptPay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string generatePayload(string $identifier, ?float $amount = null)
 * @method static string generateQRCode(string $identifier, ?float $amount = null, int $size = 300)
 * @method static string generateQRCodeBinary(string $identifier, ?float $amount = null, int $size = 300)
 * @method static array validate(string $identifier)
 * @method static array validateAmount(?float $amount)
 * @method static string|null getIdentifierType(string $identifier)
 * @method static bool isMobileNumber(string $identifier)
 * @method static bool isNationalId(string $identifier)
 * @method static bool isTaxId(string $identifier)
 * @method static bool validatePayload(string $payload)
 * @method static array getSupportedFormats()
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
