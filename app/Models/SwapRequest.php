<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwapRequest extends Model
{
    protected $fillable = [
        'product_id',
        'offered_product_id',
        'owner_id',
        'requester_id',
        'offered_amount',
        'message',
        'status',
    ];

    // relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function offeredProduct()
    {
        return $this->belongsTo(Product::class, 'offered_product_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}
