<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_code_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('course_categories')->cascadeOnDelete();
            $table->unsignedSmallInteger('jalali_year');
            $table->unsignedTinyInteger('jalali_month');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'jalali_year', 'jalali_month'], 'course_code_sequences_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_code_sequences');
    }
};
