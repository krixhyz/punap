<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id','owner_id','rent_fare','rent_deposit','rent_type','available_from','available_duration','status'
    ];

    protected $casts = [

    'available_from' => 'datetime',
];



    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }
}
