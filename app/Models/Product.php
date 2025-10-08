<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'price',
        'type',
        'category',
        'image',
    ];

    protected $casts = [
        'type' => 'array', // important for multi-type support
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

}
