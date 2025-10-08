# Laravel Thai PromptPay

A Laravel package for generating Thai PromptPay QR codes for payment processing. This package supports generating QR codes for both Thai phone numbers and National ID numbers, with optional payment amounts.

## Features

- Generate PromptPay QR codes for Thai phone numbers
- Generate PromptPay QR codes for Thai National ID
- Support for fixed amount payments
- Support for open amount (user enters amount)
- Returns QR code as data URI or binary PNG
- **Built-in AJAX/API endpoints for dynamic generation**
- **Works with Axios, Fetch, Vue.js, React, and any frontend framework**
- Beautiful example page included
- Laravel auto-discovery support
- Fully compliant with Thai PromptPay EMV QR Code specification

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x

## Installation

Install the package via Composer:

```bash
composer require mortogo321/laravel-thai-promptpay
```

The package will automatically register its service provider.

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=promptpay-config
```

## Usage

### Using Facade

```php
use Mortogo321\LaravelThaiPromptPay\Facades\PromptPay;

// Generate QR code with phone number (data URI)
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

### Display QR Code in Blade

```blade
<div class="payment-qr">
    <h3>Scan to Pay</h3>
    <img src="{{ $qrCode }}" alt="PromptPay QR Code">
    <p>Amount: ฿{{ number_format($amount, 2) }}</p>
</div>
```

### Download QR Code as Image

```php
use Mortogo321\LaravelThaiPromptPay\Facades\PromptPay;

Route::get('/qr-download', function () {
    $binary = PromptPay::generateQRCodeBinary('0812345678', 100.00);

    return response($binary)
        ->header('Content-Type', 'image/png')
        ->header('Content-Disposition', 'attachment; filename="promptpay-qr.png"');
});
```

## AJAX/API Usage

The package provides built-in API endpoints for generating QR codes dynamically via AJAX/Axios.

### Available API Endpoints

#### 1. Generate QR Code
**POST** `/promptpay/generate`

```javascript
axios.post('/promptpay/generate', {
    identifier: '0812345678',  // Phone or National ID
    amount: 100.50,            // Optional
    size: 300                  // Optional (default: 300)
})
.then(response => {
    // response.data.qr_code contains the data URI
    document.getElementById('qr-image').src = response.data.qr_code;
})
.catch(error => {
    console.error(error.response.data.message);
});
```

**Response:**
```json
{
    "success": true,
    "qr_code": "data:image/png;base64,iVBORw0KG...",
    "identifier": "0812345678",
    "amount": 100.50
}
```

#### 2. Get Payload Only
**POST** `/promptpay/payload`

```javascript
axios.post('/promptpay/payload', {
    identifier: '0812345678',
    amount: 100.50
})
.then(response => {
    console.log(response.data.payload);
});
```

#### 3. Download QR Code
**POST** `/promptpay/download`

```javascript
axios.post('/promptpay/download', {
    identifier: '0812345678',
    amount: 100.50,
    size: 500
}, {
    responseType: 'blob'
})
.then(response => {
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'promptpay-qr.png';
    link.click();
});
```

### Complete AJAX Example

```html
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
    <input type="text" id="phone" placeholder="0812345678">
    <input type="number" id="amount" placeholder="100.00">
    <button onclick="generateQR()">Generate QR</button>

    <div id="result">
        <img id="qr-image" style="display:none;">
    </div>

    <script>
        // Setup CSRF token
        axios.defaults.headers.common['X-CSRF-TOKEN'] =
            document.querySelector('meta[name="csrf-token"]').content;

        async function generateQR() {
            try {
                const response = await axios.post('/promptpay/generate', {
                    identifier: document.getElementById('phone').value,
                    amount: document.getElementById('amount').value || null
                });

                const img = document.getElementById('qr-image');
                img.src = response.data.qr_code;
                img.style.display = 'block';
            } catch (error) {
                alert(error.response.data.message);
            }
        }
    </script>
</body>
</html>
```

### Vue.js Example

```vue
<template>
  <div>
    <input v-model="identifier" placeholder="Phone or ID">
    <input v-model="amount" type="number" placeholder="Amount">
    <button @click="generateQR">Generate</button>

    <img v-if="qrCode" :src="qrCode" alt="PromptPay QR">
  </div>
</template>

<script>
export default {
  data() {
    return {
      identifier: '',
      amount: null,
      qrCode: null
    }
  },
  methods: {
    async generateQR() {
      try {
        const response = await axios.post('/promptpay/generate', {
          identifier: this.identifier,
          amount: this.amount
        });
        this.qrCode = response.data.qr_code;
      } catch (error) {
        alert(error.response.data.message);
      }
    }
  }
}
</script>
```

### React Example

```jsx
import { useState } from 'react';
import axios from 'axios';

function PromptPayGenerator() {
  const [identifier, setIdentifier] = useState('');
  const [amount, setAmount] = useState('');
  const [qrCode, setQrCode] = useState(null);

  const generateQR = async () => {
    try {
      const response = await axios.post('/promptpay/generate', {
        identifier,
        amount: amount || null
      });
      setQrCode(response.data.qr_code);
    } catch (error) {
      alert(error.response.data.message);
    }
  };

  return (
    <div>
      <input
        value={identifier}
        onChange={(e) => setIdentifier(e.target.value)}
        placeholder="Phone or ID"
      />
      <input
        value={amount}
        onChange={(e) => setAmount(e.target.value)}
        type="number"
        placeholder="Amount"
      />
      <button onClick={generateQR}>Generate</button>

      {qrCode && <img src={qrCode} alt="PromptPay QR" />}
    </div>
  );
}
```

### Publishing Example View

To use the included example page with beautiful UI:

```bash
php artisan vendor:publish --tag=promptpay-views
```

Then add a route in your `routes/web.php`:

```php
Route::get('/promptpay-demo', function () {
    return view('vendor.promptpay.example');
});
```

Visit `/promptpay-demo` to see the interactive demo page.

## Supported Identifier Formats

### Phone Numbers
- `0812345678` - Thai phone format (10 digits starting with 0)
- `812345678` - Without leading 0 (9 digits)
- `66812345678` - International format (automatically converted)

### National ID
- `1234567890123` - 13-digit Thai National ID

## API Reference

### `generatePayload(string $identifier, ?float $amount = null): string`

Generates the PromptPay EMV QR code payload string.

**Parameters:**
- `$identifier` - Thai phone number or National ID
- `$amount` - Payment amount (optional, null for open amount)

**Returns:** PromptPay payload string

### `generateQRCode(string $identifier, ?float $amount = null, int $size = 300): string`

Generates QR code image as data URI.

**Parameters:**
- `$identifier` - Thai phone number or National ID
- `$amount` - Payment amount (optional, null for open amount)
- `$size` - QR code size in pixels (default: 300)

**Returns:** Data URI string (can be used directly in `<img src="">`)

### `generateQRCodeBinary(string $identifier, ?float $amount = null, int $size = 300): string`

Generates QR code as binary PNG data.

**Parameters:**
- `$identifier` - Thai phone number or National ID
- `$amount` - Payment amount (optional, null for open amount)
- `$size` - QR code size in pixels (default: 300)

**Returns:** Binary PNG data

## Configuration

The config file (`config/promptpay.php`) allows you to set:

```php
return [
    // Default QR code size in pixels
    'qr_size' => env('PROMPTPAY_QR_SIZE', 300),

    // Default merchant identifier (optional)
    'default_identifier' => env('PROMPTPAY_DEFAULT_IDENTIFIER', null),
];
```

Add to your `.env` file:

```env
PROMPTPAY_QR_SIZE=300
PROMPTPAY_DEFAULT_IDENTIFIER=0812345678
```

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Mor](https://github.com/mortogo321)
- Built with [endroid/qr-code](https://github.com/endroid/qr-code)

## Support

If you discover any issues, please email morgoto321@gmail.com or create an issue on [GitHub](https://github.com/mortogo321/laravel-thai-promptpay/issues).
