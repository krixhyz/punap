<?php

// database/migrations/2025_10_13_000000_create_rentals_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();

            // product this listing is for (keeps main product details in products table)
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // owner who listed this rental
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            // Owner-set rental configuration
            $table->decimal('rent_fare', 10, 2)->nullable();
            $table->decimal('rent_deposit', 10, 2)->nullable();
            $table->dateTime('available_from')->nullable();
            
            $table->integer('available_duration')->nullable()->comment('Max duration owner allows (in units of rent_type)');

            // listing status: available, disabled, removed, rented (keeps quick filtering)
            $table->enum('status', ['available','rented','disabled'])->default('available');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
