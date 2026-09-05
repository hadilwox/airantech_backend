<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\InstructorRegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StudentRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Instructor;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new student account and log them in.
     */
    public function registerStudent(StudentRegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'name' => trim("{$request->string('first_name')} {$request->string('last_name')}"),
                'email' => $request->string('email'),
                'phone' => $request->string('mobile_phone'),
                'password' => $request->string('password'),
            ]);

            $user->assignRole(RoleName::Student->value);

            Student::create([
                ...$request->only([
                    'national_id', 'first_name', 'last_name', 'father_name',
                    'date_of_birth', 'mobile_phone', 'landline_phone', 'emergency_phone',
                    'education_level', 'address', 'postal_code',
                ]),
                'user_id' => $user->id,
                'email' => $request->string('email'),
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user->load('student')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Register a new instructor application (pending approval) and log them in.
     */
    public function registerInstructor(InstructorRegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'name' => trim("{$request->string('first_name')} {$request->string('last_name')}"),
                'email' => $request->string('email'),
                'phone' => $request->string('mobile_phone'),
                'password' => $request->string('password'),
            ]);

            $user->assignRole(RoleName::Instructor->value);

            Instructor::create([
                ...$request->only([
                    'national_id', 'first_name', 'last_name', 'father_name', 'residence',
                    'marital_status', 'date_of_birth', 'mobile_phone', 'landline_phone',
                    'emergency_phone', 'education_degree', 'expected_salary', 'skills', 'work_experience',
                ]),
                'user_id' => $user->id,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user->load('instructor')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Authenticate a user via email/password and start a stateful session.
     */
    public function login(LoginRequest $request): UserResource
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('این حساب کاربری غیرفعال شده است.')],
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return new UserResource($user->load(['student', 'instructor']));
    }

    /**
     * Log the current user out and invalidate their session.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'با موفقیت خارج شدید.']);
    }

    /**
     * Return the currently authenticated user with roles/permissions.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load(['student', 'instructor']));
    }
}
