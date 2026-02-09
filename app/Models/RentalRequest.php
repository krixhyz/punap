<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'product_id',
        'owner_id',
        'renter_id',
        'start_date',
        'end_date',
        'duration',
        'total_amount',
        'rent_deposit',
        'status',
    ];

    /** Relationships **/

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
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
