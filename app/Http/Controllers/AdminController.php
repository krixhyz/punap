<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\RentedRentals;
use App\Models\Review;
use App\Models\Swap;
use App\Models\User;
use App\Notifications\DisputeStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

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
        $products = Product::with('user')->latest()->take(20)->get();
        $recentDisputes = Dispute::with('reporter')->latest()->take(5)->get();
        $pendingVerifications = User::whereNull('email_verified_at')->latest()->take(5)->get();

        $totalUsers = User::count();
        $totalProducts = Product::count();
        $totalAdmins = User::whereIn('role', ['admin', 'super_admin'])->count();
        $totalSuperAdmins = User::where('role', 'super_admin')->count();
        $flaggedProducts = Product::where('flagged', true)->count();
        $openDisputes = Dispute::where('status', 'open')->count();
        $totalReviews = Review::count();
        $pendingUsers = User::whereNull('email_verified_at')->count();
        $reportedItems = Dispute::count() + $flaggedProducts;
        $activeUsers = User::where('account_status', 'active')->count();
        $completedTransactions = Order::where('status', 'completed')->count() + RentedRentals::where('status', 'completed')->count() + Swap::where('status', 'completed')->count();
        $monthlyRevenue = (float) Payment::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_amount');

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
            'pendingUsers' => $pendingUsers,
            'reportedItems' => $reportedItems,
            'activeUsers' => $activeUsers,
            'completedTransactions' => $completedTransactions,
            'monthlyRevenue' => $monthlyRevenue,
            'isSuperAdmin' => $admin->isSuperAdmin(),
        ]);
    }

    public function userStore(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|max:64',
            'role' => 'required|in:user,admin,super_admin',
            'account_status' => 'nullable|in:active,suspended,banned',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'account_status' => $data['account_status'] ?? 'active',
        ]);

        return redirect()->route('admin.users')->with('success', 'User created successfully.');
    }

    public function users(Request $request)
    {
        $admin = auth()->user();
        $query = User::query();

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

        return view('admin.users.index', compact('users', 'admin'));
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

        return view('admin.users.show', compact('user', 'products', 'orders', 'reviews', 'payments', 'admin'));
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

    public function products()
    {
        $products = Product::with('user')->latest()->paginate(15);
        return view('admin.products.index', compact('products'));
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

    public function contentModeration(Request $request)
    {
        $query = Product::with('user')->latest();

        if ($request->filled('status')) {
            if ($request->status === 'flagged') {
                $query->where('flagged', true);
            }

            if ($request->status === 'clean') {
                $query->where('flagged', false);
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $products = $query->paginate(20)->withQueryString();
        $categories = Product::query()->whereNotNull('category')->distinct()->pluck('category')->filter()->values();

        return view('admin.content.index', compact('products', 'categories'));
    }

    public function contentDecision(Request $request, Product $product)
    {
        $data = $request->validate([
            'decision' => 'required|in:approve,reject,flag',
        ]);

        $decision = $data['decision'];

        if ($decision === 'approve') {
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

        $product->flagged = true;
        $product->save();

        return redirect()->back()->with('success', 'Listing rejected and kept flagged.');
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

    public function contentBulkDelete(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Super admin only action.');
        }

        $data = $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        Product::whereIn('id', $data['product_ids'])->delete();

        return redirect()->back()->with('success', 'Selected listings deleted.');
    }

    public function productDelete($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return redirect()->route('admin.products')->with('success', 'Product deleted successfully');
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
                'payments_successful' => (float) Payment::where('status', 'completed')->sum('total_amount'),
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
                'successful_revenue' => (float) Payment::where('status', 'completed')->sum('total_amount'),
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

        $categories = Product::query()->whereNotNull('category')->distinct()->pluck('category')->filter()->values();
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
            'rentalRequest.product.user',
            'rentalRequest.renter',
            'swap.ownerA',
            'swap.ownerB',
        ]);

        $requiresEscalation = ! auth()->user()->isSuperAdmin() && $this->disputeInvolvesPrivilegedAccount($dispute);

        return view('admin.disputes.show', compact('dispute', 'requiresEscalation'));
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

    public function disputeResolve(Request $request, Dispute $dispute)
    {
        if (! auth()->user()->isSuperAdmin() && $this->disputeInvolvesPrivilegedAccount($dispute)) {
            return redirect()->back()->with('success', 'This dispute involves an admin account and must be escalated to super admin.');
        }

        $request->validate([
            'status' => 'required|in:in_review,resolved,dismissed',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $dispute->status;

        $dispute->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'resolved_by' => in_array($request->status, ['resolved', 'dismissed']) ? auth()->id() : $dispute->resolved_by,
            'resolved_at' => in_array($request->status, ['resolved', 'dismissed']) ? now() : $dispute->resolved_at,
        ]);

        if ($oldStatus !== $request->status) {
            $dispute->reporter->notify(new DisputeStatusUpdated($dispute));
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
            'rentalRequest.owner',
            'rentalRequest.renter',
            'swap.ownerA',
            'swap.ownerB',
        ]);

        $users = collect([
            $dispute->reporter,
            $dispute->order?->buyer,
            $dispute->order?->product?->user,
            $dispute->rentalRequest?->owner,
            $dispute->rentalRequest?->renter,
            $dispute->swap?->ownerA,
            $dispute->swap?->ownerB,
        ])->filter();

        return $users->contains(fn (User $user) => in_array($user->role, ['admin', 'super_admin'], true));
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
