<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'product_id',
        'transaction_type',
        'quantity',
        'unit_price',    // NEW
        'total_price',    // NEW
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer', // NEW
    ];

    // Relationships
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
