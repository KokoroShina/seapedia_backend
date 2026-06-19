<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\DuitkuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/* @noinspection PhpParamsInspection */

class WalletController extends Controller
{
    /** @var DuitkuService */
    protected $duitkuService;

    public function __construct(DuitkuService $duitkuService)
    {
        $this->duitkuService = $duitkuService;
    }

    // Helper: ambil atau buat wallet user
    private function getOrCreateWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0]
        );
    }

    // Lihat saldo wallet
    public function show(Request $request)
    {
        $wallet = $this->getOrCreateWallet($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Saldo wallet berhasil diambil',
            'data' => $wallet,
        ]);
    }

    // Request top-up via DuitKu
    public function topup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();
        $wallet = $this->getOrCreateWallet($user->id);
        $amount = (int) $request->amount;
        $paymentMethod = strtoupper($request->payment_method);

        // Generate merchantOrderId unik
        $merchantOrderId = $this->duitkuService->generateMerchantOrderId($user->id);

        // Buat transaksi di DuitKu
        $duitkuResponse = $this->duitkuService->createTransaction(
            $amount,
            $merchantOrderId,
            $paymentMethod,
            $user->email,
            $user->name,
            $user->phone_number ?? null
        );

        // Cek apakah request ke DuitKu berhasil
        if (!isset($duitkuResponse['success']) || $duitkuResponse['success'] !== true) {
            // DuitKu mungkin return responseCode != '00' sebagai error
            if (isset($duitkuResponse['responseCode']) && $duitkuResponse['responseCode'] !== '00') {
                return response()->json([
                    'success' => false,
                    'message' => $duitkuResponse['responseMessage'] ?? 'Gagal membuat transaksi di DuitKu',
                ], 400);
            }

            // Jika tidak ada responseCode atau success flag, berarti error lain
            return response()->json([
                'success' => false,
                'message' => $duitkuResponse['message'] ?? 'Gagal menghubungi server DuitKu',
            ], 500);
        }

        // Simpan record transaksi dengan status pending
        WalletTransaction::create([
            'wallet_id'          => $wallet->id,
            'type'               => 'topup',
            'amount'             => $amount,
            'status'             => 'pending',
            'payment_reference'  => $merchantOrderId,
            'payment_method'     => $paymentMethod,
            'description'        => 'Top-up via DuitKu - Menunggu pembayaran',
        ]);

        // Siapkan response untuk frontend
        $paymentData = [
            'merchantOrderId'   => $merchantOrderId,
            'reference'         => $duitkuResponse['reference'] ?? null,
            'amount'            => $amount,
            'status'            => 'pending',
        ];

        // Tambahkan data sesuai metode pembayaran
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

    // Callback dari DuitKu (PUBLIC - tanpa auth:sanctum)
    public function callback(Request $request)
    {
        // Ambil data dari callback DuitKu
        $merchantCode = $request->input('merchantCode');
        $amount = (int) $request->input('amount');
        $merchantOrderId = $request->input('merchantOrderId');
        $signature = $request->input('signature');
        $resultCode = $request->input('resultCode');

        // Validasi signature
        $apiKey = config('duitku.api_key');
        if (!$this->duitkuService->validateSignature($merchantCode, $amount, $merchantOrderId, $apiKey, $signature)) {
            // Signature tidak valid - respond OK untuk stop retry dari DuitKu
            return response('SIGNATURE_VALIDATION_FAILED', 400);
        }

        // Cari transaksi
        $transaction = WalletTransaction::where('payment_reference', $merchantOrderId)
            ->where('type', 'topup')
            ->first();

        if (!$transaction) {
            // Transaksi tidak ditemukan - respond OK untuk stop retry
            return response('ORDER_NOT_FOUND');
        }

        // IDEMPOTENCY: Jika status sudah success atau failed, ignore
        if ($transaction->status !== 'pending') {
            return response('OK');
        }

        // Update status transaksi dan saldo wallet
        DB::transaction(function () use ($transaction, $resultCode, $amount): void {
            if ($resultCode === '00') {
                // Sukses - update status dan tambah saldo
                $transaction->update([
                    'status' => 'success',
                    'description' => 'Top-up via DuitKu - Berhasil',
                ]);

                // Increment saldo wallet menggunakan DB::table
                $wallet = $transaction->wallet;
                Wallet::where('id', $wallet->id)->update([
                    'balance' => DB::raw("balance + {$amount}"),
                ]);
            } else {
                // Gagal - update status saja, jangan ubah saldo
                $transaction->update([
                    'status' => 'failed',
                    'description' => 'Top-up via DuitKu - Gagal (resultCode: ' . $resultCode . ')',
                ]);
            }
        });

        // Response untuk DuitKu
        return response('OK');
    }

    // Cek status transaksi (untuk polling dari frontend)
    public function checkStatus(Request $request, string $merchantOrderId)
    {
        $wallet = $this->getOrCreateWallet($request->user()->id);

        // Cari transaksi yang milik user ini
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

    // Riwayat transaksi wallet (paginated)
    public function transactions(Request $request)
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
}
