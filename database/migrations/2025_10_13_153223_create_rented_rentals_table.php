
<?php
// database/migrations/2025_10_13_000001_create_rented_rentals_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rented_rentals', function (Blueprint $table) {
            $table->id();

            // reference to rental listing and original product
            $table->foreignId('rental_id')->constrained('rentals')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // user relations
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade'); // owner (lister)
            $table->foreignId('renter_id')->constrained('users')->onDelete('cascade'); // person who rented

            // rental details at the moment of approval (snapshot)
            $table->decimal('rent_fare', 10, 2)->nullable();
            $table->decimal('rent_deposit', 10, 2)->nullable();
            $table->enum('rent_type', ['hourly','daily'])->default('daily');
            $table->integer('duration')->nullable()->comment('Duration booked in units of rent_type');

            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();

            // payment fields (for future integration)
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->enum('payment_status', ['pending','paid','refunded'])->default('pending');
            $table->string('payment_reference')->nullable();

            // rental lifecycle status
            $table->enum('status', ['active','completed','cancelled'])->default('active');

            $table->timestamps();

            // add indexes for quick lookups
            $table->index(['renter_id']);
            $table->index(['owner_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rented_rentals');
    }
};
