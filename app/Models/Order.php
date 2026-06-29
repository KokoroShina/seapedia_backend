<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'buyer_id', 'store_id', 'address_id', 'voucher_id', 'promo_id', 'delivery_method','subtotal', 'discount_amount', 'delivery_fee', 'ppn', 'total','status',

    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);

        
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }
}
