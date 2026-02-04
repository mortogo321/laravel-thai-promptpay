<?php

namespace Mortogo321\LaravelThaiPromptPay\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Mortogo321\LaravelThaiPromptPay\Http\Requests\GeneratePromptPayRequest;
use Mortogo321\LaravelThaiPromptPay\Http\Requests\PayloadPromptPayRequest;
use Mortogo321\LaravelThaiPromptPay\PromptPayQR;

class PromptPayController extends Controller
{
    protected PromptPayQR $promptPay;

    public function __construct(PromptPayQR $promptPay)
    {
        $this->promptPay = $promptPay;
    }

    /**
     * Generate PromptPay QR code
     */
    public function generate(GeneratePromptPayRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $qrCode = $this->promptPay->generateQRCode(
                $validated['identifier'],
                $validated['amount'] ?? null,
                $validated['size'] ?? 300
            );

            $identifierType = $this->promptPay->getIdentifierType($validated['identifier']);

            return response()->json([
                'success' => true,
                'qr_code' => $qrCode,
                'identifier' => $validated['identifier'],
                'type' => $identifierType,
                'amount' => $validated['amount'] ?? null,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate PromptPay payload string only
     */
    public function payload(PayloadPromptPayRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $payload = $this->promptPay->generatePayload(
                $validated['identifier'],
                $validated['amount'] ?? null
            );

            return response()->json([
                'success' => true,
                'payload' => $payload,
                'identifier' => $validated['identifier'],
                'amount' => $validated['amount'] ?? null,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download PromptPay QR code as PNG
     */
    public function download(GeneratePromptPayRequest $request): Response|JsonResponse
    {
        $validated = $request->validated();

        try {
            $binary = $this->promptPay->generateQRCodeBinary(
                $validated['identifier'],
                $validated['amount'] ?? null,
                $validated['size'] ?? 300
            );

            $filename = 'promptpay-' . uniqid() . '.png';

            return response($binary)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
