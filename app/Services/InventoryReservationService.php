<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\RentalRequest;
use App\Models\SwapRequest;
use Illuminate\Support\Facades\DB;

class InventoryReservationService
{
    public function ensurePurchasableQuantity(Product $product, int $requestedQty, $asOf = null): void
    {
        $asOf = $asOf ?? now();

        if ($requestedQty < 1) {
            throw new \RuntimeException('Quantity must be at least 1.');
        }

        if ($product->status === 'sold') {
            throw new \RuntimeException('This product is already sold.');
        }

        if ($product->quantity < 1) {
            throw new \RuntimeException('No available stock.');
        }

        $reservedQty = Order::where('product_id', $product->id)
            ->where('status', 'pending')
            ->where('reserved_until', '>', $asOf)
            ->sum('quantity');

        $availableQty = $product->quantity - $reservedQty;
        if ($availableQty < $requestedQty) {
            throw new \RuntimeException('Requested quantity exceeds available stock.');
        }
    }

    public function consumeProductQuantity(Product $product, int $quantity, string $depletedStatus = 'sold'): void
    {
        if ($quantity < 1) {
            return;
        }

        $product->quantity -= $quantity;
        if ($product->quantity <= 0) {
            $product->quantity = 0;
            $product->status = $depletedStatus;
        } else {
            $this->normalizeAvailableStatus($product);
        }
        $product->save();
    }

    public function reserveRentalRequest(RentalRequest $rentalRequest, int $reservationMinutes): void
    {
        DB::transaction(function () use ($rentalRequest, $reservationMinutes) {
            $lockedRequest = RentalRequest::lockForUpdate()->findOrFail($rentalRequest->id);

            if ($lockedRequest->status !== 'requested') {
                throw new \RuntimeException('This request has already been processed.');
            }

            $product = Product::lockForUpdate()->find($lockedRequest->product_id);
            if (!$product || $product->quantity < 1) {
                throw new \RuntimeException('No stock available for this rental request.');
            }

            $this->consumeProductQuantity($product, 1, 'rented');

            $lockedRequest->status = 'approved';
            $lockedRequest->stock_reserved = true;
            $lockedRequest->reserved_until = now()->addMinutes($reservationMinutes);
            $lockedRequest->save();
        });
    }

    public function releaseRentalReservation(RentalRequest $rentalRequest): void
    {
        DB::transaction(function () use ($rentalRequest) {
            $lockedRequest = RentalRequest::lockForUpdate()->find($rentalRequest->id);
            if (!$lockedRequest || $lockedRequest->status !== 'approved') {
                return;
            }

            if ($lockedRequest->stock_reserved) {
                $product = Product::lockForUpdate()->find($lockedRequest->product_id);
                if ($product) {
                    $product->quantity += 1;
                    $this->normalizeAvailableStatus($product);
                    $product->save();
                }
            }

            $lockedRequest->stock_reserved = false;
            $lockedRequest->reserved_until = null;
            $lockedRequest->status = 'cancelled';
            $lockedRequest->save();
        });
    }

    public function reserveSwapItems(SwapRequest $swapRequest): void
    {
        DB::transaction(function () use ($swapRequest) {
            $reqProduct = Product::lockForUpdate()->find($swapRequest->product_id);
            $offProduct = Product::lockForUpdate()->find($swapRequest->offered_product_id);

            if (!$reqProduct || !$offProduct) {
                throw new \RuntimeException('Swap products are not available.');
            }

            if ($reqProduct->quantity < 1 || $offProduct->quantity < 1) {
                throw new \RuntimeException('Insufficient stock for swap.');
            }

            $this->consumeProductQuantity($reqProduct, 1, 'swapped');
            $this->consumeProductQuantity($offProduct, 1, 'swapped');
        });
    }

    public function releaseSwapReservation(SwapRequest $swapRequest): void
    {
        DB::transaction(function () use ($swapRequest) {
            $reqProduct = Product::lockForUpdate()->find($swapRequest->product_id);
            $offProduct = Product::lockForUpdate()->find($swapRequest->offered_product_id);

            if ($reqProduct) {
                $reqProduct->quantity += 1;
                $this->normalizeAvailableStatus($reqProduct);
                $reqProduct->save();
            }

            if ($offProduct) {
                $offProduct->quantity += 1;
                $this->normalizeAvailableStatus($offProduct);
                $offProduct->save();
            }

            $swapRequest->status = 'cancelled';
            $swapRequest->reserved_until = null;
            $swapRequest->save();
        });
    }

    public function normalizeAvailableStatus(Product $product): void
    {
        if ($product->quantity > 0 && in_array($product->status, ['rented', 'sold', 'swapped'], true)) {
            $product->status = 'available';
        }
    }
}
