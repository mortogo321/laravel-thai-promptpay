<?php

namespace Mortogo321\LaravelThaiPromptPay;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use InvalidArgumentException;

class PromptPayQR
{
    private const PAYLOAD_FORMAT_INDICATOR = '00';

    private const POI_METHOD = '01';

    private const MERCHANT_INFORMATION = '29';

    private const TRANSACTION_CURRENCY = '53';

    private const TRANSACTION_AMOUNT = '54';

    private const COUNTRY_CODE = '58';

    private const CRC = '63';

    private const AID = 'A000000677010111';

    private const AID_TAG = '00';

    private const MOBILE_TAG = '01';      // Sub-tag for phone numbers

    private const TAX_ID_TAG = '02';      // Sub-tag for National ID / Tax ID

    private const EWALLET_TAG = '03';     // Sub-tag for e-Wallet ID

    private const CURRENCY_THB = '764';

    private const COUNTRY_TH = 'TH';

    private const TYPE_MOBILE = 'mobile';

    private const TYPE_TAX_ID = 'tax_id';

    private const TYPE_EWALLET = 'ewallet';

    /**
     * Get supported identifier formats
     *
     * @return array<string, array{supported: bool, sub_tag: string|null, format: string, example: string}>
     */
    public static function getSupportedFormats(): array
    {
        return [
            'mobile' => [
                'supported' => true,
                'sub_tag' => '01',
                'format' => '10 digits starting with 06, 08, or 09',
                'example' => '0812345678',
            ],
            'national_id' => [
                'supported' => true,
                'sub_tag' => '02',
                'format' => '13 digits (Thai National ID)',
                'example' => '1234567890123',
            ],
            'tax_id' => [
                'supported' => true,
                'sub_tag' => '02',
                'format' => '13 digits (Tax ID)',
                'example' => '0123456789012',
            ],
            'ewallet' => [
                'supported' => true,
                'sub_tag' => '03',
                'format' => '15 digits',
                'example' => '123456789012345',
            ],
            'bank_account' => [
                'supported' => false,
                'sub_tag' => null,
                'format' => 'NOT SUPPORTED (BOT spec: reserved)',
                'example' => '-',
            ],
        ];
    }

    /**
     * Validate identifier without generating QR
     *
     * @param  string  $identifier  Phone, National ID, or e-Wallet ID
     * @return array{valid: bool, type: string|null, formatted: string|null, error: string|null}
     */
    public function validate(string $identifier): array
    {
        try {
            $parsed = $this->parseIdentifier($identifier);

            return [
                'valid' => true,
                'type' => $parsed['type'],
                'formatted' => $parsed['value'],
                'error' => null,
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'valid' => false,
                'type' => null,
                'formatted' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate amount
     *
     * @return array{valid: bool, error: string|null}
     */
    public function validateAmount(?float $amount): array
    {
        if ($amount === null) {
            return ['valid' => true, 'error' => null];
        }

        if ($amount < 0) {
            return ['valid' => false, 'error' => 'Amount cannot be negative'];
        }

        if ($amount > 999999999.99) {
            return ['valid' => false, 'error' => 'Amount exceeds maximum (999,999,999.99)'];
        }

        // Check for valid decimal places (max 2 for Thai Baht)
        if (fmod($amount * 100, 1) != 0) {
            return ['valid' => false, 'error' => 'Amount must have at most 2 decimal places'];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Get identifier type without generating QR
     *
     * @return string|null Returns 'mobile', 'tax_id', 'ewallet', or null if invalid
     */
    public function getIdentifierType(string $identifier): ?string
    {
        $result = $this->validate($identifier);

        return $result['type'];
    }

    /**
     * Check if identifier is a valid Thai mobile number
     */
    public function isMobileNumber(string $identifier): bool
    {
        $cleaned = preg_replace('/[^0-9]/', '', $identifier);

        // Thai mobile: 10 digits starting with 06, 08, or 09
        if (strlen($cleaned) === 10 && preg_match('/^0[689]/', $cleaned)) {
            return true;
        }

        // International format: 66XXXXXXXXX (11 digits)
        if (strlen($cleaned) === 11 && str_starts_with($cleaned, '66')) {
            return true;
        }

        // PromptPay format: 0066XXXXXXXXX (13 digits)
        if (strlen($cleaned) === 13 && str_starts_with($cleaned, '0066')) {
            return true;
        }

        return false;
    }

    /**
     * Check if identifier is a valid Thai National ID (with checksum validation)
     */
    public function isNationalId(string $identifier): bool
    {
        $cleaned = preg_replace('/[^0-9]/', '', $identifier);

        if (strlen($cleaned) !== 13) {
            return false;
        }

        // Skip if it looks like a phone number
        if (str_starts_with($cleaned, '0066')) {
            return false;
        }

        // Validate checksum (Thai National ID algorithm)
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cleaned[$i] * (13 - $i);
        }

        $checkDigit = (11 - ($sum % 11)) % 10;

        return $checkDigit === (int) $cleaned[12];
    }

    /**
     * Check if identifier is a valid Tax ID
     */
    public function isTaxId(string $identifier): bool
    {
        $cleaned = preg_replace('/[^0-9]/', '', $identifier);

        // Tax ID is 13 digits, not starting with 0066
        return strlen($cleaned) === 13 && ! str_starts_with($cleaned, '0066');
    }

    /**
     * Generate PromptPay QR code payload string
     *
     * @param  string  $identifier  Phone number (0812345678), National ID (1234567890123), or e-Wallet ID
     * @param  float|null  $amount  Payment amount (optional)
     * @return string PromptPay payload string
     *
     * @throws InvalidArgumentException
     */
    public function generatePayload(string $identifier, ?float $amount = null): string
    {
        // Validate amount
        $amountValidation = $this->validateAmount($amount);
        if (! $amountValidation['valid']) {
            throw new InvalidArgumentException($amountValidation['error']);
        }

        $parsed = $this->parseIdentifier($identifier);

        $payload = '';
        $payload .= $this->buildTag(self::PAYLOAD_FORMAT_INDICATOR, '01');
        $payload .= $this->buildTag(self::POI_METHOD, $amount !== null && $amount > 0 ? '12' : '11');
        $payload .= $this->buildMerchantInformation($parsed['value'], $parsed['type']);
        $payload .= $this->buildTag(self::TRANSACTION_CURRENCY, self::CURRENCY_THB);

        if ($amount !== null && $amount > 0) {
            $payload .= $this->buildTag(self::TRANSACTION_AMOUNT, number_format($amount, 2, '.', ''));
        }

        $payload .= $this->buildTag(self::COUNTRY_CODE, self::COUNTRY_TH);
        $payload .= self::CRC . '04';

        $crc = $this->calculateCRC16($payload);

        return $payload . strtoupper($crc);
    }

    /**
     * Generate QR code image as data URI
     */
    public function generateQRCode(string $identifier, ?float $amount = null, int $size = 300): string
    {
        $payload = $this->generatePayload($identifier, $amount);

        $builder = new Builder(
            writer: new PngWriter,
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return $builder->build()->getDataUri();
    }

    /**
     * Generate QR code image as PNG binary
     */
    public function generateQRCodeBinary(string $identifier, ?float $amount = null, int $size = 300): string
    {
        $payload = $this->generatePayload($identifier, $amount);

        $builder = new Builder(
            writer: new PngWriter,
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return $builder->build()->getString();
    }

    /**
     * Parse and validate identifier, returning type and formatted value
     *
     * @return array{type: string, value: string}
     *
     * @throws InvalidArgumentException
     */
    private function parseIdentifier(string $identifier): array
    {
        $cleaned = preg_replace('/[^0-9]/', '', $identifier);

        if (empty($cleaned)) {
            throw new InvalidArgumentException(
                'Invalid PromptPay ID: identifier must contain numeric characters'
            );
        }

        $length = strlen($cleaned);

        // E-wallet ID (15 digits)
        if ($length === 15) {
            return ['type' => self::TYPE_EWALLET, 'value' => $cleaned];
        }

        // National ID or Tax ID (13 digits, doesn't start with 0066)
        if ($length === 13 && ! str_starts_with($cleaned, '0066')) {
            return ['type' => self::TYPE_TAX_ID, 'value' => $cleaned];
        }

        // Phone already in PromptPay format (0066XXXXXXXXX)
        if ($length === 13 && str_starts_with($cleaned, '0066')) {
            return ['type' => self::TYPE_MOBILE, 'value' => $cleaned];
        }

        // Thai phone number (10 digits starting with 0): 08XXXXXXXX -> 0066XXXXXXXX
        if ($length === 10 && $cleaned[0] === '0') {
            return ['type' => self::TYPE_MOBILE, 'value' => '0066' . substr($cleaned, 1)];
        }

        // Phone number without leading 0 (9 digits): 8XXXXXXXX -> 00668XXXXXXXX
        if ($length === 9) {
            return ['type' => self::TYPE_MOBILE, 'value' => '0066' . $cleaned];
        }

        // 10 digits not starting with 0 - non-standard format, prepend 00 and let bank handle it
        if ($length === 10) {
            return ['type' => self::TYPE_MOBILE, 'value' => '00' . $cleaned];
        }

        // International format 66XXXXXXXXX (11 digits)
        if ($length === 11 && str_starts_with($cleaned, '66')) {
            return ['type' => self::TYPE_MOBILE, 'value' => '00' . $cleaned];
        }

        throw new InvalidArgumentException(
            'Invalid PromptPay ID format. Supported formats: ' .
            'Mobile phone (10 digits starting with 06/08/09), ' .
            'National ID/Tax ID (13 digits), ' .
            'e-Wallet (15 digits). ' .
            'Note: Bank account numbers are NOT supported (BOT spec: reserved).'
        );
    }

    /**
     * Build merchant information tag with correct sub-tag based on identifier type
     */
    private function buildMerchantInformation(string $identifier, string $type): string
    {
        $aid = $this->buildTag(self::AID_TAG, self::AID);

        $accountTag = match ($type) {
            self::TYPE_MOBILE => self::MOBILE_TAG,
            self::TYPE_TAX_ID => self::TAX_ID_TAG,
            self::TYPE_EWALLET => self::EWALLET_TAG,
            default => self::MOBILE_TAG,
        };

        $account = $this->buildTag($accountTag, $identifier);
        $merchantInfo = $aid . $account;

        return $this->buildTag(self::MERCHANT_INFORMATION, $merchantInfo);
    }

    /**
     * Build EMV tag
     */
    private function buildTag(string $tag, string $value): string
    {
        $length = strlen($value);

        return $tag . str_pad((string) $length, 2, '0', STR_PAD_LEFT) . $value;
    }

    /**
     * Validate CRC16-CCITT checksum of a complete payload
     *
     * @param  string  $payload  Complete PromptPay payload string
     * @return bool True if checksum is valid
     */
    public function validatePayload(string $payload): bool
    {
        if (strlen($payload) < 8) {
            return false;
        }

        $expectedCrc = substr($payload, -4);
        $dataWithoutCrc = substr($payload, 0, -4);
        $calculatedCrc = $this->calculateCRC16($dataWithoutCrc);

        return strtoupper($expectedCrc) === strtoupper($calculatedCrc);
    }

    /**
     * Calculate CRC16-CCITT checksum
     */
    private function calculateCRC16(string $data): string
    {
        $crc = 0xFFFF;
        $strlen = strlen($data);

        for ($i = 0; $i < $strlen; $i++) {
            $crc ^= ord($data[$i]) << 8;

            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
            }
        }

        $crc = $crc & 0xFFFF;

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
