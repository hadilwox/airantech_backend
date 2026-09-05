<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('course_categories')->restrictOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('instructors')->nullOnDelete();
            $table->string('code')->unique();
            $table->unsignedSmallInteger('jalali_year');
            $table->unsignedTinyInteger('jalali_month');
            $table->string('title');
            $table->unsignedSmallInteger('teaching_hours')->nullable();
            $table->unsignedSmallInteger('capacity');
            $table->decimal('tuition_fee', 12, 2);
            $table->decimal('instructor_cost', 12, 2)->nullable();
            $table->boolean('has_university_certificate')->default(false);
            $table->unsignedInteger('exam_introduced_count')->default(0);
            $table->unsignedInteger('exam_passed_count')->default(0);
            $table->unsignedInteger('exam_retake_count')->default(0);
            $table->json('schedule_days')->nullable();
            $table->string('status')->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'jalali_year', 'jalali_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
