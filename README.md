# Laravel Thai PromptPay

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mortogo321/laravel-thai-promptpay.svg?style=flat-square)](https://packagist.org/packages/mortogo321/laravel-thai-promptpay)
[![Total Downloads](https://img.shields.io/packagist/dt/mortogo321/laravel-thai-promptpay.svg?style=flat-square)](https://packagist.org/packages/mortogo321/laravel-thai-promptpay)
[![GitHub License](https://img.shields.io/github/license/mortogo321/laravel-thai-promptpay.svg?style=flat-square)](https://github.com/mortogo321/laravel-thai-promptpay/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/mortogo321/laravel-thai-promptpay.svg?style=flat-square)](https://packagist.org/packages/mortogo321/laravel-thai-promptpay)
[![Laravel Version](https://img.shields.io/badge/Laravel-10.x%20|%2011.x%20|%2012.x-red?style=flat-square)](https://laravel.com)
[![GitHub Stars](https://img.shields.io/github/stars/mortogo321/laravel-thai-promptpay?style=flat-square)](https://github.com/mortogo321/laravel-thai-promptpay/stargazers)

A Laravel package for generating Thai PromptPay QR codes following the **BOT (Bank of Thailand) Thai QR Payment Standard**. Supports phone numbers, National ID, Tax ID, and e-Wallet with full validation.

## Supported Formats

| Type | Supported | Sub-tag | Format | Example |
|------|-----------|---------|--------|---------|
| Mobile Phone | ✅ | 01 | 10 digits (06/08/09) | `0812345678` |
| National ID | ✅ | 02 | 13 digits | `1234567890123` |
| Tax ID | ✅ | 02 | 13 digits | `0123456789012` |
| e-Wallet | ✅ | 03 | 15 digits | `123456789012345` |
| Bank Account | ❌ | - | NOT SUPPORTED | BOT spec: reserved |

> **Note:** Bank account numbers are NOT supported per BOT specification (marked as "reserved" - no Thai banks have implemented this).

## Features

- Generate PromptPay QR codes compliant with BOT Thai QR Payment Standard
- Support for phone numbers, National ID, Tax ID, and e-Wallet
- **Validation methods** for identifier and amount checking
- **Thai National ID checksum validation**
- Support for fixed amount and open amount payments
- Returns QR code as data URI or binary PNG
- Built-in AJAX/API endpoints for dynamic generation
- Works with Axios, Fetch, Vue.js, React, and any frontend framework
- Laravel auto-discovery support

## Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x

## Installation

```bash
composer require mortogo321/laravel-thai-promptpay
```

The package will automatically register its service provider.

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=promptpay-config
```

## Basic Usage

### Using Facade

```php
use Mortogo321\LaravelThaiPromptPay\Facades\PromptPay;

// Generate QR code with phone number
$qrCode = PromptPay::generateQRCode('0812345678');

// Generate QR code with fixed amount
$qrCode = PromptPay::generateQRCode('0812345678', 100.50);

// Generate QR code with National ID
$qrCode = PromptPay::generateQRCode('1234567890123', 250.00);

// Generate QR code with custom size (default: 300px)
$qrCode = PromptPay::generateQRCode('0812345678', 100.00, 400);

// Generate payload string only
$payload = PromptPay::generatePayload('0812345678', 100.00);

// Generate QR code as binary PNG
$binary = PromptPay::generateQRCodeBinary('0812345678', 100.00);
```

### Using Dependency Injection

```php
use Mortogo321\LaravelThaiPromptPay\PromptPayQR;

class PaymentController extends Controller
{
    public function generateQR(PromptPayQR $promptpay)
    {
        $qrCode = $promptpay->generateQRCode('0812345678', 150.00);
        return view('payment', compact('qrCode'));
    }
}
```

## Validation (v2.0.4+)

### Validate Identifier

```php
use Mortogo321\LaravelThaiPromptPay\PromptPayQR;

$qr = new PromptPayQR();

// Validate without generating QR
$result = $qr->validate('0812345678');
// Returns: ['valid' => true, 'type' => 'mobile', 'formatted' => '0066812345678', 'error' => null]

$result = $qr->validate('invalid');
// Returns: ['valid' => false, 'type' => null, 'formatted' => null, 'error' => 'Invalid PromptPay ID format...']
```

### Validate Amount

```php
$qr->validateAmount(100.50);      // ['valid' => true, 'error' => null]
$qr->validateAmount(-50);         // ['valid' => false, 'error' => 'Amount cannot be negative']
$qr->validateAmount(9999999999);  // ['valid' => false, 'error' => 'Amount exceeds maximum...']
```

### Type Detection

```php
// Get identifier type
$qr->getIdentifierType('0812345678');     // 'mobile'
$qr->getIdentifierType('1234567890123');  // 'tax_id'

// Specific type checks
$qr->isMobileNumber('0812345678');        // true (validates 06/08/09 prefix)
$qr->isMobileNumber('0212345678');        // false (02 is landline)
$qr->isNationalId('3320101323923');       // true (with checksum validation)
$qr->isTaxId('1234567890123');            // true
```

### Get Supported Formats

```php
$formats = PromptPayQR::getSupportedFormats();
// Returns array with all supported formats, sub-tags, and examples
```

## Display QR Code in Blade

```blade
<div class="payment-qr">
    <h3>Scan to Pay</h3>
    <img src="{{ $qrCode }}" alt="PromptPay QR Code">
    <p>Amount: ฿{{ number_format($amount, 2) }}</p>
</div>
```

## AJAX/API Usage

### Available API Endpoints

#### Generate QR Code
**POST** `/promptpay/generate`

```javascript
axios.post('/promptpay/generate', {
    identifier: '0812345678',  // Phone, National ID, or e-Wallet
    amount: 100.50,            // Optional
    size: 300                  // Optional (default: 300)
})
.then(response => {
    document.getElementById('qr-image').src = response.data.qr_code;
});
```

#### Get Payload Only
**POST** `/promptpay/payload`

```javascript
axios.post('/promptpay/payload', {
    identifier: '0812345678',
    amount: 100.50
});
```

#### Download QR Code
**POST** `/promptpay/download`

```javascript
axios.post('/promptpay/download', {
    identifier: '0812345678',
    amount: 100.50
}, { responseType: 'blob' });
```

## Identifier Format Details

### Phone Numbers
- `0812345678` - Thai mobile (10 digits starting with 06/08/09)
- `812345678` - Without leading 0 (9 digits)
- `66812345678` - International format
- `0066812345678` - PromptPay format

### National ID / Tax ID
- `1234567890123` - 13-digit number
- Includes checksum validation for National ID

### e-Wallet
- `123456789012345` - 15-digit e-Wallet ID

## API Reference

### `validate(string $identifier): array`
Validate identifier without generating QR.

### `validateAmount(?float $amount): array`
Validate payment amount.

### `getIdentifierType(string $identifier): ?string`
Get identifier type ('mobile', 'tax_id', 'ewallet', or null).

### `isMobileNumber(string $identifier): bool`
Check if valid Thai mobile number (06/08/09).

### `isNationalId(string $identifier): bool`
Check if valid National ID with checksum validation.

### `generatePayload(string $identifier, ?float $amount = null): string`
Generate PromptPay EMV QR code payload string.

### `generateQRCode(string $identifier, ?float $amount = null, int $size = 300): string`
Generate QR code image as data URI.

### `generateQRCodeBinary(string $identifier, ?float $amount = null, int $size = 300): string`
Generate QR code as binary PNG data.

### `getSupportedFormats(): array`
Get list of all supported identifier formats.

## Technical Specification

This package follows the **BOT (Bank of Thailand) Thai QR Payment Standard**:

- **AID:** `A000000677010111` (PromptPay Application ID)
- **Sub-tag 01:** Mobile phone number (`0066XXXXXXXXX` format)
- **Sub-tag 02:** National ID / Tax ID (13 digits)
- **Sub-tag 03:** e-Wallet ID (15 digits)
- **CRC:** CRC16-CCITT checksum (EMVCo standard)

Reference: [BOT Thai QR Payment Specification](https://www.bot.or.th/content/dam/bot/fipcs/documents/FPG/2562/ThaiPDF/25620084.pdf)

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License

## Credits

- [Mor](https://github.com/mortogo321)
- Built with [endroid/qr-code](https://github.com/endroid/qr-code)

## Support

If you discover any issues, please create an issue on [GitHub](https://github.com/mortogo321/laravel-thai-promptpay/issues).
