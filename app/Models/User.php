<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
        'password',
        'role',
        'account_status',
        'status_notes',
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
            'password' => 'hashed',
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
    return $this->hasMany(\App\Models\Wishlist::class, 'user_id');
}

public function wishlistedProducts()
{
    return $this->belongsToMany(\App\Models\Product::class, 'wishlists', 'user_id', 'product_id')
        ->withTimestamps();
}

public function recentlyViewed()
{
    return $this->hasMany(\App\Models\RecentlyViewed::class, 'user_id')
        ->orderByDesc('viewed_at');
}

public function isAdmin(): bool
{
    return in_array($this->role, ['admin', 'super_admin'], true);
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





}
