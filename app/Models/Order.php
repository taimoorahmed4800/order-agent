<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'customer_email', 'status', 'items', 'total',
        'tracking_number', 'estimated_delivery', 'delivered_at',
    ];

    protected $casts = [
        'estimated_delivery' => 'date',
        'delivered_at'       => 'datetime',
    ];
}
