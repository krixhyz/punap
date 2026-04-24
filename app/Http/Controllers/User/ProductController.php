<?php

namespace App\Http\Controllers\User;

use App\Models\Product;
use App\Models\Category;
use App\Models\Rental;
use App\Models\RentedRentals;
use App\Models\User\Wishlist;
use App\Models\User\RecentlyViewed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\RentalRequest;
use App\Models\SwapRequest;
use App\Models\Review;
use App\Services\ProductDeletionGuardService;
use App\Services\UserVerificationService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;



class ProductController extends Controller
{
    private function hasImageUploadError(Request $request): bool
    {
        $errors = data_get($_FILES, 'images.error', []);

        if (!is_array($errors)) {
            $errors = [$errors];
        }

        foreach ($errors as $errorCode) {
            if (in_array($errorCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION], true)) {
                return true;
            }
        }

        return false;
    }

    public function index(Request $request)
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.products');
        }

        $search = trim((string) $request->query('search', ''));
        $parentCategoryId = $request->query('category');
        $listingType = $request->query('listing_type');
        $minPrice = $request->filled('min_price') && is_numeric($request->query('min_price'))
            ? (float) $request->query('min_price')
            : null;
        $maxPrice = $request->filled('max_price') && is_numeric($request->query('max_price'))
            ? (float) $request->query('max_price')
            : null;

        // Ignore non-positive values and normalize reversed ranges.
        if ($minPrice !== null && $minPrice <= 0) {
            $minPrice = null;
        }
        if ($maxPrice !== null && $maxPrice <= 0) {
            $maxPrice = null;
        }
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $productsQuery = Product::query()
            ->where('status', 'available')
            ->where(function ($q) {
                // For authenticated users: show only APPROVED products
                if (Auth::check()) {
                    $q->where('approval_status', 'APPROVED');
                } else {
                    // For guests: show APPROVED and PENDING products
                    $q->whereIn('approval_status', ['APPROVED', 'PENDING']);
                }
            })
            ->when(Auth::check(), fn($q) => $q->where('user_id', '!=', Auth::id()))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($parentCategoryId, function ($q) use ($parentCategoryId) {
                $q->whereHas('category', function ($inner) use ($parentCategoryId) {
                    $inner->where(function ($subquery) use ($parentCategoryId) {
                        $subquery->where('id', $parentCategoryId)
                            ->orWhere('parent_id', $parentCategoryId);
                    });
                });
            })
            ->when(in_array($listingType, ['sell', 'rent', 'swap'], true), function ($q) use ($listingType) {
                $q->where(function ($inner) use ($listingType) {
                    $inner->whereJsonContains('type', $listingType)
                        ->orWhere('type', 'like', '%"' . $listingType . '"%')
                        ->orWhere('type', $listingType);
                });
            })
            ->when($minPrice !== null || $maxPrice !== null, function ($q) use ($minPrice, $maxPrice) {
                $q->where(function ($priceFilter) use ($minPrice, $maxPrice) {
                    // Sell/swap listings use products.price
                    $priceFilter->where(function ($sellSwapFilter) use ($minPrice, $maxPrice) {
                        $sellSwapFilter->where(function ($typeFilter) {
                            $typeFilter->whereJsonContains('type', 'sell')
                                ->orWhereJsonContains('type', 'swap')
                                ->orWhere('type', 'like', '%"sell"%')
                                ->orWhere('type', 'like', '%"swap"%')
                                ->orWhere('type', 'sell')
                                ->orWhere('type', 'swap');
                        })
                            ->when($minPrice !== null, function ($inner) use ($minPrice) {
                                $inner->where('price', '>=', $minPrice);
                            })
                            ->when($maxPrice !== null, function ($inner) use ($maxPrice) {
                                $inner->where('price', '<=', $maxPrice);
                            });
                    })
                    // Rent listings show deposit on cards, so filter against rent_deposit
                    ->orWhere(function ($rentFilter) use ($minPrice, $maxPrice) {
                        $rentFilter->where(function ($typeFilter) {
                            $typeFilter->whereJsonContains('type', 'rent')
                                ->orWhere('type', 'like', '%"rent"%')
                                ->orWhere('type', 'rent');
                        })
                            ->whereHas('rentals', function ($rentalQuery) use ($minPrice, $maxPrice) {
                                $rentalQuery
                                    ->when($minPrice !== null, function ($inner) use ($minPrice) {
                                        $inner->where('rent_deposit', '>=', $minPrice);
                                    })
                                    ->when($maxPrice !== null, function ($inner) use ($maxPrice) {
                                        $inner->where('rent_deposit', '<=', $maxPrice);
                                    });
                            });
                    });
                });
            })
            ->latest();

        $products = $productsQuery->paginate(12)->withQueryString();

        $wishlistedIds = Auth::check()
            ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray()
            : [];

        $recentlyViewed = collect();
        if (Auth::check()) {
            $recentlyViewed = RecentlyViewed::where('user_id', Auth::id())
                ->with('product.user')
                ->orderByDesc('viewed_at')
                ->limit(6)
                ->get()
                ->filter(fn($r) => $r->product && $r->product->status === 'available' && $r->product->user_id !== Auth::id())
                ->values();
        }

        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('products.index', compact(
            'products',
            'wishlistedIds',
            'recentlyViewed',
            'search',
            'parentCategoryId',
            'parentCategories',
            'listingType',
            'minPrice',
            'maxPrice'
        ));
    }

    public function create()
{
    if (!Auth::user()?->hasVerifiedEmail()) {
        return redirect()->route('verification.notice')
            ->withErrors(['email' => 'Email must be verified to create a product.']);
    }

    $action = route('products.store'); // form submission URL
    $method = 'POST';                  // HTTP method
    
   return view('products.create', compact('action', 'method'));
}


    public function store(Request $request)
    {
        $user = Auth::user();

        $tempImages = $this->normalizeTempImages($request->input('temp_images', []));
        $removeTempImages = $this->normalizeTempImages($request->input('remove_temp_images', []));

        if (!empty($removeTempImages)) {
            $this->deleteTemporaryImages($removeTempImages);
            $tempImages = array_values(array_diff($tempImages, $removeTempImages));
        }

        if ($this->hasImageUploadError($request)) {
            return back()
                ->withInput()
                ->with('temp_product_images', $tempImages)
                ->withErrors(['images' => 'One or more images failed to upload. Make sure each image is 4 MB or smaller.']);
        }

        if ($request->hasFile('images')) {
            $newImageValidator = Validator::make(
                ['images' => $request->file('images')],
                [
                    'images' => 'array|max:6',
                    'images.*' => 'file|image|max:4096',
                ]
            );

            if ($newImageValidator->fails()) {
                return back()
                    ->withInput($request->except('images'))
                    ->with('temp_product_images', $tempImages)
                    ->withErrors($newImageValidator)
                    ->withErrors(['images' => 'One or more images failed to upload. Make sure each image is 4 MB or smaller.']);
            }

            $tempImages = $this->storeUploadedImagesTemporarily($request->file('images'), $tempImages);
        }

        if (count($tempImages) > 6) {
            return back()
                ->withInput($request->except('images'))
                ->with('temp_product_images', $tempImages)
                ->withErrors(['images' => 'You can upload up to 6 images only.']);
        }

        // Check if email is verified
        if (!$user->email_verified_at) {
            return redirect()->route('verification.notice')
                ->withErrors(['email' => 'Email must be verified to create a product.']);
        }

        $selectedTypes = (array) $request->input('listing_type', []);
        if (!in_array('rent', $selectedTypes, true)) {
            // Hidden rent fields can still be posted by the browser; clear them when rent is not selected.
            $request->merge([
                'rent_deposit' => null,
                'rent_fare' => null,
                'rent_type' => null,
                'available_from' => null,
                'end_date' => null,
                'rent_duration' => null,
            ]);
        }

        $validator = Validator::make(array_merge($request->all(), ['images' => $tempImages]), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'condition' => 'required|in:NEW,LIKE_NEW,GOOD,FAIR,WORN_FOR_PARTS',
            'price' => 'required_if:listing_type.*,sell,swap|nullable|numeric|gt:0',
            'listing_type' => 'required|array|min:1',
            'listing_type.*' => 'in:sell,rent,swap',
            'images' => 'nullable|array|max:6',
            'rent_deposit' => 'required_if:listing_type.*,rent|nullable|numeric|min:0',
            'rent_fare' => 'required_if:listing_type.*,rent|nullable|numeric|min:0',
            'rent_type' => 'required_if:listing_type.*,rent|nullable|in:hourly,daily',
            'available_from' => 'required_if:listing_type.*,rent|nullable|date|after_or_equal:today',
            'end_date' => 'required_if:listing_type.*,rent|nullable|date|after_or_equal:available_from',
            'rent_duration' => 'required_if:listing_type.*,rent|nullable|integer|min:1',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput($request->except('images'))
                ->with('temp_product_images', $tempImages)
                ->withErrors($validator);
        }

        $validated = $validator->validated();

   
        // Check 5-product cap for UNVERIFIED users
        $currentProductCount = $user->products()->count();
        $isUserVerified = $user->profile_status === 'VERIFIED';

        if (!$isUserVerified && $currentProductCount >= 5) {
            // User has reached the limit. Trigger auto-evaluation.
            $verificationService = new UserVerificationService();
            $verificationService->evaluateUser($user);
            $user->refresh();

            // After evaluation, check if user is still unverified
            if ($user->profile_status !== 'VERIFIED') {
                return redirect()->back()->withErrors(['listing' => 'You can only create 5 listings until your profile is verified. Complete more transactions with positive reviews to get verified.']);
            }
        }

        // Promote temporary images to final product images
        $imagePaths = $this->promoteTemporaryImages($tempImages);
        $coverImage = $imagePaths[0] ?? null;

        // Determine approval_status based on profile_status
        $approvalStatus = $isUserVerified ? 'APPROVED' : 'PENDING';

        // Create product entry
        $product = Product::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'condition' => $validated['condition'],
            'price' => $validated['price'] ?? null,
            'quantity' => $validated['quantity'],
            'type' => $validated['listing_type'],
            'image' => $coverImage,
            'images' => $imagePaths ?: null,
            'status' => 'available',
            'approval_status' => $approvalStatus,
            'rent_duration' => in_array('rent', $validated['listing_type']) ? $validated['rent_duration'] : null,
        ]);

        // If rent selected, create a rental record
        if (in_array('rent', $validated['listing_type'])) {
            Rental::create([
                'product_id' => $product->id,
                'owner_id' => Auth::id(),
                'rent_fare' => $validated['rent_fare'],
                'rent_deposit' => $validated['rent_deposit'],
                'rent_type' => $validated['rent_type'],
                'available_from' => Carbon::parse($validated['available_from'])->startOfDay(),
                'available_duration' => $validated['rent_duration'],
                'status' => 'available'
            ]);
        }

        $request->session()->forget('temp_product_images');

        return redirect()->route('dashboard')->with('success', 'Listing added successfully!');
    }




  public function edit($id)
{
    $product = Product::where('user_id', Auth::id())->with('rentals')->findOrFail($id);
    $action = route('products.update', $product->id); // form submission URL
    $method = 'PUT';                                 // HTTP method

    return view('products.edit', compact('product', 'action', 'method'));
}


public function update(Request $request, $id)
{
    $product = Product::where('user_id', Auth::id())->findOrFail($id);

    $tempImages = $this->normalizeTempImages($request->input('temp_images', []));
    $removeTempImages = $this->normalizeTempImages($request->input('remove_temp_images', []));

    if (!empty($removeTempImages)) {
        $this->deleteTemporaryImages($removeTempImages);
        $tempImages = array_values(array_diff($tempImages, $removeTempImages));
    }

    if ($this->hasImageUploadError($request)) {
        return back()
            ->withInput()
            ->with('temp_product_images', $tempImages)
            ->withErrors(['images' => 'One or more images failed to upload. Make sure each image is 4 MB or smaller.']);
    }

    if ($request->hasFile('images')) {
        $newImageValidator = Validator::make(
            ['images' => $request->file('images')],
            [
                'images' => 'array|max:6',
                'images.*' => 'file|image|max:4096',
            ]
        );

        if ($newImageValidator->fails()) {
            return back()
                ->withInput($request->except('images'))
                ->with('temp_product_images', $tempImages)
                ->withErrors($newImageValidator)
                ->withErrors(['images' => 'One or more images failed to upload. Make sure each image is 4 MB or smaller.']);
        }

        $tempImages = $this->storeUploadedImagesTemporarily($request->file('images'), $tempImages);
    }

    $selectedTypes = (array) $request->input('listing_type', []);
    if (!in_array('rent', $selectedTypes, true)) {
        // Hidden rent fields can still be posted by the browser; clear them when rent is not selected.
        $request->merge([
            'rent_deposit' => null,
            'rent_fare' => null,
            'rent_type' => null,
            'available_from' => null,
            'end_date' => null,
            'rent_duration' => null,
        ]);
    }

    $validator = Validator::make(array_merge($request->all(), ['images' => $tempImages]), [
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'category_id' => 'required|exists:categories,id',
        'condition' => 'required|in:NEW,LIKE_NEW,GOOD,FAIR,WORN_FOR_PARTS',
        'price' => 'required_if:listing_type.*,sell,swap|nullable|numeric|gt:0',
        'listing_type' => 'required|array|min:1',
        'listing_type.*' => 'in:sell,rent,swap',
        'images' => 'nullable|array|max:6',
        'remove_images' => 'nullable|array',
        'remove_images.*' => 'string',
        'rent_deposit' => 'required_if:listing_type.*,rent|nullable|numeric|min:0',
        'rent_fare' => 'required_if:listing_type.*,rent|nullable|numeric|min:0',
        'rent_type' => 'required_if:listing_type.*,rent|nullable|in:hourly,daily',
        'available_from' => 'required_if:listing_type.*,rent|nullable|date|after_or_equal:today',
        'end_date' => 'required_if:listing_type.*,rent|nullable|date|after_or_equal:available_from',
        'rent_duration' => 'required_if:listing_type.*,rent|nullable|integer|min:1',
        'quantity' => 'required|integer|min:1',
    ]);

    if ($validator->fails()) {
        return back()
            ->withInput($request->except('images'))
            ->with('temp_product_images', $tempImages)
            ->withErrors($validator);
    }

    $validated = $validator->validated();

    // Build existing images list, removing any checked for deletion
    $existingImages = $product->images ?? [];
    $removeImages = $request->input('remove_images', []);
    foreach ($removeImages as $removePath) {
        Storage::disk('public')->delete($removePath);
        $existingImages = array_values(array_filter($existingImages, fn($p) => $p !== $removePath));
    }

    // Promote temporary images and merge with existing
    $promotedTempImages = $this->promoteTemporaryImages($tempImages);
    if (!empty($promotedTempImages)) {
        $existingImages = array_merge($existingImages, $promotedTempImages);
    }

    if (count($existingImages) > 6) {
        return back()
            ->withInput($request->except('images'))
            ->with('temp_product_images', $tempImages)
            ->withErrors(['images' => 'You can upload up to 6 images only.']);
    }

    $coverImage = $existingImages[0] ?? $product->image;

    // Update product
    $product->update([
        'title' => $validated['title'],
        'description' => $validated['description'],
        'category_id' => $validated['category_id'],
        'condition' => $validated['condition'],
        'price' => $validated['price'] ?? null,
        'quantity' => $validated['quantity'],
        'type' => $validated['listing_type'],
        'image' => $coverImage,
        'images' => $existingImages ?: null,
        'rent_duration' => in_array('rent', $validated['listing_type']) ? $validated['rent_duration'] : null,
    ]);

    // Handle rent details
    if (in_array('rent', $validated['listing_type'])) {
        Rental::updateOrCreate(
            ['product_id' => $product->id],
            [
                'owner_id' => Auth::id(),
                'rent_fare' => $validated['rent_fare'],
                'rent_deposit' => $validated['rent_deposit'],
                'rent_type' => $validated['rent_type'],
                'available_from' => Carbon::parse($validated['available_from'])->startOfDay(),
                'available_duration' => $validated['rent_duration'],
                'status' => 'available',
            ]
        );
    } else {
        // If rent was removed, delete its rental record if it exists
        Rental::where('product_id', $product->id)->delete();
    }

    $request->session()->forget('temp_product_images');

    return redirect()->route('products.myListings')->with('success', 'Listing updated successfully!');
}

    private function normalizeTempImages($paths): array
    {
        if (!is_array($paths)) {
            return [];
        }

        $normalized = array_filter(array_map(function ($path) {
            if (!is_string($path)) {
                return null;
            }

            $clean = trim($path);
            if ($clean === '' || !str_starts_with($clean, 'uploads/tmp-products/')) {
                return null;
            }

            return $clean;
        }, $paths));

        return array_values(array_unique($normalized));
    }

    private function storeUploadedImagesTemporarily(array $files, array $existingTempImages = []): array
    {
        $tempImages = $existingTempImages;

        foreach ($files as $file) {
            if (count($tempImages) >= 6) {
                break;
            }

            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $tempImages[] = $file->storeAs('uploads/tmp-products', $filename, 'public');
        }

        return array_values(array_unique($tempImages));
    }

    private function promoteTemporaryImages(array $tempImages): array
    {
        $finalImages = [];

        foreach ($tempImages as $tempPath) {
            if (!str_starts_with($tempPath, 'uploads/tmp-products/')) {
                continue;
            }

            if (!Storage::disk('public')->exists($tempPath)) {
                continue;
            }

            $extension = pathinfo($tempPath, PATHINFO_EXTENSION);
            $finalPath = 'uploads/products/' . time() . '_' . uniqid() . ($extension ? '.' . $extension : '');
            Storage::disk('public')->move($tempPath, $finalPath);
            $finalImages[] = $finalPath;
        }

        return $finalImages;
    }

    private function deleteTemporaryImages(array $tempImages): void
    {
        foreach ($tempImages as $tempPath) {
            if (str_starts_with($tempPath, 'uploads/tmp-products/')) {
                Storage::disk('public')->delete($tempPath);
            }
        }
    }


    public function myListings(ProductDeletionGuardService $deletionGuard)
{
    $user = Auth::user();

    // Eager-load orders for accurate sold calculations
    $products = $user->products()->with('orders')->get(); // UPDATED
    $pendingRequests = RentalRequest::with(['product', 'renter'])
        ->where('owner_id', $user->id)
        ->where('status', 'requested')
        ->latest()
        ->get();

    $activeRentals = RentedRentals::with(['product', 'renter'])
        ->where('owner_id', $user->id)
        ->where('status', 'active')
        ->latest()
        ->get();

    // Sold products with orders
    $soldProducts = $user->products() // CHANGED: include partially sold (has buy orders)
        ->whereHas('orders', function ($q) {
            $q->where('transaction_type', 'buy');
        })
        ->with(['orders' => function ($q) {
            $q->where('transaction_type', 'buy');
        }])
        ->get();

    $swapRequests = SwapRequest::with(['requestedProduct', 'offeredProduct', 'requester'])
        ->where('owner_id', $user->id)
        ->where('status', 'requested')
        ->latest()
        ->get();

    $activeSwaps = SwapRequest::with(['requestedProduct', 'offeredProduct', 'requester'])
        ->where('owner_id', $user->id)
        ->whereIn('status', ['countered', 'awaiting_payment', 'paid'])
        ->latest()
        ->get();

    $canDeleteByProduct = [];
    $deleteBlockersByProduct = [];
    foreach ($products as $product) {
        $blockerMessage = $deletionGuard->blockerMessage($product);
        $canDeleteByProduct[$product->id] = $blockerMessage === '';
        $deleteBlockersByProduct[$product->id] = $blockerMessage;
    }

    return view('products.my_listings', compact(
        'products',
        'pendingRequests',
        'activeRentals',
        'soldProducts',
        'swapRequests',
        'activeSwaps',
        'canDeleteByProduct',
        'deleteBlockersByProduct'
    ));
}



    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:available,sold,rented,swapped',
        ]);

        $product = Product::where('user_id', Auth::id())->findOrFail($id);

        // If attempting to mark sold but still has units, block it
        if ($request->status === 'sold' && $product->quantity > 0) {
            return redirect()->back()
                ->with('error', 'Cannot mark as sold while quantity > 0. Quantity must reach 0 after purchases.');
        }

        // Only allow sold when quantity == 0
        if ($request->status === 'sold' && $product->quantity === 0) {
            $product->status = 'sold';
        } else {
            // For other statuses just set directly
            $product->status = $request->status;
        }

        $product->save();

        return redirect()->back()->with('success', 'Product status updated successfully!');
    }

    public function destroy($id, ProductDeletionGuardService $deletionGuard)
    {
        $product = Product::where('user_id', Auth::id())->findOrFail($id);

        $blockerMessage = $deletionGuard->blockerMessage($product);
        if ($blockerMessage !== '') {
            return redirect()->route('products.myListings')->with('error', $blockerMessage);
        }

        // Delete all associated images from storage
        $allImages = array_filter(array_merge(
            $product->images ?? [],
            $product->image ? [$product->image] : []
        ));
        foreach (array_unique($allImages) as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $product->delete();

        return redirect()->route('products.myListings')->with('success', 'Product deleted successfully!');
    }

public function myPurchases()
{
    $user = Auth::user();

    // Pending rental requests made by this user (not yet approved/paid)
    $pendingRentalRequests = RentalRequest::with(['product', 'owner'])
        ->where('renter_id', $user->id)
        ->where('status', 'requested')
        ->latest()
        ->get();

    // Approved rental requests awaiting payment
    $approvedRentalRequests = RentalRequest::with(['product', 'owner'])
        ->where('renter_id', $user->id)
        ->where('status', 'approved')
        ->latest()
        ->get();

    // Rented items (approved rentals)
    $rentedRentals = RentedRentals::with('product', 'owner')
        ->where('renter_id', $user->id)
        ->where('status', 'active')
        ->orderByDesc('created_at')
        ->get();

    // Purchased products (buy transactions only)
    $orders = $user->orders()
        ->with('product')
        ->where('transaction_type', 'buy')
        ->orderByDesc('created_at')
        ->get();

     // Swaps involving this user
    $swaps = \App\Models\Swap::where(function($query) use ($user) {
            $query->where('owner_a_id', $user->id)
                  ->orWhere('owner_b_id', $user->id);
        })
        ->where('status', 'completed') // show only completed swaps
        ->with(['requestedProduct', 'offeredProduct'])
        ->latest()
        ->get();

    // Active (pending/countered) swap requests made by this user
    $pendingSwapRequests = \App\Models\SwapRequest::with(['product', 'offeredProduct', 'owner'])
        ->where('requester_id', $user->id)
        ->whereIn('status', ['requested', 'countered'])
        ->latest()
        ->get();

    $awaitingSwapPaymentRequests = \App\Models\SwapRequest::with(['product', 'offeredProduct', 'owner'])
        ->where('requester_id', $user->id)
        ->where('status', 'awaiting_payment')
        ->latest()
        ->get();

    $orderReviewedIds = Review::where('reviewer_id', $user->id)
        ->whereNotNull('order_id')
        ->pluck('order_id')
        ->map(fn($id) => (int) $id)
        ->all();

    $rentalReviewedIds = Review::where('reviewer_id', $user->id)
        ->whereNotNull('rented_rental_id')
        ->pluck('rented_rental_id')
        ->map(fn($id) => (int) $id)
        ->all();

    $swapReviewedIds = Review::where('reviewer_id', $user->id)
        ->whereNotNull('swap_id')
        ->pluck('swap_id')
        ->map(fn($id) => (int) $id)
        ->all();

    return view('products.my_purchases', compact(
        'rentedRentals',
        'pendingRentalRequests',
        'approvedRentalRequests',
        'orders',
        'swaps',
        'pendingSwapRequests',
        'awaitingSwapPaymentRequests',
        'orderReviewedIds',
        'rentalReviewedIds',
        'swapReviewedIds'
    ));
}

public function show($id)
{
    if (Auth::check() && Auth::user()->isAdmin()) {
        return redirect()->route('admin.products.show', $id);
    }

    $product = Product::with(['user', 'rentals'])->findOrFail($id);

    // Check if the user is authorized to view this product
    $isOwner = Auth::check() && $product->user_id === Auth::id();
    if (!$isOwner && $product->approval_status !== 'APPROVED') {
        abort(404, 'Product not found or not approved for public viewing.');
    }

    // Track recently viewed for authenticated users (not the owner)
    if (Auth::check() && $product->user_id !== Auth::id()) {
        RecentlyViewed::updateOrCreate(
            ['user_id' => Auth::id(), 'product_id' => $product->id],
            ['viewed_at' => now()]
        );

        // Keep only the 10 most recent per user
        $idsToKeep = RecentlyViewed::where('user_id', Auth::id())
            ->orderByDesc('viewed_at')
            ->limit(10)
            ->pluck('id');
        RecentlyViewed::where('user_id', Auth::id())
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }

    $isWishlisted = Auth::check()
        ? Wishlist::where('user_id', Auth::id())->where('product_id', $product->id)->exists()
        : false;

    // Calculate seller's average rating and review count from user-targeted reviews.
    $ownerReviews = \App\Models\Review::where('reviewee_id', $product->user_id)
        ->whereNotNull('rating');
    $ownerCount = (int) $ownerReviews->count();
    $ownerAvg = $ownerCount > 0 ? (float) $ownerReviews->avg('rating') : 0;

    return view('products.show', compact('product', 'isWishlisted', 'ownerAvg', 'ownerCount'));
}

public function buy($id)
{
    $product = Product::with(['user', 'rentals'])->findOrFail($id);
    
    // Check if the user is authorized to view this product
    $isOwner = Auth::check() && $product->user_id === Auth::id();
    if (!$isOwner && $product->approval_status !== 'APPROVED') {
        abort(404, 'Product not found or not approved for public viewing.');
    }
    
    // Check if product is available for buying
    if (!in_array('sell', $product->type)) {
        abort(404, 'This product is not available for purchase.');
    }
    
    // Redirect to buyer-bound checkout link
    $checkoutUrl = URL::temporarySignedRoute(
        'order.checkout.product',
        now()->addMinutes(15),
        [
            'product' => $product->id,
            'quantity' => 1,
            'buyer' => Auth::id(),
        ]
    );

    return redirect($checkoutUrl);
}

public function rent($id)
{
    $product = Product::with(['user', 'rentals'])->findOrFail($id);
    
    // Check if the user is authorized to view this product
    $isOwner = Auth::check() && $product->user_id === Auth::id();
    if (!$isOwner && $product->approval_status !== 'APPROVED') {
        abort(404, 'Product not found or not approved for public viewing.');
    }
    
    // Check if product is available for renting
    if (!in_array('rent', $product->type) || !$product->rentals) {
        abort(404, 'This product is not available for rental.');
    }

    // Prevent owner from entering renter flow
    if (Auth::check() && $product->user_id === Auth::id()) {
        return redirect()->route('products.show', $product->id)
            ->with('error', 'You cannot rent your own item.');
    }
    
    // Redirect to rental request form
    return redirect()->route('rental.create', $product->id);
}

public function swap($id)
{
    $product = Product::with(['user', 'rentals'])->findOrFail($id);
    
    // Check if the user is authorized to view this product
    $isOwner = Auth::check() && $product->user_id === Auth::id();
    if (!$isOwner && $product->approval_status !== 'APPROVED') {
        abort(404, 'Product not found or not approved for public viewing.');
    }
    
    // Check if product is available for swapping
    if (!in_array('swap', $product->type)) {
        abort(404, 'This product is not available for swapping.');
    }
    
    // Redirect to swap request form
    return redirect()->route('swap.request.form', $product->id);
}

}