<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    /**
     * Nonaktifkan timestamps otomatis karena tabel hanya punya created_at
     */
    public $timestamps = false;

    /**
     * Definisikan UPDATED_AT sebagai null agar Eloquent tidak mencoba update kolom yang tidak ada
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'expired_at',
        'max_usage',
        'used_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'value' => 'decimal:2',
        'expired_at' => 'datetime',
        'max_usage' => 'integer',
        'used_count' => 'integer',
    ];

    /**
     * Check if voucher is valid (not expired and has usage remaining)
     */
    public function isValid(): bool
    {
        return $this->used_count < $this->max_usage && now()->lessThanOrEqualTo($this->expired_at);
    }

    /**
     * Check if voucher is expired
     */
    public function isExpired(): bool
    {
        return now()->greaterThan($this->expired_at);
    }

    /**
     * Check if voucher has reached usage limit
     */
    public function isMaxUsageReached(): bool
    {
        return $this->used_count >= $this->max_usage;
    }

    /**
     * Increment used count after successful checkout
     */
    public function incrementUsedCount(): void
    {
        $this->increment('used_count');
    }

    /**
     * Scope to get valid vouchers (not expired and has usage remaining)
     */
    public function scopeValid($query)
    {
        return $query->where('used_count', '<', 'max_usage')
            ->where('expired_at', '>', now());
    }
}
