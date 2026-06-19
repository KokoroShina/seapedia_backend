<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    // Tabel tidak memiliki kolom updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'driver_id',
        'status',
        'taken_at',
        'completed_at',
        'due_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
