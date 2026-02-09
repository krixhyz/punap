<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentedRentals extends Model
{
    protected $table = 'rented_rentals';

    protected $fillable = [
        'rental_id','product_id','owner_id','renter_id',
        'rent_fare','rent_deposit','rent_type','duration',
        'start_date','end_date','total_amount','payment_status','payment_reference','status'
    ];

    protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
];

    public function rental() { return $this->belongsTo(Rental::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function owner() { return $this->belongsTo(User::class,'owner_id'); }
    public function renter() { return $this->belongsTo(User::class,'renter_id'); }
}
