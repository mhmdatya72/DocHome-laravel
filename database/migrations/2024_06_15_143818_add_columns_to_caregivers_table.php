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
        Schema::table('caregivers', function (Blueprint $table) {
            $table->text('about')->nullable();
            $table->float('stars', 2, 1)->default(0);
            $table->integer('number_of_reviews')->default(0);
            $table->string('title')->nullable();
            $table->tinyInteger('open')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caregivers', function (Blueprint $table) {
            $table->dropColumn(['about', 'stars', 'number_of_reviews', 'title', 'open']);
        });
    }
};
