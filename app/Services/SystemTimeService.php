<?php

namespace App\Services;

use App\Models\SystemSetting;
use Carbon\Carbon;

class SystemTimeService
{
    private const TIME_OFFSET_KEY = 'time_offset_hours';

    /**
     * Ambil offset jam saat ini dari system_settings.
     * Default 0 jika belum ada record.
     */
    public function getOffsetHours(): int
    {
        $setting = SystemSetting::where('key', self::TIME_OFFSET_KEY)->first();
        
        if (!$setting) {
            return 0;
        }

        return (int) $setting->value;
    }

    /**
     * Return "waktu sekarang sistem" = now() + offset hours.
     * INI YANG DIPAKAI di semua tempat yang butuh "waktu sekarang sistem".
     */
    public function now(): Carbon
    {
        return now()->addHours($this->getOffsetHours());
    }

    /**
     * Tambahkan offset hours (kumulatif).
     * Return offset baru setelah ditambah.
     */
    public function advanceHours(int $hours): int
    {
        $currentOffset = $this->getOffsetHours();
        $newOffset = $currentOffset + $hours;

        SystemSetting::updateOrCreate(
            ['key' => self::TIME_OFFSET_KEY],
            ['value' => (string) $newOffset]
        );

        return $newOffset;
    }

    /**
     * Reset offset kembali ke 0.
     */
    public function resetOffset(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => self::TIME_OFFSET_KEY],
            ['value' => '0']
        );
    }
}