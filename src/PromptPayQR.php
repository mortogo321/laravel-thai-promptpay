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
     * Generate PromptPay QR code payload string
     *
     * @param string $identifier Phone number (0812345678), National ID (1234567890123), or e-Wallet ID
     * @param float|null $amount Payment amount (optional)
     * @return string PromptPay payload string
     */
    public function generatePayload(string $identifier, ?float $amount = null): string
    {
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

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->size($size)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return $result->getDataUri();
    }

    /**
     * Generate QR code image as PNG binary
     */
    public function generateQRCodeBinary(string $identifier, ?float $amount = null, int $size = 300): string
    {
        $payload = $this->generatePayload($identifier, $amount);

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->size($size)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return $result->getString();
    }

    /**
     * Parse and validate identifier, returning type and formatted value
     *
     * @param string $identifier
     * @return array{type: string, value: string}
     * @throws InvalidArgumentException
     */
    private function parseIdentifier(string $identifier): array
    {
        $cleaned = preg_replace('/[^0-9]/', '', $identifier);
        $length = strlen($cleaned);

        // E-wallet ID (15 digits)
        if ($length === 15) {
            return ['type' => self::TYPE_EWALLET, 'value' => $cleaned];
        }

        // National ID or Tax ID (13 digits, doesn't start with 0066)
        if ($length === 13 && !str_starts_with($cleaned, '0066')) {
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
            'Invalid identifier. Use: phone (0812345678), National ID (1234567890123), or e-Wallet (15 digits)'
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
