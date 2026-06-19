<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
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
        'min_purchase',
        'expired_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'expired_at' => 'datetime',
    ];

    /**
     * Check if promo is valid (not expired)
     */
    public function isValid(): bool
    {
        return now()->lessThanOrEqualTo($this->expired_at);
    }

    /**
     * Check if promo is expired
     */
    public function isExpired(): bool
    {
        return now()->greaterThan($this->expired_at);
    }

    /**
     * Check if subtotal meets minimum purchase requirement
     */
    public function meetsMinPurchase(float $subtotal): bool
    {
        return $subtotal >= (float) $this->min_purchase;
    }

    /**
     * Scope to get valid promos (not expired and meets min purchase)
     */
    public function scopeValid($query, float $subtotal = 0)
    {
        return $query->where('expired_at', '>', now())
            ->where('min_purchase', '<=', $subtotal);
    }

    /**
     * Scope to order by highest value (percentage)
     */
    public function scopeOrderByHighestValue($query)
    {
        return $query->orderByDesc('value');
    }
}
