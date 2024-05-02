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
        Schema::create('caregiver_details', function (Blueprint $table) {
            $table->id();
            $table->text('description');
            $table->text('personal_photo');
            $table->string('email')->unique();
            $table->string('id_card')->unique();
            $table->text('pro_card')->unique();
            $table->foreignId('category_id')->constrained('categories') ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('caregiver_id')->constrained('caregivers')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caregiver_details');
    }
};
