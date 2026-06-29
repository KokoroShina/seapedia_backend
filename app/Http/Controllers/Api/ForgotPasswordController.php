<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPassword\ResetPasswordRequest;
use App\Http\Requests\ForgotPassword\SendOtpRequest;
use App\Http\Requests\ForgotPassword\VerifyOtpRequest;
use App\Mail\OtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /**
     * Send OTP to user's email
     * 
     * @param SendOtpRequest $request
     * @return JsonResponse
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        // Check if user exists
        $user = User::where('email', $email)->first();
        if (!$user) {
            // Return success anyway to prevent email enumeration
            return response()->json([
                'success' => true,
                'message' => 'Jika email tersebut terdaftar, kode OTP telah dikirim.',
            ]);
        }

        try {
            return DB::transaction(function () use ($email) {
                // Invalidate all existing OTPs for this email
                PasswordResetOtp::invalidateAllForEmail($email);

                // Generate 6-digit OTP
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

                // Calculate expiry time (default 15 minutes)
                $expiresMinutes = config('auth.otp_expires_minutes', 15);
                $expiresAt = now()->addMinutes($expiresMinutes);

                // Store OTP in database
                $passwordResetOtp = PasswordResetOtp::create([
                    'email' => $email,
                    'otp' => $otp,
                    'expires_at' => $expiresAt,
                    'is_used' => false,
                ]);

                // Send email with OTP
                Mail::to($email)->send(new OtpMail($otp, $email));

                Log::info('OTP sent for password reset', [
                    'email' => $email,
                    'otp_id' => $passwordResetOtp->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Kode OTP telah dikirim ke email Anda.',
                    'data' => [
                        'expires_at' => $expiresAt->toIso8601String(),
                        'expires_in_minutes' => $expiresMinutes,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim kode OTP. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Verify OTP
     * 
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $otp = $validated['otp'];

        // Find valid OTP
        $passwordResetOtp = PasswordResetOtp::where('email', $email)
            ->where('otp', $otp)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$passwordResetOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak valid atau sudah expired.',
            ], 400);
        }

        // Mark OTP as verified
        $passwordResetOtp->markAsVerified();

        Log::info('OTP verified for password reset', [
            'email' => $email,
            'otp_id' => $passwordResetOtp->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP terverifikasi. Silakan reset password Anda.',
        ]);
    }

    /**
     * Reset password with verified OTP
     * 
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $otp = $validated['otp'];
        $newPassword = $validated['password'];

        // Find verified OTP (is_used = true means it's been verified via verifyOtp)
        // We need to find the OTP that was used in verifyOtp flow
        $passwordResetOtp = PasswordResetOtp::where('email', $email)
            ->where('otp', $otp)
            ->where('verified_at', '!=', null)
            ->where('is_used', true)
            ->where('expires_at', '>', now()) // Still within expiry (user must reset within OTP expiry)
            ->first();

        if (!$passwordResetOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi OTP tidak valid. Silakan mulai ulang proses reset password.',
            ], 400);
        }

        // Check if OTP is being reused
        if ($passwordResetOtp->verified_at && $passwordResetOtp->verified_at->addMinutes(config('auth.otp_expires_minutes', 15))->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi OTP sudah expired. Silakan mulai ulang proses reset password.',
            ], 400);
        }

        // Find user and update password
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($user, $newPassword, $passwordResetOtp) {
                // Update password
                $user->password = bcrypt($newPassword);
                $user->save();

                // Invalidate all OTPs for this email (cleanup)
                PasswordResetOtp::invalidateAllForEmail($user->email);
            });

            Log::info('Password reset successfully', [
                'email' => $email,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset. Silakan login dengan password baru Anda.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reset password', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset password. Silakan coba lagi.',
            ], 500);
        }
    }
}
