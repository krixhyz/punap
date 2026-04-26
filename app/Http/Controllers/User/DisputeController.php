<?php

namespace App\Http\Controllers\User;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\RentalRequest;
use App\Models\RentedRentals;
use App\Models\Swap;
use App\Models\User\User;
use App\Notifications\User\DisputeFiledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class DisputeController extends Controller
{
    /**
     * Show dispute form for a transaction.
     * GET /dispute/create?type=order&id=1
     */
    public function create(Request $request)
    {
        $type = $request->query('type');
        $id   = $request->query('id');

        $transaction = $this->resolveTransaction($type, $id);
        if (! $transaction) abort(404);

        if (! $this->isAuthorizedReporter($type, $transaction)) {
            abort(403);
        }

        $canSubmitOwnerClaim = $this->canSubmitOwnerClaim($type, $transaction);
        $maxOwnerClaim = $this->maxOwnerClaimAmount($type, $transaction);

        $existing = Dispute::where('reporter_id', Auth::id())
            ->where($this->txColumn($type), $id)
            ->first();

        $counterpartyDispute = Dispute::where($this->txColumn($type), $id)
            ->where('reporter_id', '!=', (int) Auth::id())
            ->latest()
            ->first();

        return view('disputes.create', compact('type', 'id', 'transaction', 'existing', 'counterpartyDispute', 'canSubmitOwnerClaim', 'maxOwnerClaim'));
    }

    /**
     * Store a new dispute.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => 'required|in:order,rental,swap',
            'ref_id'      => 'required|integer',
            'subject'     => 'required|string|max:200',
            'description' => 'required|string|max:3000',
            'evidence_photos' => 'nullable|array',
            'evidence_photos.*' => 'file|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ], [
            'type.required' => 'Dispute type is required.',
            'type.in' => 'Selected dispute type is invalid.',
            'ref_id.required' => 'Transaction reference is required.',
            'ref_id.integer' => 'Transaction reference is invalid.',
            'subject.required' => 'Please provide a dispute subject.',
            'subject.max' => 'Subject cannot exceed 200 characters.',
            'description.required' => 'Please describe the dispute details.',
            'description.max' => 'Description cannot exceed 3000 characters.',
            'evidence_photos.array' => 'Evidence photos must be uploaded as a list of files.',
            'evidence_photos.*.image' => 'Each evidence file must be an image.',
            'evidence_photos.*.mimes' => 'Evidence images must be JPG, JPEG, PNG, GIF, or WebP.',
            'evidence_photos.*.max' => 'Each evidence image must be 5 MB or smaller.',
        ]);

        $type = $validated['type'];
        $id   = (int) $validated['ref_id'];

        $transaction = $this->resolveTransaction($type, $id);
        if (! $transaction) abort(404);

        if (! $this->isAuthorizedReporter($type, $transaction)) {
            abort(403);
        }

        $canSubmitOwnerClaim = $this->canSubmitOwnerClaim($type, $transaction);
        $maxOwnerClaim = $this->maxOwnerClaimAmount($type, $transaction);

        $claimValidation = $request->validate([
            'owner_claim_amount' => $canSubmitOwnerClaim
                ? 'nullable|bail|regex:/^\d+(\.\d{1,2})?$/|gte:0|lte:' . $maxOwnerClaim
                : 'nullable|prohibited',
        ], [
            'owner_claim_amount.regex' => 'Claim amount must be a valid number with up to 2 decimal places.',
            'owner_claim_amount.gte' => 'Claim amount cannot be less than 0.',
            'owner_claim_amount.lte' => 'Claim amount cannot exceed available deposit (Rs. ' . number_format($maxOwnerClaim, 2) . ').',
            'owner_claim_amount.prohibited' => 'Only the rental owner can submit a claim amount.',
        ]);

        $ownerClaimAmount = $canSubmitOwnerClaim && array_key_exists('owner_claim_amount', $claimValidation)
            ? ($claimValidation['owner_claim_amount'] !== null ? (float) $claimValidation['owner_claim_amount'] : null)
            : null;

        if ($ownerClaimAmount !== null && ($ownerClaimAmount < 0 || $ownerClaimAmount > $maxOwnerClaim)) {
            return back()->withErrors([
                'owner_claim_amount' => 'Claim amount must be between 0 and Rs. ' . number_format($maxOwnerClaim, 2) . '.',
            ])->withInput();
        }

        $existing = Dispute::where('reporter_id', Auth::id())
            ->where($this->txColumn($type), $id)
            ->first();

        $existingPhotos = $existing?->evidence_photos ?? [];
        $newPhotos = $this->storeEvidencePhotos($request);
        $mergedPhotos = array_values(array_filter(array_merge($existingPhotos, $newPhotos)));

        $dispute = Dispute::updateOrCreate(
            array_filter([
                'reporter_id'       => Auth::id(),
                $this->txColumn($type) => $id,
            ]),
            [
                'seller_id' => $this->resolveSellerId($type, $transaction),
                'transaction_type' => $type,
                'subject'          => $validated['subject'],
                'description'      => $validated['description'],
                'evidence_photos'   => $mergedPhotos,
                'owner_claim_amount' => $ownerClaimAmount,
                'owner_award_amount' => null,
                'status'           => 'open',
                'admin_notes'      => null,
            ]
        );

        $counterparty = $this->resolveCounterparty($type, $transaction);
        if ($counterparty && (int) $counterparty->id !== (int) Auth::id()) {
            $counterparty->notify(new DisputeFiledNotification(
                $dispute,
                $type,
                $id,
                (string) (Auth::user()?->name ?? 'A user'),
                $existing === null
            ));
        }

        return redirect()->route('products.myPurchases')->with('success', 'Dispute submitted. An admin will review it shortly.');
    }

    /**
     * User's own disputes list.
     */
    public function myDisputes()
    {
        $disputes = Dispute::where('reporter_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('disputes.my', compact('disputes'));
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function txColumn(string $type): string
    {
        return match($type) {
            'order'  => 'order_id',
            'rental' => 'rented_rental_id',
            'swap'   => 'swap_id',
        };
    }

    private function resolveTransaction(string $type, int $id): mixed
    {
        return match($type) {
            'order'  => Order::find($id),
            'rental' => RentedRentals::find($id) ?? RentalRequest::find($id),
            'swap'   => Swap::find($id),
            default  => null,
        };
    }

    private function isAuthorizedReporter(string $type, mixed $transaction): bool
    {
        $userId = (int) Auth::id();

        return match($type) {
            'order' => (int) ($transaction->buyer_id ?? 0) === $userId || (int) ($transaction->seller_id ?? 0) === $userId,
            'rental' => (int) ($transaction->renter_id ?? 0) === $userId || (int) ($transaction->owner_id ?? 0) === $userId,
            'swap' => (int) ($transaction->owner_a_id ?? 0) === $userId || (int) ($transaction->owner_b_id ?? 0) === $userId,
            default => false,
        };
    }

    private function canSubmitOwnerClaim(string $type, mixed $transaction): bool
    {
        if ($type !== 'rental' || !($transaction instanceof RentedRentals)) {
            return false;
        }

        return (int) $transaction->owner_id === (int) Auth::id();
    }

    private function maxOwnerClaimAmount(string $type, mixed $transaction): float
    {
        if ($type !== 'rental' || !($transaction instanceof RentedRentals)) {
            return 0.0;
        }

        $transaction->loadMissing('deposit');
        $deposit = (float) ($transaction->deposit?->amount ?? $transaction->rent_deposit ?? 0);

        return max($deposit, 0.0);
    }

    private function resolveSellerId(string $type, mixed $transaction): ?int
    {
        return match ($type) {
            'order' => (int) ($transaction->seller_id ?? 0) ?: null,
            'rental' => (int) ($transaction->owner_id ?? 0) ?: null,
            'swap' => (int) ($transaction->owner_b_id ?? 0) ?: null,
            default => null,
        };
    }

    private function resolveCounterparty(string $type, mixed $transaction): ?User
    {
        $me = (int) Auth::id();

        return match ($type) {
            'order' => (int) ($transaction->buyer_id ?? 0) === $me
                ? ($transaction->seller ?? $transaction->product?->user ?? User::find((int) ($transaction->seller_id ?? 0)))
                : ($transaction->buyer ?? User::find((int) ($transaction->buyer_id ?? 0))),
            'rental' => (int) ($transaction->owner_id ?? 0) === $me
                ? ($transaction->renter ?? User::find((int) ($transaction->renter_id ?? 0)))
                : ($transaction->owner ?? User::find((int) ($transaction->owner_id ?? 0))),
            'swap' => (int) ($transaction->owner_a_id ?? 0) === $me
                ? ($transaction->ownerB ?? User::find((int) ($transaction->owner_b_id ?? 0)))
                : ($transaction->ownerA ?? User::find((int) ($transaction->owner_a_id ?? 0))),
            default => null,
        };
    }

    private function storeEvidencePhotos(Request $request): array
    {
        if (!$request->hasFile('evidence_photos')) {
            return [];
        }

        $stored = [];
        $disk = config('filesystems.default') === 'cloudinary' ? 'cloudinary' : 'public';

        foreach ($request->file('evidence_photos') as $file) {
            if (!$file) {
                continue;
            }

            $storedPath = Storage::disk($disk)->putFile('disputes/evidence', $file);
            if ($storedPath) {
                $stored[] = $storedPath;
            }
        }

        return $stored;
    }
}
