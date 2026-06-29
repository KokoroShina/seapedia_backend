<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function user_can_request_otp_for_password_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/forgot-password/send-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Kode OTP telah dikirim ke email Anda.',
            ]);

        // Assert OTP was created
        $this->assertDatabaseHas('password_reset_otps', [
            'email' => 'test@example.com',
        ]);

        // Assert email was sent
        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo('test@example.com');
        });
    }

    /** @test */
    public function send_otp_returns_success_for_non_existent_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password/send-otp', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should return success to prevent email enumeration
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Jika email tersebut terdaftar, kode OTP telah dikirim.',
            ]);

        // No OTP should be created
        $this->assertDatabaseMissing('password_reset_otps', [
            'email' => 'nonexistent@example.com',
        ]);

        // No email should be sent
        Mail::assertNotSent(OtpMail::class);
    }

    /** @test */
    public function send_otp_validates_email_format(): void
    {
        $response = $this->postJson('/api/auth/forgot-password/send-otp', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_verify_valid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Create OTP directly
        $otp = PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(15),
            'is_used' => false,
        ]);

        $response = $this->postJson('/api/auth/forgot-password/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Kode OTP terverifikasi. Silakan reset password Anda.',
            ]);

        // Assert OTP is marked as used
        $this->assertDatabaseHas('password_reset_otps', [
            'email' => 'test@example.com',
            'is_used' => true,
        ]);
    }

    /** @test */
    public function user_cannot_verify_invalid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(15),
            'is_used' => false,
        ]);

        $response = $this->postJson('/api/auth/forgot-password/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '999999', // Wrong OTP
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Kode OTP tidak valid atau sudah expired.',
            ]);
    }

    /** @test */
    public function user_cannot_verify_expired_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->subMinutes(1), // Expired
            'is_used' => false,
        ]);

        $response = $this->postJson('/api/auth/forgot-password/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Kode OTP tidak valid atau sudah expired.',
            ]);
    }

    /** @test */
    public function user_can_reset_password_with_verified_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $oldPassword = $user->password;

        // Create verified OTP
        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(15),
            'verified_at' => now(),
            'is_used' => true,
        ]);

        $response = $this->postJson('/api/auth/forgot-password/reset-password', [
            'email' => 'test@example.com',
            'otp' => '123456',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password berhasil direset. Silakan login dengan password baru Anda.',
            ]);

        // Assert password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password));
        $this->assertFalse(Hash::check('oldpassword', $user->password));
    }

    /** @test */
    public function user_cannot_reset_password_with_unverified_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        // Create unverified OTP
        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(15),
            'is_used' => false,
        ]);

        $response = $this->postJson('/api/auth/forgot-password/reset-password', [
            'email' => 'test@example.com',
            'otp' => '123456',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Sesi OTP tidak valid. Silakan mulai ulang proses reset password.',
            ]);
    }

    /** @test */
    public function reset_password_validates_password_requirements(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(15),
            'verified_at' => now(),
            'is_used' => true,
        ]);

        // Password too short
        $response = $this->postJson('/api/auth/forgot-password/reset-password', [
            'email' => 'test@example.com',
            'otp' => '123456',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Password confirmation mismatch
        $response = $this->postJson('/api/auth/forgot-password/reset-password', [
            'email' => 'test@example.com',
            'otp' => '123456',
            'password' => 'NewPassword123',
            'password_confirmation' => 'DifferentPassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function otp_is_invalidated_when_new_otp_is_requested(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Create first OTP
        $firstOtp = PasswordResetOtp::create([
            'email' => 'test@example.com',
            'otp' => '111111',
            'expires_at' => now()->addMinutes(15),
            'is_used' => false,
        ]);

        // Request new OTP
        $this->postJson('/api/auth/forgot-password/send-otp', [
            'email' => 'test@example.com',
        ]);

        // First OTP should be invalidated
        $this->assertDatabaseHas('password_reset_otps', [
            'email' => 'test@example.com',
            'otp' => '111111',
            'is_used' => true,
        ]);

        // New OTP should exist
        $this->assertDatabaseCount('password_reset_otps', 2);
    }
}
