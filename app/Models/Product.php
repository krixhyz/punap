<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'flagged',
        'price',
        'quantity', // NEW
        'type',
        'category',
        'image',
        'status', 
    ];

    protected $casts = [
        'type' => 'array', // important for multi-type support
        'quantity' => 'integer', // NEW
        'flagged' => 'boolean',
    ];


    public function getTypeAttribute($value)
{
    // If it's already JSON or array, return as array
    if (is_array($value)) {
        return $value;
    }

    // If it's null or empty
    if (empty($value)) {
        return [];
    }

    // If it's a single string like "sell", make it an array
    if (is_string($value)) {
        // Try to decode JSON first
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Otherwise, split comma-separated strings
        return explode(',', $value);
    }

    // Fallback — return empty array
    return [];
}


public function owner()
{
    return $this->belongsTo(User::class, 'user_id');
}


public function rentals()
{
    return $this->hasOne(Rental::class);
}

    /** 
     * Get the user that owns the product.
     */
    public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}


public function receivedSwapRequests()
{
    return $this->hasMany(SwapRequest::class, 'product_id');
}

public function offeredSwapRequests()
{
    return $this->hasMany(SwapRequest::class, 'offered_product_id');
}

public function orders()
{
    return $this->hasMany(\App\Models\Order::class); // NEW
}


}
