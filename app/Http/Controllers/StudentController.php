<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Student::class);

        $students = Student::query()
            ->with('user')
            ->withCount('enrollments')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($query) use ($term) {
                    $query->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('national_id', 'like', $term)
                        ->orWhere('student_code', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 15), 100));

        return StudentResource::collection($students);
    }

    public function store(StoreStudentRequest $request): StudentResource
    {
        $student = DB::transaction(function () use ($request): Student {
            $user = User::create([
                'name' => trim("{$request->string('first_name')} {$request->string('last_name')}"),
                'email' => $request->string('email'),
                'phone' => $request->string('mobile_phone'),
                'password' => $request->filled('password') ? $request->string('password') : Str::password(20),
            ]);

            $user->assignRole(RoleName::Student->value);

            $student = Student::create([
                ...$request->safe()->except(['email', 'password', 'password_confirmation']),
                'user_id' => $user->id,
                'email' => $request->string('email'),
            ]);

            $student->update(['student_code' => 'STU-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT)]);

            return $student;
        });

        return new StudentResource($student->load('user'));
    }

    public function show(Student $student): StudentResource
    {
        $this->authorize('view', $student);

        return new StudentResource($student->load('user')->loadCount('enrollments'));
    }

    public function update(UpdateStudentRequest $request, Student $student): StudentResource
    {
        $student->update($request->safe()->except(['email', 'is_active']));

        if ($request->filled('email')) {
            $student->update(['email' => $request->string('email')]);
            $student->user->update(['email' => $request->string('email')]);
        }

        if ($request->has('is_active')) {
            $student->user->update(['is_active' => $request->boolean('is_active')]);
        }

        return new StudentResource($student->load('user'));
    }

    public function destroy(Student $student): Response
    {
        $this->authorize('delete', $student);

        $student->delete();

        return response()->noContent();
    }
}
