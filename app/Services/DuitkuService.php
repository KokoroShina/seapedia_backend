<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DuitkuService
{
    /**
     * Generate signature HMAC SHA256 untuk request ke DuitKu
     * Format: HMAC-SHA256(merchantCode + merchantOrderId + paymentAmount, apiKey)
     */
    public function generateSignature(string $merchantCode, int $amount, string $merchantOrderId, string $apiKey): string
    {
        $stringToSign = $merchantCode . $merchantOrderId . $amount;
        return hash_hmac('sha256', $stringToSign, $apiKey);
    }

    /**
     * Generate signature HMAC SHA256 untuk Get Payment Methods
     * Format: HMAC-SHA256(merchantCode + amount + datetime, apiKey)
     */
    public function generatePaymentMethodSignature(string $merchantCode, int $amount, string $datetime, string $apiKey): string
    {
        $stringToSign = $merchantCode . $amount . $datetime;
        return hash_hmac('sha256', $stringToSign, $apiKey);
    }

    /**
     * Generate signature HMAC SHA256 untuk Check Transaction Status
     * Format: HMAC-SHA256(merchantCode + merchantOrderId, apiKey)
     */
    public function generateStatusSignature(string $merchantCode, string $merchantOrderId, string $apiKey): string
    {
        $stringToSign = $merchantCode . $merchantOrderId;
        return hash_hmac('sha256', $stringToSign, $apiKey);
    }

    /**
     * Generate signature HMAC SHA256 untuk validasi callback DuitKu
     * Format: HMAC-SHA256(merchantCode + paymentAmount + merchantOrderId, apiKey)
     * NOTE: Urutannya BERBEDA dari inquiry — amount ada di TENGAH, bukan di akhir
     */
    public function validateCallbackSignature(
        string $merchantCode,
        int $amount,
        string $merchantOrderId,
        string $apiKey,
        string $signature
    ): bool {
        $stringToSign = $merchantCode . $amount . $merchantOrderId;
        $expectedSignature = hash_hmac('sha256', $stringToSign, $apiKey);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate merchantOrderId unik (max 50 karakter untuk DuitKu)
     * Format: T-{user_id}-{timestamp}{random}
     */
    public function generateMerchantOrderId(int $userId): string
    {
        $random = substr(Str::random(8), 0, 6);
        return 'T' . $userId . time() . $random;
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
            'paymentAmount'     => $amount,
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
            $http = Http::timeout(30);
            
            // Skip SSL verification for local development
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }
            
            \Log::info('DuitKu Request', [
                'url' => $baseUrl . '/v2/inquiry',
                'payload' => $payload,
            ]);
            
            $response = $http->post($baseUrl . '/v2/inquiry', $payload);

            \Log::info('DuitKu Response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => 'Gagal menghubungi server DuitKu',
                'responseCode' => $response->status(),
                'responseBody' => $response->json(),
            ];
        } catch (\Exception $e) {
            \Log::error('DuitKu Error', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil daftar payment methods yang tersedia dari DuitKu
     * Endpoint: /paymentmethod/getpaymentmethod
     * Signature: HMAC-SHA256(merchantCode + amount + datetime, apiKey)
     */
    public function getPaymentMethods(int $amount = 10000): array
    {
        $merchantCode = config('duitku.merchant_code');
        $apiKey = config('duitku.api_key');
        $datetime = date('Y-m-d H:i:s');

        $signature = $this->generatePaymentMethodSignature($merchantCode, $amount, $datetime, $apiKey);

        $payload = [
            'merchantcode' => $merchantCode,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature,
        ];

        $baseUrl = config('duitku.base_url');

        try {
            $http = Http::timeout(30);
            
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }
            
            \Log::info('DuitKu Payment Methods Request', [
                'url' => $baseUrl . '/paymentmethod/getpaymentmethod',
                'payload' => $payload,
            ]);
            
            $response = $http->post($baseUrl . '/paymentmethod/getpaymentmethod', $payload);

            \Log::info('DuitKu Payment Methods Response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => 'Gagal mengambil payment methods',
                'responseCode' => $response->status(),
            ];
        } catch (\Exception $e) {
            \Log::error('DuitKu Payment Methods Error', [
                'message' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cek status transaksi di DuitKu
     * Endpoint: /transactionStatus
     * Signature: HMAC-SHA256(merchantCode + merchantOrderId, apiKey)
     */
    public function checkTransactionStatus(string $merchantOrderId): array
    {
        $merchantCode = config('duitku.merchant_code');
        $apiKey = config('duitku.api_key');

        $signature = $this->generateStatusSignature($merchantCode, $merchantOrderId, $apiKey);

        $payload = [
            'merchantCode' => $merchantCode,
            'merchantOrderId' => $merchantOrderId,
            'signature' => $signature,
        ];

        $baseUrl = config('duitku.base_url');

        try {
            $http = Http::timeout(30);
            
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }
            
            \Log::info('DuitKu Transaction Status Request', [
                'url' => $baseUrl . '/transactionStatus',
                'payload' => $payload,
            ]);
            
            $response = $http->post($baseUrl . '/transactionStatus', $payload);

            \Log::info('DuitKu Transaction Status Response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => 'Gagal mengambil status transaksi',
                'responseCode' => $response->status(),
            ];
        } catch (\Exception $e) {
            \Log::error('DuitKu Transaction Status Error', [
                'message' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }
}
