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
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->text('description');
        $table->decimal('price', 10, 2)->nullable();
        $table->json('type')->nullable(); // store as ["sell", "rent", "swap"]
        $table->string('category')->nullable();
        $table->string('image')->nullable();

        // for better control and future flexibility
        $table->enum('status', ['pending', 'approved', 'rejected', 'sold', 'rented'])->default('pending');
        $table->integer('rent_duration')->nullable(); // For rentals
        $table->string('location')->nullable(); // Optional

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
