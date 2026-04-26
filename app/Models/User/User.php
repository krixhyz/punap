<?php

namespace App\Models\User;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Province;
use App\Models\City;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'address',
        'province_id',
        'city_id',
        'password',
        'role',
        'account_status',
        'status_notes',
        'profile_status',
        'terms_accepted_at',
        'total_eco_score',
        'eco_level',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
            'profile_status' => 'string',
            'total_eco_score' => 'decimal:2',
            'eco_level' => 'string',
        ];
    }
    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'user_id');
    }

    public function ownedRentals()
{
    return $this->hasMany(Rental::class, 'owner_id');
}



public function products()
{
    return $this->hasMany(\App\Models\Product::class, 'user_id');
}

// In User.php
public function orders()
{
    return $this->hasMany(\App\Models\Order::class, 'buyer_id'); 
}
// All listings created by user as owner
public function rentalListings()
{
    return $this->hasMany(\App\Models\Rental::class, 'owner_id');
}

// All rentals that the user has rented (approved/completed rentals)
public function rentedItems()
{
    return $this->hasMany(\App\Models\RentedRentals::class, 'renter_id');
}

// All requests made by the user
public function rentalRequests()
{
    return $this->hasMany(\App\Models\RentalRequest::class, 'renter_id');
}

// Requests user received as owner (incoming requests)
public function incomingRentalRequests()
{
    return $this->hasMany(\App\Models\RentalRequest::class, 'owner_id');
}

// Final rentals approved where user is the owner (earned revenue)
public function approvedRentalsAsOwner()
{
    return $this->hasMany(\App\Models\RentedRentals::class, 'owner_id');
}

public function processedRentalDeposits()
{
    return $this->hasMany(\App\Models\RentalDeposit::class, 'processed_by');
}

public function wallet()
{
    return $this->hasOne(\App\Models\Wallet::class, 'user_id')
        ->where('wallet_type', 'user');
}

public function payoutRequests()
{
    return $this->hasMany(\App\Models\PayoutRequest::class, 'user_id');
}

public function swapRequestsMade()
{
    return $this->hasMany(SwapRequest::class, 'requester_id');
}

public function swapRequestsReceived()
{
    return $this->hasMany(SwapRequest::class, 'owner_id');
}

public function wishlist()
{
    return $this->hasMany(Wishlist::class, 'user_id');
}

public function wishlistedProducts()
{
    return $this->belongsToMany(\App\Models\Product::class, 'wishlists', 'user_id', 'product_id')
        ->withTimestamps();
}

public function recentlyViewed()
{
    return $this->hasMany(RecentlyViewed::class, 'user_id')
        ->orderByDesc('viewed_at');
}

public function province()
{
    return $this->belongsTo(Province::class);
}

public function city()
{
    return $this->belongsTo(City::class);
}

public function isAdmin(): bool
{
    return in_array($this->role, ['admin', 'super_admin'], true);
}

/**
 * Get all eco score records for this user
 */
public function ecoScores()
{
    return $this->hasMany(\App\Models\UserEcoScore::class);
}

/**
 * Get the latest eco score for this user
 */
public function latestEcoScore()
{
    return $this->hasOne(\App\Models\UserEcoScore::class)->latestOfMany();
}

/**
 * Get user's cumulative eco score
 */
public function getTotalEcoScore()
{
    if ($this->total_eco_score !== null) {
        return (float) $this->total_eco_score;
    }

    return (float) $this->ecoScores()->sum('eco_points_awarded');
}

/**
 * Get user's current eco level
 */
public function getCurrentEcoLevel()
{
    if (!empty($this->eco_level)) {
        return $this->eco_level;
    }

    $total = $this->getTotalEcoScore();
    return \App\Models\UserEcoScore::calculateEcoLevel($total);
}

/**
 * Get eco score statistics for user
 */
public function getEcoStats()
{
    $scores = $this->ecoScores()->get();
    $total = $scores->sum('eco_points_awarded');
    
    return [
        'total_eco_points' => $total,
        'eco_level' => \App\Models\UserEcoScore::calculateEcoLevel($total),
        'transaction_count' => $scores->count(),
        'sells' => $scores->where('transaction_type', 'sell')->sum('eco_points_awarded'),
        'rentals' => $scores->where('transaction_type', 'rent')->sum('eco_points_awarded'),
        'swaps' => $scores->where('transaction_type', 'swap')->sum('eco_points_awarded'),
        'last_activity' => $scores->first()->created_at ?? null,
    ];
}

public function isSuperAdmin(): bool
{
    return $this->role === 'super_admin';
}

public function canAccessAdminPanel(): bool
{
    return $this->isAdmin();
}

public function canManageRoles(): bool
{
    return $this->isSuperAdmin();
}

public function canAccessSensitiveAdminData(): bool
{
    return $this->isSuperAdmin();
}

public function canConfigureSystem(): bool
{
    return $this->isSuperAdmin();
}

public function canManageUser(User $target): bool
{
    if ($this->isSuperAdmin()) {
        return true;
    }

    if (! $this->isAdmin()) {
        return false;
    }

    return $target->role === 'user';
}

public function isSuspended(): bool
{
    return $this->account_status === 'suspended';
}

public function isBanned(): bool
{
    return $this->account_status === 'banned';
}

// Profile verification methods
public function isVerified(): bool
{
    return $this->profile_status === 'VERIFIED';
}

public function isUnverified(): bool
{
    return $this->profile_status === 'UNVERIFIED';
}

public function getProductCountAttribute(): int
{
    return $this->products()->count();
}




}
