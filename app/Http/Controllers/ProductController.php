<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Rental;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->get();
        return view('index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|in:electronics,clothing,furniture,general',
            'price' => 'nullable|numeric|min:0',
            'listing_type' => 'required|array|min:1',
            'listing_type.*' => 'in:sell,rent,swap',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
            'rent_deposit' => 'required_if:listing_type.*,rent|nullable|numeric|min:0',
            'rent_fare' => 'required_if:listing_type.*,rent|nullable|numeric|min:0',
            'rent_type' => 'required_if:listing_type.*,rent|nullable|in:hourly,daily',
            'start_date' => 'required_if:listing_type.*,rent|nullable|date|after_or_equal:today',
            'end_date' => 'required_if:listing_type.*,rent|nullable|date|after_or_equal:start_date',
            'rent_duration' => 'required_if:listing_type.*,rent|nullable|integer|min:1',
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $filename = time() . '_' . uniqid() . '.' . $request->file('image')->getClientOriginalExtension();
                $imagePath = $request->file('image')->storeAs('uploads/products', $filename, 'public');
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['image' => 'Failed to upload image: ' . $e->getMessage()]);
            }
        }

        // Create product entry
        $product = Product::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'price' => $request->price,
            'type' => $request->listing_type,
            'image' => $imagePath,
            'status' => 'pending',
        ]);

        // If rent selected, create a rental record
        if (in_array('rent', $request->listing_type)) {
            Rental::create([
                'product_id' => $product->id,
                'owner_id' => Auth::id(),
                'rent_deposit' => $request->rent_deposit,
                'rent_fare' => $request->rent_fare,
                'duration' => $request->rent_duration,
                'start_date' => $request->start_date,
                'status' => 'available',
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Listing added successfully!');
    }

    public function myListings()
    {
        $products = Product::where('user_id', Auth::id())->latest()->get();
        return view('products.my_listings', compact('products'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:available,sold,rented,swapped',
        ]);

        $product = Product::where('user_id', Auth::id())->findOrFail($id);
        $product->status = $request->status;
        $product->save();

        return redirect()->back()->with('success', 'Product status updated successfully!');
    }

    public function destroy($id)
    {
        $product = Product::where('user_id', Auth::id())->findOrFail($id);

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('products.myListings')->with('success', 'Product deleted successfully!');
    }
}