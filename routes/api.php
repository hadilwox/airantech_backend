<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('/register/student', [AuthController::class, 'registerStudent']);
Route::post('/register/instructor', [AuthController::class, 'registerInstructor']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('course-categories', CourseCategoryController::class);
    Route::apiResource('students', StudentController::class);
    Route::apiResource('instructors', InstructorController::class);
    Route::apiResource('courses', CourseController::class);
    Route::apiResource('enrollments', EnrollmentController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::get('courses/{course}/students', [EnrollmentController::class, 'byCourse']);
    Route::get('students/{student}/courses', [EnrollmentController::class, 'byStudent']);
});
