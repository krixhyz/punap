<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
    $table->id();

    // Relationships
    $table->foreignId('product_id')->constrained()->onDelete('cascade'); // The item being rented
    $table->foreignId('owner_id')->constrained('users')->onDelete('cascade'); // Product owner
    $table->foreignId('renter_id')->constrained('users')->nullOnDelete()->onDelete('cascade'); // Person who rents

    // Rental details
    $table->decimal('rent_fare', 10, 2);
    $table->decimal('rent_deposit', 10, 2)->nullable();
    $table->integer('duration')->nullable(); // hours or days
    $table->dateTime('start_date')->nullable();
    $table->dateTime('end_date')->nullable();

    // Payment-related (for your future API integration)
    $table->decimal('total_amount', 10, 2)->nullable();
    $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
    $table->string('payment_reference')->nullable(); // for storing API transaction ID

    // Status of the rental
    $table->enum('rental_status', [
        'requested', // renter requested
        'approved',  // owner approved
        'active',    // currently rented
        'completed', // returned
        'cancelled'
    ])->default('requested');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
