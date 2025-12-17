<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = [
        'buy_order_id',
        'sell_order_id',
        'price',
        'symbol',
        'amount',
        'commission',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'commission' => 'decimal:8',
    ];
}
