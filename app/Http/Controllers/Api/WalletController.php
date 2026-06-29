<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\TopupRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\DuitkuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function __construct(
        private DuitkuService $duitkuService
    ) {}

    private function getOrCreateWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0]
        );
    }

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->getOrCreateWallet($request->user()->id);

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Saldo wallet berhasil diambil',
            'data' => [
                'id' => $wallet->id,
                'balance' => $wallet->balance,
                'transactions' => $transactions,
            ],
        ]);
    }

    public function topup(TopupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $wallet = $this->getOrCreateWallet($user->id);
        $amount = (int) $validated['amount'];
        $paymentMethod = strtoupper($validated['payment_method']);

        $merchantOrderId = $this->duitkuService->generateMerchantOrderId($user->id);

        $duitkuResponse = $this->duitkuService->createTransaction(
            $amount,
            $merchantOrderId,
            $paymentMethod,
            $user->email,
            $user->name,
            $user->phone_number ?? null
        );

        // DuitKu returns statusCode: "00" for success
        if (isset($duitkuResponse['statusCode']) && $duitkuResponse['statusCode'] !== '00') {
            return response()->json([
                'success' => false,
                'message' => $duitkuResponse['statusMessage'] ?? $duitkuResponse['Message'] ?? 'Gagal membuat transaksi di DuitKu',
                'error_code' => $duitkuResponse['statusCode'] ?? null,
            ], 400);
        }

        if (!isset($duitkuResponse['paymentUrl']) && !isset($duitkuResponse['vaNumber'])) {
            return response()->json([
                'success' => false,
                'message' => $duitkuResponse['statusMessage'] ?? $duitkuResponse['Message'] ?? 'Gagal membuat transaksi di DuitKu',
            ], 500);
        }

        WalletTransaction::create([
            'wallet_id'          => $wallet->id,
            'type'               => 'topup',
            'amount'             => $amount,
            'status'             => 'pending',
            'payment_reference'  => $merchantOrderId,
            'payment_method'     => $paymentMethod,
            'description'        => 'Top-up via DuitKu - Menunggu pembayaran',
        ]);

        $paymentData = [
            'merchantOrderId'   => $merchantOrderId,
            'reference'         => $duitkuResponse['reference'] ?? null,
            'amount'            => $amount,
            'status'            => 'pending',
        ];

        if (isset($duitkuResponse['paymentUrl'])) {
            $paymentData['paymentUrl'] = $duitkuResponse['paymentUrl'];
        }

        if (isset($duitkuResponse['vaNumber'])) {
            $paymentData['vaNumber'] = $duitkuResponse['vaNumber'];
        }

        if (isset($duitkuResponse['qrString'])) {
            $paymentData['qrString'] = $duitkuResponse['qrString'];
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaksi top-up berhasil dibuat',
            'data' => $paymentData,
        ]);
    }

    public function callback(Request $request)
    {
        // Log SEMUA input yang masuk — penting untuk debugging
        Log::info('DuitKu Callback RAW Input', $request->all());

        // Ambil semua parameter — DuitKu bisa kirim dengan nama field berbeda
        $merchantCode = $request->input('merchantCode');
        $amount = $request->input('amount');  // jangan cast dulu
        $merchantOrderId = $request->input('merchantOrderId');
        $signature = $request->input('signature');
        // resultCode bisa beda case / nama
        $resultCode = $request->input('resultCode')
            ?? $request->input('result_code')
            ?? $request->input('statusCode')
            ?? $request->input('status_code')
            ?? null;

        // Additional fields (nullable — tidak perlu untuk proses utama)
        $reference = $request->input('reference');
        $paymentMethod = $request->input('paymentCode') ?? $request->input('paymentMethod');
        $issuerCode = $request->input('issuerCode');
        $settlementDate = $request->input('settlementDate');

        Log::info('DuitKu Callback Parsed', [
            'merchantCode' => $merchantCode,
            'amount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            'resultCode' => $resultCode,
            'signature' => $signature,
        ]);

        // Validasi signature — callback pakai format MID + AMOUNT + OID (berbeda dari inquiry)
        $apiKey = config('duitku.api_key');
        $amountInt = (int) $amount;
        if (!$this->duitkuService->validateCallbackSignature($merchantCode, $amountInt, $merchantOrderId, $apiKey, $signature)) {
            // Log detail untuk debug
            Log::warning('DuitKu Callback: Invalid Signature', [
                'merchantOrderId' => $merchantOrderId,
                'amount_received' => $amount,
                'amount_int' => $amountInt,
                'signature_received' => $signature,
                'signature_expected' => hash_hmac('sha256', $merchantCode . $amountInt . $merchantOrderId, $apiKey),
            ]);
            return response('SIGNATURE_VALIDATION_FAILED', 400);
        }

        $transaction = WalletTransaction::where('payment_reference', $merchantOrderId)
            ->where('type', 'topup')
            ->first();

        if (!$transaction) {
            Log::warning('DuitKu Callback: Transaction Not Found', [
                'merchantOrderId' => $merchantOrderId,
            ]);
            return response('ORDER_NOT_FOUND');
        }

        if ($transaction->status !== 'pending') {
            Log::info('DuitKu Callback: Transaction Already Processed', [
                'merchantOrderId' => $merchantOrderId,
                'currentStatus' => $transaction->status,
            ]);
            return response('OK');
        }

        DB::transaction(function () use ($transaction, $resultCode, $amountInt, $reference, $paymentMethod, $issuerCode, $settlementDate): void {
            if ($resultCode === '00') {
                $transaction->update([
                    'status' => 'success',
                    'description' => 'Top-up via DuitKu - Berhasil',
                ]);

                $wallet = $transaction->wallet;
                Wallet::where('id', $wallet->id)->update([
                    'balance' => DB::raw("balance + {$amountInt}"),
                ]);
                
                Log::info('DuitKu Callback: Topup Success', [
                    'merchantOrderId' => $transaction->payment_reference,
                    'amount' => $amountInt,
                    'reference' => $reference ?? null,
                    'issuerCode' => $issuerCode ?? null,
                    'settlementDate' => $settlementDate ?? null,
                ]);
            } else {
                $transaction->update([
                    'status' => 'failed',
                    'description' => 'Top-up via DuitKu - Gagal (resultCode: ' . $resultCode . ')',
                ]);
                
                Log::info('DuitKu Callback: Topup Failed', [
                    'merchantOrderId' => $transaction->payment_reference,
                    'resultCode' => $resultCode,
                ]);
            }
        });

        return response('OK');
    }

    public function checkStatus(Request $request, string $merchantOrderId): JsonResponse
    {
        $wallet = $this->getOrCreateWallet($request->user()->id);

        $transaction = WalletTransaction::where('payment_reference', $merchantOrderId)
            ->where('wallet_id', $wallet->id)
            ->where('type', 'topup')
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status transaksi berhasil diambil',
            'data' => [
                'merchantOrderId' => $transaction->payment_reference,
                'amount'          => $transaction->amount,
                'status'          => $transaction->status,
                'payment_method'  => $transaction->payment_method,
                'created_at'      => $transaction->created_at,
                'updated_at'      => $transaction->updated_at,
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $wallet = $this->getOrCreateWallet($request->user()->id);

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat transaksi berhasil diambil',
            'data' => $transactions,
        ]);
    }

    /**
     * Ambil daftar payment methods yang tersedia dari DuitKu
     * Endpoint: GET /api/payment-methods
     */
    public function getPaymentMethods(): JsonResponse
    {
        $response = $this->duitkuService->getPaymentMethods();

        if (isset($response['responseCode']) && $response['responseCode'] !== '00') {
            return response()->json([
                'success' => false,
                'message' => $response['responseMessage'] ?? 'Gagal mengambil payment methods',
            ], 400);
        }

        // Format response dari DuitKu
        $paymentMethods = [];
        if (isset($response['paymentFee']) && is_array($response['paymentFee'])) {
            foreach ($response['paymentFee'] as $method) {
                $paymentMethods[] = [
                    'code' => $method['paymentMethod'] ?? null,
                    'name' => $method['paymentName'] ?? null,
                    'image' => $method['paymentImage'] ?? null,
                    'fee' => isset($method['totalFee']) ? (int) $method['totalFee'] : 0,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment methods berhasil diambil',
            'data' => $paymentMethods,
        ]);
    }

    /**
     * Check transaction status dari DuitKu (public endpoint)
     * Endpoint: GET /api/wallet/check-status/{merchantOrderId}
     */
    public function checkDuitkuStatus(string $merchantOrderId): JsonResponse
    {
        $response = $this->duitkuService->checkTransactionStatus($merchantOrderId);

        if (isset($response['responseCode']) && $response['responseCode'] !== '00') {
            return response()->json([
                'success' => false,
                'message' => $response['responseMessage'] ?? 'Gagal mengambil status transaksi',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status transaksi berhasil diambil',
            'data' => [
                'merchantOrderId' => $response['merchantOrderId'] ?? $merchantOrderId,
                'reference' => $response['reference'] ?? null,
                'amount' => isset($response['amount']) ? (int) $response['amount'] : null,
                'fee' => isset($response['fee']) ? (int) $response['fee'] : null,
                'statusCode' => $response['statusCode'] ?? null,
                'statusMessage' => $response['statusMessage'] ?? null,
            ],
        ]);
    }

    /**
     * Sync topup status dari DuitKu — fallback kalau callback miss.
     * Cek status transaksi langsung ke DuitKu, lalu update saldo kalau berhasil.
     */
    public function syncTopup(Request $request, string $merchantOrderId): JsonResponse
    {
        $wallet = $this->getOrCreateWallet($request->user()->id);

        $transaction = WalletTransaction::where('payment_reference', $merchantOrderId)
            ->where('wallet_id', $wallet->id)
            ->where('type', 'topup')
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => true,
                'message' => 'Transaksi sudah diproses',
                'data' => [
                    'merchantOrderId' => $transaction->payment_reference,
                    'status' => $transaction->status,
                ],
            ]);
        }

        // Cek status ke DuitKu
        $duitkuStatus = $this->duitkuService->checkTransactionStatus($merchantOrderId);

        Log::info('DuitKu Sync Request', [
            'merchantOrderId' => $merchantOrderId,
            'duitkuStatus' => $duitkuStatus,
        ]);

        $statusCode = $duitkuStatus['statusCode'] ?? $duitkuStatus['responseCode'] ?? null;

        // SUCCESS — update saldo
        if ($statusCode === '00') {
            DB::transaction(function () use ($transaction, $duitkuStatus) {
                $transaction->update([
                    'status' => 'success',
                    'description' => 'Top-up via DuitKu - Berhasil (sync)',
                ]);

                $wallet = $transaction->wallet;
                Wallet::where('id', $wallet->id)->update([
                    'balance' => DB::raw("balance + {$transaction->amount}"),
                ]);
            });

            Log::info('DuitKu Sync: Topup Success', [
                'merchantOrderId' => $merchantOrderId,
                'amount' => $transaction->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Top-up berhasil disinkronkan',
                'data' => [
                    'merchantOrderId' => $transaction->payment_reference,
                    'status' => 'success',
                    'amount' => $transaction->amount,
                ],
            ]);
        }

        // GAGAL — tetap update status
        if ($statusCode !== null && $statusCode !== '00') {
            $transaction->update([
                'status' => 'failed',
                'description' => 'Top-up via DuitKu - Gagal (resultCode: ' . $statusCode . ') - sync',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status transaksi berhasil disinkronkan',
                'data' => [
                    'merchantOrderId' => $transaction->payment_reference,
                    'status' => 'failed',
                    'resultCode' => $statusCode,
                ],
            ]);
        }

        // Status tidak bisa dibaca — masih pending
        return response()->json([
            'success' => true,
            'message' => 'Status masih pending di DuitKu',
            'data' => [
                'merchantOrderId' => $transaction->payment_reference,
                'status' => 'pending',
            ],
        ]);
    }
}
