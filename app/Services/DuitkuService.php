<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DuitkuService
{
    /**
     * Generate signature MD5 untuk request ke DuitKu
     * Format: merchantCode + amount + merchantOrderId + apiKey
     */
    public function generateSignature(string $merchantCode, int $amount, string $merchantOrderId, string $apiKey): string
    {
        return md5($merchantCode . $amount . $merchantOrderId . $apiKey);
    }

    /**
     * Generate signature MD5 untuk validasi callback DuitKu
     * Format: merchantCode + amount + merchantOrderId + apiKey
     */
    public function validateSignature(
        string $merchantCode,
        int $amount,
        string $merchantOrderId,
        string $apiKey,
        string $signature
    ): bool {
        $expectedSignature = $this->generateSignature($merchantCode, $amount, $merchantOrderId, $apiKey);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate merchantOrderId unik
     * Format: TOPUP-{user_id}-{timestamp}-{uuid}
     */
    public function generateMerchantOrderId(int $userId): string
    {
        return 'TOPUP-' . $userId . '-' . time() . '-' . Str::uuid()->toString();
    }

    /**
     * Buat transaksi baru di DuitKu
     * Endpoint: /v2/inquiry
     */
    public function createTransaction(
        int $amount,
        string $merchantOrderId,
        string $paymentMethod,
        ?string $customerEmail = null,
        ?string $customerName = null,
        ?string $phoneNumber = null
    ): array {
        $merchantCode = config('duitku.merchant_code');
        $apiKey = config('duitku.api_key');
        $signature = $this->generateSignature($merchantCode, $amount, $merchantOrderId, $apiKey);

        $payload = [
            'merchantCode'      => $merchantCode,
            'amount'            => $amount,
            'merchantOrderId'   => $merchantOrderId,
            'paymentMethod'     => $paymentMethod,
            'signature'         => $signature,
        ];

        // Tambahkan field opsional jika ada
        if ($customerEmail) {
            $payload['customerEmail'] = $customerEmail;
        }

        if ($customerName) {
            $payload['customerName'] = $customerName;
        }

        if ($phoneNumber) {
            $payload['phoneNumber'] = $phoneNumber;
        }

        // Add callback URL if configured
        $callbackUrl = config('duitku.callback_url');
        if ($callbackUrl) {
            $payload['callbackUrl'] = $callbackUrl;
        }

        // Add return URL if configured
        $returnUrl = config('duitku.return_url');
        if ($returnUrl) {
            $payload['returnUrl'] = $returnUrl;
        }

        $baseUrl = config('duitku.base_url');

        try {
            $response = Http::timeout(30)->post($baseUrl . '/v2/inquiry', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => 'Gagal menghubungi server DuitKu',
                'responseCode' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cek status transaksi di DuitKu (jika diperlukan)
     * Endpoint: /v2/inquiry (dengan merchantOrderId)
     */
    public function checkTransactionStatus(string $merchantOrderId): array
    {
        $merchantCode = config('duitku.merchant_code');
        $apiKey = config('duitku.api_key');

        // Untuk cek status, amount = 0 atau bisa pakai format berbeda
        // Sesuaikan dengan dokumentasi DuitKu
        $signature = md5($merchantCode . '0' . $merchantOrderId . $apiKey);

        $payload = [
            'merchantCode'      => $merchantCode,
            'merchantOrderId'   => $merchantOrderId,
            'signature'         => $signature,
        ];

        $baseUrl = config('duitku.base_url');

        try {
            $response = Http::timeout(30)->post($baseUrl . '/v2/inquiry', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => 'Gagal menghubungi server DuitKu',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }
}