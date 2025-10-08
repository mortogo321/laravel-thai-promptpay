<?php

namespace Mortogo321\LaravelThaiPromptPay\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
            'size' => 'nullable|integer|min:100|max:1000',
        ]);

        try {
            $qrCode = $this->promptPay->generateQRCode(
                $validated['identifier'],
                $validated['amount'] ?? null,
                $validated['size'] ?? 300
            );

            return response()->json([
                'success' => true,
                'qr_code' => $qrCode,
                'identifier' => $validated['identifier'],
                'amount' => $validated['amount'] ?? null,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate PromptPay payload string only
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function payload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
        ]);

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
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download PromptPay QR code as PNG
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request)
    {
        $validated = $request->validate([
            'identifier' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
            'size' => 'nullable|integer|min:100|max:1000',
        ]);

        try {
            $binary = $this->promptPay->generateQRCodeBinary(
                $validated['identifier'],
                $validated['amount'] ?? null,
                $validated['size'] ?? 300
            );

            $filename = 'promptpay-' . time() . '.png';

            return response($binary)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
