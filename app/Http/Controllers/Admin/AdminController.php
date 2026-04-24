<?php

namespace App\Http\Controllers\Admin;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Admin\PlatformSetting;
use App\Models\Product;
use App\Models\RentalDeposit;
use App\Models\RentedRentals;
use App\Models\RentalRequest;
use App\Models\Review;
use App\Models\Swap;
use App\Models\SwapRequest;
use App\Models\User\User;
use App\Notifications\User\DisputeStatusUpdated;
use App\Services\ProductDeletionGuardService;
use App\Services\RentalDepositRefundService;
use App\Services\WalletLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use App\Services\UserVerificationService;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function dashboard()
    {
        $admin = auth()->user();

        $users = User::latest()->take(20)->get();
        // Show products that are either pending approval OR flagged
        $products = Product::where('approval_status', 'PENDING')
            ->orWhere('flagged', true)
            ->with('user')
            ->latest()
            ->take(20)
            ->get();
        $recentDisputes = Dispute::where('status', 'open')->orWhere('status', 'in_review')->with('reporter')->latest()->take(5)->get();
        // Show users with unverified profiles (awaiting admin verification)
        $pendingVerifications = User::where('role', 'user')
            ->where('profile_status', 'UNVERIFIED')
            ->latest()
            ->take(5)
            ->get();

        $totalUsers = User::count();
        $totalProducts = Product::count();
        $totalAdmins = User::whereIn('role', ['admin', 'super_admin'])->count();
        $totalSuperAdmins = User::where('role', 'super_admin')->count();
        $flaggedProducts = Product::where('flagged', true)->count();
        $openDisputes = Dispute::where('status', 'open')->count();
        $totalReviews = Review::count();
        $pendingProfileVerifications = User::where('role', 'user')
            ->where('profile_status', 'UNVERIFIED')
            ->count();
        $reportedItems = Dispute::count() + $flaggedProducts;
        $activeUsers = User::where('account_status', 'active')->count();
        $totalOrders = Order::count();
        $totalServiceFeesEarned = (float) Payment::where('status', 'complete')->sum('fee_amount');
        $pendingPayments = Payment::where('status', 'pending')->count();
        $completedRentals = RentedRentals::whereIn('status', ['completed', 'returned'])->count();
        $swapCount = Swap::count();
        $completedTransactions = Order::where('status', 'completed')->count() + $completedRentals + Swap::where('status', 'completed')->count();
        $monthlyRevenue = (float) Payment::where('status', 'complete')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('fee_amount');

        return view('admin.dashboard', [
            'users' => $users,
            'products' => $products,
            'recentDisputes' => $recentDisputes,
            'pendingVerifications' => $pendingVerifications,
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalAdmins' => $totalAdmins,
            'totalSuperAdmins' => $totalSuperAdmins,
            'flaggedProducts' => $flaggedProducts,
            'openDisputes' => $openDisputes,
            'totalReviews' => $totalReviews,
            'pendingProfileVerifications' => $pendingProfileVerifications,
            'reportedItems' => $reportedItems,
            'activeUsers' => $activeUsers,
            'totalOrders' => $totalOrders,
            'totalServiceFeesEarned' => $totalServiceFeesEarned,
            'pendingPayments' => $pendingPayments,
            'completedRentals' => $completedRentals,
            'swapCount' => $swapCount,
            'completedTransactions' => $completedTransactions,
            'monthlyRevenue' => $monthlyRevenue,
            'isSuperAdmin' => $admin->isSuperAdmin(),
        ]);
    }

    public function processDeposit(Request $request, RentalDeposit $rentalDeposit, RentalDepositRefundService $refundService)
    {
        $admin = auth()->user();

        if (!$admin->isAdmin()) {
            abort(403, 'You do not have permission to process deposits.');
        }

        $validated = $request->validate([
            'condition' => 'required|in:good,minor_damage,major_damage',
            'deduction_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        if ($rentalDeposit->status !== 'held') {
            return back()->with('error', 'This deposit has already been processed.');
        }

        try {
            $result = DB::transaction(function () use ($rentalDeposit, $validated, $admin) {
                $amount = (float) $rentalDeposit->amount;
                $deductionAmount = 0.0;
                $refundAmount = $amount;
                $status = 'refunded';

                if ($validated['condition'] === 'minor_damage') {
                    $deductionAmount = (float) ($validated['deduction_amount'] ?? 0);

                    if ($deductionAmount > $amount) {
                        throw new \RuntimeException('Deduction amount cannot exceed the original deposit.');
                    }

                    $refundAmount = max($amount - $deductionAmount, 0);
                    $status = $refundAmount > 0 ? 'partial' : 'forfeited';
                }

                if ($validated['condition'] === 'major_damage') {
                    $deductionAmount = $amount;
                    $refundAmount = 0;
                    $status = 'forfeited';
                }

                $rentalDeposit->update([
                    'deduction_amount' => $deductionAmount,
                    'refund_amount' => $refundAmount,
                    'status' => $status,
                    'notes' => $validated['notes'] ?? null,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                    'refund_status' => $refundAmount > 0 ? 'processing' : 'success',
                    'refund_requested_at' => $refundAmount > 0 ? now() : null,
                    'refund_completed_at' => $refundAmount > 0 ? null : now(),
                    'refund_failed_at' => null,
                    'failure_reason' => null,
                ]);

                return $rentalDeposit->fresh(['rentedRental', 'processedBy', 'payment']);
            });
        } catch (\Throwable $e) {
            \Log::error('Deposit processing failed', [
                'rental_deposit_id' => $rentalDeposit->id,
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }

        if ((float) $result->refund_amount > 0) {
            $refundResponse = $refundService->refund($result);

            if (!($refundResponse['ok'] ?? false)) {
                $result->update([
                    'refund_status' => 'failed',
                    'refund_failed_at' => now(),
                    'failure_reason' => data_get($refundResponse, 'message', 'Refund request failed.'),
                ]);

                \Log::warning('Deposit refund request failed', [
                    'rental_deposit_id' => $result->id,
                    'payment_id' => $result->payment_id,
                    'provider' => $result->gateway,
                    'response' => $refundResponse,
                ]);

                return back()->with('error', 'Deposit was processed, but the refund request failed.');
            }

            $result->update([
                'refund_status' => 'success',
                'refund_reference' => data_get($refundResponse, 'body.refund_id') ?? data_get($refundResponse, 'body.reference') ?? data_get($refundResponse, 'body.id'),
                'refund_completed_at' => now(),
                'failure_reason' => null,
            ]);
        }

        return back()->with('success', 'Deposit processed successfully. Refund status: ' . $result->refund_status . '.');
    }

    public function userStore(Request $request)
    {
        $admin = auth()->user();

        // Validate that admin has permission to create
        if (!$admin->isAdmin()) {
            abort(403, 'You do not have permission to create users.');
        }

        // Determine allowed roles based on admin level
        $allowedRoles = $admin->isSuperAdmin() ? ['user', 'admin'] : ['user'];
        $roleValidation = 'required|in:' . implode(',', $allowedRoles);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|max:64',
            'role' => $roleValidation,
            'account_status' => 'nullable|in:active,suspended,banned',
            'phone_number' => 'nullable|string|regex:/^[0-9]{10}$/|size:10',
            'address' => 'nullable|string|max:1000',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
        ], [
            'role.in' => 'You do not have permission to create users with the selected role.',
            'phone_number.regex' => 'Phone number must be exactly 10 digits.',
            'phone_number.size' => 'Phone number must be exactly 10 digits.',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'account_status' => $data['account_status'] ?? 'active',
            'phone_number' => $data['phone_number'] ?? null,
            'address' => $data['address'] ?? null,
            'province_id' => $data['province_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
        ]);

        $roleLabel = ucfirst($data['role']);
        return redirect()->route('admin.users')->with('success', "{$roleLabel} created successfully.");
    }

    public function users(Request $request)
    {
        $admin = auth()->user();
        $query = User::query()
            ->withCount(['products', 'orders'])
            ->withSum('ecoScores as total_eco_score', 'eco_points_awarded');

        if (! $admin->isSuperAdmin()) {
            $query->where('role', 'user');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $provinces = \App\Models\Province::orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', compact('users', 'admin', 'provinces'));
    }

    public function userShow($id)
    {
        $admin = auth()->user();
        $user = User::findOrFail($id);

        if (! $admin->canManageUser($user) && ! $admin->isSuperAdmin()) {
            abort(403, 'You are not allowed to access this user.');
        }

        $products = $user->products()->latest()->get();
        $orders = $user->orders()->with('product')->latest()->take(20)->get();
        $reviews = Review::where('reviewee_id', $user->id)->latest()->take(10)->get();
        $payments = collect();

        if ($admin->canAccessSensitiveAdminData()) {
            $payments = Payment::where('user_id', $user->id)->latest()->take(20)->get();
        }

        // Get verification metrics
        $verificationService = new UserVerificationService();
        $averageRating = $verificationService->calculateAverageRating($user);
        $totalDisputes = $verificationService->countDisputes($user);
        $totalProducts = $user->products()->count();
        $profileStatus = $user->profile_status;

        return view('admin.users.show', compact(
            'user',
            'products',
            'orders',
            'reviews',
            'payments',
            'admin',
            'averageRating',
            'totalDisputes',
            'totalProducts',
            'profileStatus'
        ));
    }

    public function userUpdate(Request $request, $id)
    {
        $admin = auth()->user();
        $user = User::findOrFail($id);

        if (! $admin->canManageUser($user)) {
            abort(403, 'You cannot manage this user.');
        }

        if ($admin->id === $user->id && $request->filled('role')) {
            abort(403, 'You cannot change your own role.');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'role' => 'nullable|in:user,admin,super_admin',
            'account_status' => 'nullable|in:active,suspended,banned',
            'status_notes' => 'nullable|string|max:500',
        ]);

        if (! $admin->isSuperAdmin()) {
            unset($validated['role']);

            if (isset($validated['account_status']) && $user->role !== 'user') {
                abort(403, 'Admins can only update status for regular users.');
            }
        }

        $user->update($validated);

        return redirect()->route('admin.users')->with('success', 'User updated successfully');
    }

    public function userDelete($id)
    {
        $admin = auth()->user();
        $user = User::findOrFail($id);

        if (! $admin->canManageUser($user)) {
            abort(403, 'You cannot delete this user.');
        }

        if ($admin->id === $user->id) {
            abort(403, 'You cannot delete your own account from admin panel.');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully');
    }

    public function userStatus(Request $request, User $user)
    {
        $admin = auth()->user();

        if (! $admin->canManageUser($user)) {
            abort(403, 'You cannot update this user status.');
        }

        $data = $request->validate([
            'account_status' => 'required|in:active,suspended,banned',
            'status_notes' => 'nullable|string|max:500',
        ]);

        if (! $admin->isSuperAdmin() && $user->role !== 'user') {
            abort(403, 'Admins can only manage regular users.');
        }

        $user->update($data);

        return redirect()->route('admin.users')->with('success', 'User status updated successfully.');
    }

    public function userResetPassword(Request $request, User $user)
    {
        $admin = auth()->user();

        if (! $admin->canManageUser($user)) {
            abort(403, 'You cannot reset this password.');
        }

        $request->validate([
            'password' => 'nullable|string|min:8|max:64',
        ]);

        $temporaryPassword = $request->input('password') ?: Str::password(12);
        $user->password = Hash::make($temporaryPassword);
        $user->save();

        return redirect()->route('admin.users')
            ->with('success', 'Password reset successfully. Temporary password: ' . $temporaryPassword);
    }

    public function userVerify(User $user)
    {
        $admin = auth()->user();

        if (! $admin->canManageUser($user)) {
            abort(403, 'You cannot verify this user.');
        }

        $verificationService = new UserVerificationService();
        $verificationService->manuallyVerify($user);

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'User profile has been verified manually.');
    }

    public function userRevokeVerification(User $user)
    {
        $admin = auth()->user();

        if (! $admin->canManageUser($user)) {
            abort(403, 'You cannot revoke verification for this user.');
        }

        $verificationService = new UserVerificationService();
        $verificationService->revokeVerification($user);

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'User profile verification has been revoked.');
    }

    public function products(ProductDeletionGuardService $deletionGuard)
    {
        $products = Product::with('user')->latest()->paginate(15);

        $canDeleteByProduct = [];
        $deleteBlockersByProduct = [];
        foreach ($products as $product) {
            $blockerMessage = $deletionGuard->blockerMessage($product);
            $canDeleteByProduct[$product->id] = $blockerMessage === '';
            $deleteBlockersByProduct[$product->id] = $blockerMessage;
        }

        return view('admin.products.index', compact('products', 'canDeleteByProduct', 'deleteBlockersByProduct'));
    }

    public function productShow(Product $product, ProductDeletionGuardService $deletionGuard)
    {
        $product->load(['user', 'rentals', 'orders' => function ($q) {
            $q->where('transaction_type', 'buy');
        }]);

        $reviews = Review::with(['reviewer', 'order', 'rentedRental', 'swap'])
            ->where(function ($q) use ($product) {
                $q->whereHas('order', function ($orderQ) use ($product) {
                    $orderQ->where('product_id', $product->id);
                })
                ->orWhereHas('rentedRental', function ($rentalQ) use ($product) {
                    $rentalQ->where('product_id', $product->id);
                })
                ->orWhereHas('swap', function ($swapQ) use ($product) {
                    $swapQ->where('product_a_id', $product->id)
                        ->orWhere('product_b_id', $product->id);
                });
            })
            ->latest()
            ->get();

        $disputes = Dispute::with(['reporter', 'order.product', 'rentalRequest.product', 'swap.requestedProduct', 'swap.offeredProduct'])
            ->where(function ($q) use ($product) {
                $q->whereHas('order', function ($orderQ) use ($product) {
                    $orderQ->where('product_id', $product->id);
                })
                ->orWhereHas('rentalRequest', function ($rentalRequestQ) use ($product) {
                    $rentalRequestQ->where('product_id', $product->id);
                })
                ->orWhereHas('swap', function ($swapQ) use ($product) {
                    $swapQ->where('product_a_id', $product->id)
                        ->orWhere('product_b_id', $product->id);
                });
            })
            ->latest()
            ->get();

        $rentalRequests = RentalRequest::with(['renter', 'owner'])
            ->where('product_id', $product->id)
            ->latest()
            ->get();

        $swapRequests = SwapRequest::with(['requester', 'owner', 'offeredProduct'])
            ->where('product_id', $product->id)
            ->latest()
            ->get();

        $deleteBlockerMessage = $deletionGuard->blockerMessage($product);
        $canDelete = $deleteBlockerMessage === '';
        $canForceDelete = auth()->user()?->isSuperAdmin() === true;

        return view('admin.products.show', compact('product', 'reviews', 'disputes', 'rentalRequests', 'swapRequests', 'canDelete', 'deleteBlockerMessage', 'canForceDelete'));
    }

    public function productFlag(Product $product)
    {
        if (! $product->flagged) {
            $product->flagged = true;
            $product->save();
        }

        return redirect()->back()->with('success', 'Product flagged');
    }

    public function productUnflag(Product $product)
    {
        if ($product->flagged) {
            $product->flagged = false;
            $product->save();
        }

        return redirect()->back()->with('success', 'Product unflagged');
    }

    public function productApprove(Product $product)
    {
        if ($product->approval_status === 'PENDING') {
            $product->approval_status = 'APPROVED';
            $product->save();

            // Send notification to the product owner
            $product->user->notify(new \App\Notifications\ProductApprovedNotification($product));
        }

        return redirect()->back()->with('success', 'Product approved and seller notified.');
    }

    public function productReject(Request $request, Product $product)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($product->approval_status === 'PENDING') {
            $product->approval_status = 'REJECTED';
            $product->save();

            // Send notification to the product owner with reason
            $product->user->notify(new \App\Notifications\ProductRejectedNotification($product, $request->input('reason')));
        }

        return redirect()->back()->with('success', 'Product rejected and seller notified.');
    }

    public function contentModeration(Request $request)
    {
        $query = Product::with('user')->latest();

        // Filter by flagged status if specified
        if ($request->filled('status')) {
            if ($request->status === 'flagged') {
                $query->where('flagged', true);
            }
            if ($request->status === 'clean') {
                $query->where('flagged', false);
            }
        } else {
            // Default: show only items needing moderation (flagged OR pending approval)
            $query->where('flagged', true)->orWhere('approval_status', 'PENDING');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $products = $query->paginate(20)->withQueryString();
        $categories = Product::query()
            ->with('category')
            ->whereNotNull('category_id')
            ->distinct('category_id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->pluck('categories.name', 'categories.id')
            ->filter()
            ->values();

        return view('admin.content.index', compact('products', 'categories'));
    }

    public function contentDecision(Request $request, Product $product)
    {
        $data = $request->validate([
            'decision' => 'required|in:approve,reject,flag',
        ]);

        $decision = $data['decision'];

        if ($decision === 'approve') {
            $product->approval_status = 'APPROVED';
            $product->flagged = false;
            if ($product->status !== 'available') {
                $product->status = 'available';
            }
            $product->save();

            return redirect()->back()->with('success', 'Listing approved.');
        }

        if ($decision === 'flag') {
            $product->flagged = true;
            $product->save();

            return redirect()->back()->with('success', 'Listing flagged for review.');
        }

        // rejection
        $product->approval_status = 'REJECTED';
        $product->flagged = true;
        $product->save();

        return redirect()->back()->with('success', 'Listing rejected.');
    }

    public function contentBulkUnflag(Request $request)
    {
        $data = $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        Product::whereIn('id', $data['product_ids'])->update(['flagged' => false]);

        return redirect()->back()->with('success', 'Selected listings restored.');
    }

    public function contentBulkDelete(Request $request, ProductDeletionGuardService $deletionGuard)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Super admin only action.');
        }

        $data = $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $products = Product::whereIn('id', $data['product_ids'])->get();

        $deletableIds = [];
        $blockedProducts = [];

        foreach ($products as $product) {
            $blockers = $deletionGuard->getBlockers($product);

            if ($blockers === []) {
                $deletableIds[] = $product->id;
                continue;
            }

            $blockedProducts[] = '#' . $product->id . ' (' . $product->title . '): ' . implode(', ', $blockers);
        }

        $deletedCount = 0;
        if ($deletableIds !== []) {
            $deletedCount = Product::whereIn('id', $deletableIds)->delete();
        }

        if ($blockedProducts === []) {
            return redirect()->back()->with('success', "Selected listings deleted ({$deletedCount}).");
        }

        $blockedPreview = implode(' | ', array_slice($blockedProducts, 0, 3));
        $blockedCount = count($blockedProducts);

        return redirect()->back()
            ->with('success', "Deleted {$deletedCount} listing(s).")
            ->with('error', "Skipped {$blockedCount} listing(s) with active obligations: {$blockedPreview}");
    }

    public function productDelete($id, ProductDeletionGuardService $deletionGuard)
    {
        $product = Product::findOrFail($id);
        $admin = auth()->user();

        $blockerMessage = $deletionGuard->blockerMessage($product);
        if ($blockerMessage !== '') {
            return redirect()->back()->with('error', $blockerMessage);
        }
        
        // Log deletion for audit trail
        \Log::warning('Product deletion by admin', [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'product_owner_id' => $product->user_id,
            'admin_id' => $admin->id,
            'admin_role' => $admin->role,
            'timestamp' => now(),
        ]);
        
        $product->delete();
        return redirect()->route('admin.products')->with('success', 'Product deleted successfully');
    }

    public function productForceDelete(Request $request, Product $product, ProductDeletionGuardService $deletionGuard)
    {
        $admin = auth()->user();

        if (! $admin || ! $admin->isSuperAdmin()) {
            abort(403, 'Super admin only action.');
        }

        $data = $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        $blockers = $deletionGuard->getBlockers($product);

        \Log::critical('Product force-deleted by super admin', [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'product_owner_id' => $product->user_id,
            'super_admin_id' => $admin->id,
            'super_admin_role' => $admin->role,
            'reason' => $data['reason'],
            'blockers_snapshot' => $blockers,
            'timestamp' => now(),
        ]);

        $product->delete();

        return redirect()->route('admin.products')
            ->with('success', 'Product force-deleted by super admin action.');
    }

    public function transactions(Request $request)
    {
        $items = collect();

        $orders = Order::with(['buyer', 'product.user'])->latest()->get()->map(function (Order $order) {
            return [
                'ref' => 'order-' . $order->id,
                'buyer' => $order->buyer?->name ?? 'N/A',
                'seller' => $order->product?->user?->name ?? 'N/A',
                'item' => $order->product?->title ?? 'Order',
                'type' => 'buy',
                'amount' => (float) ($order->total_price ?? 0),
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

        $rentals = RentedRentals::with(['renter', 'owner', 'product'])->latest()->get()->map(function (RentedRentals $rental) {
            return [
                'ref' => 'rental-' . $rental->id,
                'buyer' => $rental->renter?->name ?? 'N/A',
                'seller' => $rental->owner?->name ?? 'N/A',
                'item' => $rental->product?->title ?? 'Rental',
                'type' => 'rent',
                'amount' => (float) ($rental->total_amount ?? 0),
                'status' => $rental->status,
                'created_at' => $rental->created_at,
            ];
        });

        $swaps = Swap::with(['ownerA', 'ownerB', 'requestedProduct'])->latest()->get()->map(function (Swap $swap) {
            return [
                'ref' => 'swap-' . $swap->id,
                'buyer' => $swap->ownerA?->name ?? 'N/A',
                'seller' => $swap->ownerB?->name ?? 'N/A',
                'item' => $swap->requestedProduct?->title ?? 'Swap',
                'type' => 'swap',
                'amount' => (float) ($swap->offered_amount ?? 0),
                'status' => $swap->status,
                'created_at' => $swap->created_at,
            ];
        });

        $items = $items->concat($orders)->concat($rentals)->concat($swaps)
            ->sortByDesc('created_at')
            ->values();

        if ($request->filled('type')) {
            $items = $items->where('type', $request->type)->values();
        }

        if ($request->filled('status')) {
            $items = $items->where('status', $request->status)->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;
        $currentItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

        $transactions = new LengthAwarePaginator(
            $currentItems,
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $financialSummary = null;
        if (auth()->user()->isSuperAdmin()) {
            $financialSummary = [
                'payments_total' => (float) Payment::sum('total_amount'),
                'payments_successful' => (float) Payment::where('status', 'complete')->sum('total_amount'),
                'service_fees_earned' => (float) Payment::where('status', 'complete')->sum('fee_amount'),
                'orders_completed' => Order::where('status', 'completed')->count(),
            ];
        }

        return view('admin.transactions.index', compact('transactions', 'financialSummary'));
    }

    public function reports(Request $request)
    {
        $admin = auth()->user();
        $reportItems = Dispute::with('reporter')
            ->whereIn('status', ['open', 'in_review'])
            ->latest()
            ->take(20)
            ->get();

        $base = [
            'open_disputes' => Dispute::where('status', 'open')->count(),
            'in_review_disputes' => Dispute::where('status', 'in_review')->count(),
            'resolved_disputes' => Dispute::where('status', 'resolved')->count(),
            'flagged_listings' => Product::where('flagged', true)->count(),
            'reported_today' => Dispute::whereDate('created_at', now()->toDateString())->count(),
        ];

        $full = [];
        if ($admin->isSuperAdmin()) {
            $full = [
                'total_revenue' => (float) Payment::sum('total_amount'),
                'successful_revenue' => (float) Payment::where('status', 'complete')->sum('total_amount'),
                'service_fees_earned' => (float) Payment::where('status', 'complete')->sum('fee_amount'),
                'pending_payments' => Payment::where('status', 'pending')->count(),
                'completed_rentals' => RentedRentals::whereIn('status', ['completed', 'returned'])->count(),
                'swap_count' => Swap::count(),
                'total_transactions' => Order::count() + RentedRentals::count() + Swap::count(),
                'total_users' => User::count(),
                'active_users' => User::where('account_status', 'active')->count(),
            ];

            if ($request->query('export') === 'csv') {
                return $this->exportSuperAdminReportCsv($base, $full);
            }
        }

        return view('admin.reports.index', [
            'base' => $base,
            'full' => $full,
            'reportItems' => $reportItems,
            'isSuperAdmin' => $admin->isSuperAdmin(),
        ]);
    }

    public function analytics()
    {
        $this->ensureSuperAdmin();

        $monthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $userGrowthThisMonth = User::where('created_at', '>=', $monthStart)->count();
        $userGrowthLastMonth = User::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

        $listingGrowthThisMonth = Product::where('created_at', '>=', $monthStart)->count();
        $listingGrowthLastMonth = Product::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

        $revenueThisMonth = (float) Payment::where('created_at', '>=', $monthStart)->sum('total_amount');
        $revenueLastMonth = (float) Payment::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('total_amount');
        $totalProducts = Product::count();
        $activeUsers = User::where('account_status', 'active')->count();

        return view('admin.analytics.index', compact(
            'userGrowthThisMonth',
            'userGrowthLastMonth',
            'listingGrowthThisMonth',
            'listingGrowthLastMonth',
            'revenueThisMonth',
            'revenueLastMonth',
            'totalProducts',
            'activeUsers'
        ));
    }

    public function systemConfig()
    {
        $this->ensureSuperAdmin();

        $categories = Product::query()
            ->with('category')
            ->whereNotNull('category_id')
            ->distinct('category_id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->pluck('categories.name', 'categories.id')
            ->filter()
            ->values();
        $settings = [
            'category_rules' => PlatformSetting::getValue('category_rules', ''),
            'sustainability_guidelines' => PlatformSetting::getValue('sustainability_guidelines', ''),
            'notification_policy' => PlatformSetting::getValue('notification_policy', ''),
            'security_policy' => PlatformSetting::getValue('security_policy', ''),
            'payment_fee_percent' => PlatformSetting::getValue('payment_fee_percent', '0'),
            'escrow_policy' => PlatformSetting::getValue('escrow_policy', ''),
        ];

        return view('admin.system.index', compact('categories', 'settings'));
    }

    public function systemConfigUpdate(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'category_rules' => 'nullable|string|max:4000',
            'sustainability_guidelines' => 'nullable|string|max:4000',
            'notification_policy' => 'nullable|string|max:4000',
            'security_policy' => 'nullable|string|max:4000',
            'payment_fee_percent' => 'nullable|numeric|min:0|max:100',
            'escrow_policy' => 'nullable|string|max:4000',
        ]);

        foreach ($data as $key => $value) {
            PlatformSetting::setValue($key, $value ?? '');
        }

        return redirect()->route('admin.system.config')->with('success', 'System configuration updated.');
    }

    public function disputes(Request $request)
    {
        $query = Dispute::with('reporter')->latest();

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: show only active disputes (open or in_review)
            $query->whereIn('status', ['open', 'in_review']);
        }

        $disputes = $query->paginate(20)->withQueryString();

        return view('admin.disputes.index', compact('disputes'));
    }

    public function disputeShow(Dispute $dispute)
    {
        $dispute->load([
            'reporter',
            'resolver',
            'order.product.user',
            'order.buyer',
            'rentedRental.product',
            'rentedRental.deposit',
            'rentedRental.owner',
            'rentedRental.renter',
            'rentalRequest.product.user',
            'rentalRequest.owner',
            'rentalRequest.renter',
            'swap.ownerA',
            'swap.ownerB',
        ]);

        $counterparty = $this->disputeCounterparty($dispute);
        $rentalDepositAmount = (float) ($dispute->rentedRental?->deposit?->amount ?? $dispute->rentedRental?->rent_deposit ?? 0);
        $requiresEscalation = ! auth()->user()->isSuperAdmin() && $this->disputeInvolvesPrivilegedAccount($dispute);

        return view('admin.disputes.show', compact('dispute', 'requiresEscalation', 'counterparty', 'rentalDepositAmount'));
    }

    public function disputeEscalate(Request $request, Dispute $dispute)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $notePrefix = '[ESCALATED TO SUPER ADMIN] ';
        $notes = trim($notePrefix . $data['reason'] . "\n\n" . ($dispute->admin_notes ?? ''));

        $dispute->update([
            'status' => 'in_review',
            'admin_notes' => $notes,
        ]);

        return redirect()->route('admin.disputes.show', $dispute)->with('success', 'Dispute escalated to super admin.');
    }

    public function disputeResolve(
        Request $request,
        Dispute $dispute,
        WalletLedgerService $walletLedgerService
    )
    {
        if (! auth()->user()->isSuperAdmin() && $this->disputeInvolvesPrivilegedAccount($dispute)) {
            return redirect()->back()->with('success', 'This dispute involves an admin account and must be escalated to super admin.');
        }

        $validated = $request->validate([
            'action' => 'nullable|in:start_review,resolve_reporter,resolve_counterparty,dismiss_report',
            'status' => 'nullable|in:in_review,resolved,dismissed',
            'favored_party' => 'nullable|in:reporter,counterparty',
            'owner_award_amount' => 'nullable|numeric|min:0',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $status = null;
        $favoredParty = null;
        $action = $validated['action'] ?? null;

        // Preferred path: derive status from explicit admin action.
        if ($action) {
            [$status, $favoredParty] = match ($action) {
                'start_review' => ['in_review', null],
                'resolve_reporter' => ['resolved', 'reporter'],
                'resolve_counterparty' => ['resolved', 'counterparty'],
                'dismiss_report' => ['dismissed', 'counterparty'],
                default => [null, null],
            };
        }

        // Backward compatibility: allow old form payloads.
        if ($status === null) {
            $status = (string) ($validated['status'] ?? '');
            $isFinalFromStatus = in_array($status, ['resolved', 'dismissed'], true);
            $favoredParty = $isFinalFromStatus ? (string) ($validated['favored_party'] ?? '') : null;
        }

        if (!in_array($status, ['in_review', 'resolved', 'dismissed'], true)) {
            return redirect()->back()->withErrors([
                'action' => 'Please choose a valid dispute action.',
            ]);
        }

        $isFinalStatus = in_array($status, ['resolved', 'dismissed'], true);
        if ($isFinalStatus && !in_array($favoredParty, ['reporter', 'counterparty'], true)) {
            return redirect()->back()->withErrors([
                'favored_party' => 'A final decision requires selecting which party is favored.',
            ]);
        }

        $oldStatus = $dispute->status;
        $oldFavoredParty = $dispute->favored_party;
        $oldAdminNotes = $dispute->admin_notes;
        $oldResolvedBy = $dispute->resolved_by;
        $oldResolvedAt = $dispute->resolved_at;

        DB::transaction(function () use ($dispute, $status, $isFinalStatus, $favoredParty) {
            $dispute->status = $status;
            $dispute->favored_party = $favoredParty;
            $dispute->admin_notes = request('admin_notes');
            $dispute->resolved_by = $isFinalStatus ? auth()->id() : $dispute->resolved_by;
            $dispute->resolved_at = $isFinalStatus ? now() : $dispute->resolved_at;
            $dispute->save();
        });

        if ($isFinalStatus && $dispute->transaction_type === 'rental') {
            $settled = $this->settleRentalDispute(
                $dispute->fresh(['reporter', 'rentedRental.deposit', 'rentedRental.product', 'rentedRental.product.rentals']),
                $request,
                $walletLedgerService
            );

            if (!$settled['ok']) {
                $dispute->update([
                    'status' => $oldStatus,
                    'favored_party' => $oldFavoredParty,
                    'admin_notes' => $oldAdminNotes,
                    'resolved_by' => $oldResolvedBy,
                    'resolved_at' => $oldResolvedAt,
                ]);

                return redirect()->back()->with('error', $settled['message']);
            }
        }

        if ($oldStatus !== $status) {
            $dispute->reporter?->notify(new DisputeStatusUpdated($dispute));

            $counterparty = $this->disputeCounterparty($dispute->fresh([
                'reporter',
                'order.buyer',
                'order.product.user',
                'rentedRental.owner',
                'rentedRental.renter',
                'rentalRequest.owner',
                'rentalRequest.renter',
                'swap.ownerA',
                'swap.ownerB',
            ]));

            if ($counterparty && (int) $counterparty->id !== (int) $dispute->reporter_id) {
                $counterparty->notify(new DisputeStatusUpdated($dispute));
            }
        }

        return redirect()->route('admin.disputes.show', $dispute)->with('success', 'Dispute updated.');
    }

    public function reviews()
    {
        $reviews = Review::with(['reviewer', 'reviewee'])->latest()->paginate(20);
        return view('admin.reviews.index', compact('reviews'));
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Super admin access required.');
        }
    }

    private function disputeInvolvesPrivilegedAccount(Dispute $dispute): bool
    {
        $dispute->loadMissing([
            'reporter',
            'order.product.user',
            'order.buyer',
            'rentedRental.owner',
            'rentedRental.renter',
            'rentalRequest.owner',
            'rentalRequest.renter',
            'swap.ownerA',
            'swap.ownerB',
        ]);

        $users = collect([
            $dispute->reporter,
            $dispute->order?->buyer,
            $dispute->order?->product?->user,
            $dispute->rentedRental?->owner,
            $dispute->rentedRental?->renter,
            $dispute->rentalRequest?->owner,
            $dispute->rentalRequest?->renter,
            $dispute->swap?->ownerA,
            $dispute->swap?->ownerB,
        ])->filter();

        return $users->contains(fn (User $user) => in_array($user->role, ['admin', 'super_admin'], true));
    }

    private function disputeCounterparty(Dispute $dispute): ?User
    {
        $dispute->loadMissing([
            'reporter',
            'order.buyer',
            'order.product.user',
            'rentedRental.owner',
            'rentedRental.renter',
            'rentalRequest.owner',
            'rentalRequest.renter',
            'swap.ownerA',
            'swap.ownerB',
        ]);

        $partyCandidates = collect([
            $dispute->order?->buyer,
            $dispute->order?->product?->user,
            $dispute->rentedRental?->owner,
            $dispute->rentedRental?->renter,
            $dispute->rentalRequest?->owner,
            $dispute->rentalRequest?->renter,
            $dispute->swap?->ownerA,
            $dispute->swap?->ownerB,
        ])->filter();

        return $partyCandidates
            ->first(fn (User $user) => $user->id !== (int) $dispute->reporter_id);
    }

    private function settleRentalDispute(
        Dispute $dispute,
        Request $request,
        WalletLedgerService $walletLedgerService
    ): array {
        $rental = $dispute->rentedRental;
        if (!$rental) {
            return ['ok' => false, 'message' => 'No rental record is attached to this dispute.'];
        }

        $deposit = $this->resolveOrCreateRentalDeposit($rental);

        $depositAmount = (float) ($deposit->amount ?? 0);
        $reporterWon = $dispute->favored_party === 'reporter';
        $winnerId = $reporterWon
            ? (int) $dispute->reporter_id
            : (int) ($this->disputeCounterparty($dispute)?->id ?? 0);
        $ownerWon = $winnerId === (int) $rental->owner_id;

        $requestedClaim = (float) ($dispute->owner_claim_amount ?? 0);
        $awardInput = $request->input('owner_award_amount');
        $awardAmount = $ownerWon
            ? ($awardInput !== null && $awardInput !== '' ? (float) $awardInput : $requestedClaim)
            : 0.0;
        $awardAmount = min(max($awardAmount, 0.0), $depositAmount);
        $refundAmount = max($depositAmount - $awardAmount, 0.0);

        $depositStatus = $awardAmount <= 0
            ? 'refunded'
            : ($refundAmount > 0 ? 'partial' : 'forfeited');

        $deposit->update([
            'deduction_amount' => $awardAmount,
            'refund_amount' => $refundAmount,
            'status' => $depositStatus,
            'notes' => $dispute->admin_notes,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'refund_status' => $refundAmount > 0 ? 'pending' : 'success',
            'refund_requested_at' => $refundAmount > 0 ? now() : null,
            'refund_completed_at' => $refundAmount > 0 ? null : now(),
            'refund_failed_at' => null,
            'failure_reason' => null,
        ]);

        $dispute->owner_award_amount = $awardAmount;
        $dispute->save();

        if ($awardAmount > 0 && $ownerWon) {
            $walletLedgerService->creditSaleIfMissing(
                (int) $rental->owner_id,
                $awardAmount,
                'rental_damage_award',
                'dispute',
                (int) $dispute->id,
                [
                    'rented_rental_id' => $rental->id,
                    'deposit_id' => $deposit->id,
                ]
            );
        }

        if ($refundAmount > 0) {
            // System-ledger refund: credit refundable deposit amount directly to renter wallet.
            $walletLedgerService->creditSaleIfMissing(
                (int) $rental->renter_id,
                $refundAmount,
                'rental_deposit_refund',
                'rental_deposit',
                (int) $deposit->id,
                [
                    'rented_rental_id' => $rental->id,
                    'dispute_id' => $dispute->id,
                ]
            );

            $deposit->update([
                'refund_status' => 'success',
                'refund_reference' => 'wallet-ledger:' . $deposit->id,
                'refund_completed_at' => now(),
                'failure_reason' => null,
            ]);
        }

        $this->completeRentalAfterDispute($rental);

        return [
            'ok' => true,
            'message' => null,
        ];
    }

    private function resolveOrCreateRentalDeposit(RentedRentals $rental): RentalDeposit
    {
        $deposit = RentalDeposit::where('rented_rental_id', $rental->id)->first();
        $payment = $this->resolveRentalPaymentForDeposit($rental);

        if (!$deposit) {
            return RentalDeposit::create([
                'rented_rental_id' => $rental->id,
                'payment_id' => $payment?->id,
                'amount' => (float) ($rental->rent_deposit ?? 0),
                'deduction_amount' => 0,
                'refund_amount' => 0,
                'status' => 'held',
                'refund_status' => 'pending',
                'gateway' => $payment?->provider,
                'gateway_reference' => $payment?->transaction_code,
            ]);
        }

        $updates = [];

        if (!(int) ($deposit->payment_id ?? 0) && $payment) {
            $updates['payment_id'] = (int) $payment->id;
        }

        if (empty($deposit->gateway) && $payment?->provider) {
            $updates['gateway'] = (string) $payment->provider;
        }

        if (empty($deposit->gateway_reference) && $payment?->transaction_code) {
            $updates['gateway_reference'] = (string) $payment->transaction_code;
        }

        if ((float) ($deposit->amount ?? 0) <= 0) {
            $updates['amount'] = (float) ($rental->rent_deposit ?? 0);
        }

        if (!empty($updates)) {
            $deposit->update($updates);
        }

        return $deposit->fresh(['payment']);
    }

    private function resolveRentalPaymentForDeposit(RentedRentals $rental): ?Payment
    {
        $reference = trim((string) ($rental->payment_reference ?? ''));
        if ($reference !== '') {
            $match = Payment::where('status', 'complete')
                ->where(function ($query) use ($reference) {
                    $query->where('transaction_code', $reference)
                        ->orWhere('payment_reference', $reference);
                })
                ->latest('id')
                ->first();

            if ($match) {
                return $match;
            }
        }

        return Payment::where('status', 'complete')
            ->where('user_id', (int) $rental->renter_id)
            ->where('provider', '!=', '')
            ->where(function ($query) use ($rental) {
                $query->where('total_amount', (float) ($rental->total_amount ?? 0))
                    ->orWhere('request_payload->source', 'rental');
            })
            ->latest('id')
            ->first();
    }

    private function completeRentalAfterDispute(RentedRentals $rental): void
    {
        if ($rental->status === 'completed') {
            return;
        }

        DB::transaction(function () use ($rental) {
            $lockedRental = RentedRentals::lockForUpdate()->findOrFail($rental->id);
            $wasActive = $lockedRental->status === 'active';

            $lockedRental->status = 'completed';
            $lockedRental->returned_at = $lockedRental->returned_at ?: now();
            $lockedRental->save();

            if (!$wasActive) {
                return;
            }

            $product = Product::lockForUpdate()->find($lockedRental->product_id);
            if (!$product) {
                return;
            }

            $product->quantity += 1;
            if ($product->status === 'rented' && $product->quantity > 0) {
                $product->status = 'available';
            }
            $product->save();

            $rentalConfig = $product->rentals()->first();
            if ($rentalConfig) {
                $rentalConfig->status = 'available';
                $rentalConfig->available_from = now()->toDateString();
                $rentalConfig->save();
            }
        });
    }

    private function exportSuperAdminReportCsv(array $base, array $full)
    {
        $rows = [
            ['metric', 'value'],
        ];

        foreach (array_merge($base, $full) as $key => $value) {
            $rows[] = [$key, (string) $value];
        }

        $csv = collect($rows)->map(function ($row) {
            return collect($row)->map(function ($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            })->implode(',');
        })->implode("\n");

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="super-admin-report.csv"',
        ]);
    }
}
