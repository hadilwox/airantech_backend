<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Enrollment::class);

        return EnrollmentResource::collection($this->baseQuery($request)->paginate(
            min((int) $request->integer('per_page', 15), 100)
        ));
    }

    public function byCourse(Request $request, Course $course): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Enrollment::class);

        $request->merge(['course_id' => $course->id]);

        return EnrollmentResource::collection($this->baseQuery($request)->paginate(
            min((int) $request->integer('per_page', 15), 100)
        ));
    }

    public function byStudent(Request $request, Student $student): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Enrollment::class);

        $request->merge(['student_id' => $student->id]);

        return EnrollmentResource::collection($this->baseQuery($request)->paginate(
            min((int) $request->integer('per_page', 15), 100)
        ));
    }

    private function baseQuery(Request $request): Builder
    {
        return Enrollment::query()
            ->with(['student', 'course.category', 'course.instructor'])
            ->when($request->filled('course_id'), fn ($query) => $query->where('course_id', $request->integer('course_id')))
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->integer('student_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->string('payment_status')))
            ->orderByDesc('id');
    }

    public function store(StoreEnrollmentRequest $request): EnrollmentResource
    {
        $enrollment = DB::transaction(function () use ($request): Enrollment {
            // Lock the course row so two concurrent enrollment requests
            // can't both pass the capacity check for the last open seat.
            $course = Course::query()->lockForUpdate()->findOrFail($request->integer('course_id'));

            $activeCount = $course->enrollments()
                ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
                ->count();

            if ($activeCount >= $course->capacity) {
                throw ValidationException::withMessages([
                    'course_id' => ['ظرفیت این دوره تکمیل شده است.'],
                ]);
            }

            if (Enrollment::query()->where('student_id', $request->integer('student_id'))->where('course_id', $course->id)->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => ['این دانشجو قبلاً در این دوره ثبت‌نام کرده است.'],
                ]);
            }

            // student_id/course_id/tuition_amount/paid_amount/status are
            // deliberately excluded from Enrollment's fillable list (server
            // derived, never client input) — forceFill is the one place
            // they're allowed to be set.
            $enrollment = new Enrollment;
            $enrollment->forceFill([
                'student_id' => $request->integer('student_id'),
                'course_id' => $course->id,
                'enrolled_at' => $request->date('enrolled_at') ?? now()->toDateString(),
                'notes' => $request->string('notes')->value() ?: null,
                'tuition_amount' => $course->tuition_fee,
                'paid_amount' => 0,
                'status' => EnrollmentStatus::Active,
            ]);
            $enrollment->save();

            return $enrollment;
        });

        return new EnrollmentResource($enrollment->load(['student', 'course.category', 'course.instructor']));
    }

    public function show(Enrollment $enrollment): EnrollmentResource
    {
        $this->authorize('view', $enrollment);

        return new EnrollmentResource($enrollment->load(['student', 'course.category', 'course.instructor']));
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): EnrollmentResource
    {
        // status/paid_amount aren't in Enrollment's fillable list either —
        // same reasoning as store(), forceFill the validated data through.
        $enrollment->forceFill($request->validated());
        $enrollment->save();

        return new EnrollmentResource($enrollment->load(['student', 'course.category', 'course.instructor']));
    }

    public function destroy(Enrollment $enrollment): Response
    {
        $this->authorize('delete', $enrollment);

        $enrollment->delete();

        return response()->noContent();
    }
}
