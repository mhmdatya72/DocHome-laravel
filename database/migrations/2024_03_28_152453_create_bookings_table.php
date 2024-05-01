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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->json('services')->nullable();
            $table->unsignedBigInteger('caregiver_id');
            $table->foreign('caregiver_id')->references('id')->on('caregivers')->onDelete('cascade');
            $table->point('location')->nullable();
            $table->decimal('total_price', 8, 2)->default(0.00); // Default value for total_price
            $table->timestamp('booking_date')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('approval_status')->nullable()->default(null);
            $table->string('phone_number', 11)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
