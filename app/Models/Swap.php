<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Swap extends Model
{
    protected $fillable = [
        'swap_request_id',
        'product_a_id',
        'product_b_id',
        'owner_a_id',
        'owner_b_id',
        'offered_amount',
        'notes',
        'status',
    ];

    public function request()
    {
        return $this->belongsTo(SwapRequest::class, 'swap_request_id');
    }

    public function requestedProduct()
    {
        return $this->belongsTo(Product::class, 'product_a_id');
    }

    public function offeredProduct()
    {
        return $this->belongsTo(Product::class, 'product_b_id');
    }

    public function ownerA()
    {
        return $this->belongsTo(User::class, 'owner_a_id');
    }

    public function ownerB()
    {
        return $this->belongsTo(User::class, 'owner_b_id');
    }
}
