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

    private const APP_ID_THAILAND = '0016A000000677010111';
    private const APP_ID_TAG = '00';
    private const ACCOUNT_TAG = '01';

    private const CURRENCY_THB = '764';
    private const COUNTRY_TH = 'TH';

    /**
     * Generate PromptPay QR code payload string
     *
     * @param string $identifier Phone number (format: 0812345678) or National ID (format: 1234567890123)
     * @param float|null $amount Payment amount (optional)
     * @return string PromptPay payload string
     */
    public function generatePayload(string $identifier, ?float $amount = null): string
    {
        $identifier = $this->formatIdentifier($identifier);

        $payload = '';
        $payload .= $this->buildTag(self::PAYLOAD_FORMAT_INDICATOR, '01');
        $payload .= $this->buildTag(self::POI_METHOD, '12');
        $payload .= $this->buildMerchantInformation($identifier);
        $payload .= $this->buildTag(self::TRANSACTION_CURRENCY, self::CURRENCY_THB);

        if ($amount !== null && $amount > 0) {
            $payload .= $this->buildTag(self::TRANSACTION_AMOUNT, number_format($amount, 2, '.', ''));
        }

        $payload .= $this->buildTag(self::COUNTRY_CODE, self::COUNTRY_TH);
        $payload .= self::CRC . '04'; // CRC placeholder

        // Calculate CRC16-CCITT
        $crc = $this->calculateCRC16($payload);
        $payload = substr($payload, 0, -4) . strtoupper($crc);

        return $payload;
    }

    /**
     * Generate QR code image as data URI
     *
     * @param string $identifier Phone number or National ID
     * @param float|null $amount Payment amount (optional)
     * @param int $size QR code size in pixels
     * @return string Data URI of QR code image
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
     *
     * @param string $identifier Phone number or National ID
     * @param float|null $amount Payment amount (optional)
     * @param int $size QR code size in pixels
     * @return string PNG binary data
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
     * Format identifier (phone number or national ID)
     *
     * @param string $identifier
     * @return string Formatted identifier
     * @throws InvalidArgumentException
     */
    private function formatIdentifier(string $identifier): string
    {
        // Remove all non-numeric characters
        $identifier = preg_replace('/[^0-9]/', '', $identifier);

        if (strlen($identifier) === 10 && $identifier[0] === '0') {
            // Thai phone number (10 digits starting with 0)
            return '66' . substr($identifier, 1); // Convert to international format
        } elseif (strlen($identifier) === 9) {
            // Phone number without leading 0
            return '66' . $identifier;
        } elseif (strlen($identifier) === 13) {
            // Thai National ID (13 digits)
            return $identifier;
        } elseif (strlen($identifier) === 15 && str_starts_with($identifier, '66')) {
            // Already in e-wallet format
            return $identifier;
        }

        throw new InvalidArgumentException(
            'Invalid identifier format. Use Thai phone number (0812345678) or National ID (1234567890123)'
        );
    }

    /**
     * Build merchant information tag
     *
     * @param string $identifier
     * @return string
     */
    private function buildMerchantInformation(string $identifier): string
    {
        $appId = $this->buildTag(self::APP_ID_TAG, self::APP_ID_THAILAND);
        $account = $this->buildTag(self::ACCOUNT_TAG, $identifier);
        $merchantInfo = $appId . $account;

        return $this->buildTag(self::MERCHANT_INFORMATION, $merchantInfo);
    }

    /**
     * Build EMV tag
     *
     * @param string $tag
     * @param string $value
     * @return string
     */
    private function buildTag(string $tag, string $value): string
    {
        $length = strlen($value);
        return $tag . str_pad((string)$length, 2, '0', STR_PAD_LEFT) . $value;
    }

    /**
     * Calculate CRC16-CCITT checksum
     *
     * @param string $data
     * @return string
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
        return strtoupper(dechex($crc));
    }
}
