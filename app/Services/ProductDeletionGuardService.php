<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\RentalRequest;
use App\Models\RentedRentals;
use App\Models\SwapRequest;

class ProductDeletionGuardService
{
    /**
     * Swap request statuses that indicate an active in-flight swap lifecycle.
     */
    private const OPEN_SWAP_STATUSES = [
        'requested',
        'countered',
        'awaiting_payment',
        'paid',
        'confirmation_pending',
    ];

    /**
     * Rental request statuses that still represent an open obligation.
     */
    private const OPEN_RENTAL_REQUEST_STATUSES = [
        'requested',
        'approved',
    ];

    /**
     * Returns blocking reasons when a product cannot be safely hard-deleted.
     *
     * @return array<int, string>
     */
    public function getBlockers(Product $product): array
    {
        $pendingOrders = Order::where('product_id', $product->id)
            ->where('transaction_type', 'buy')
            ->where('status', 'pending')
            ->count();

        $openRentalRequests = RentalRequest::where('product_id', $product->id)
            ->whereIn('status', self::OPEN_RENTAL_REQUEST_STATUSES)
            ->count();

        $activeRentals = RentedRentals::where('product_id', $product->id)
            ->where('status', 'active')
            ->count();

        $openSwapRequests = SwapRequest::where(function ($query) use ($product) {
                $query->where('product_id', $product->id)
                    ->orWhere('offered_product_id', $product->id);
            })
            ->whereIn('status', self::OPEN_SWAP_STATUSES)
            ->count();

        $reasons = [];

        if ($pendingOrders > 0) {
            $reasons[] = "{$pendingOrders} pending buy order(s)";
        }

        if ($openRentalRequests > 0) {
            $reasons[] = "{$openRentalRequests} open rental request(s)";
        }

        if ($activeRentals > 0) {
            $reasons[] = "{$activeRentals} active rental(s)";
        }

        if ($openSwapRequests > 0) {
            $reasons[] = "{$openSwapRequests} open swap request(s)";
        }

        return $reasons;
    }

    public function canDelete(Product $product): bool
    {
        return $this->getBlockers($product) === [];
    }

    public function blockerMessage(Product $product): string
    {
        $reasons = $this->getBlockers($product);

        if ($reasons === []) {
            return '';
        }

        return 'This product cannot be deleted because it has ' . implode(', ', $reasons) . '.';
    }
}
