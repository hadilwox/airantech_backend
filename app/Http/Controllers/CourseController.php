<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\CourseCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Course::class);

        $courses = Course::query()
            ->with(['category', 'instructor'])
            ->withCount('enrollments')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($query) use ($term) {
                    $query->where('title', 'like', $term)->orWhere('code', 'like', $term);
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('instructor_id'), fn ($query) => $query->where('instructor_id', $request->integer('instructor_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 15), 100));

        return CourseResource::collection($courses);
    }

    public function store(StoreCourseRequest $request, CourseCodeGenerator $codeGenerator): CourseResource
    {
        $course = DB::transaction(function () use ($request, $codeGenerator): Course {
            $category = CourseCategory::findOrFail($request->integer('category_id'));

            $generated = $codeGenerator->generate($category);

            // code/jalali_year/jalali_month are deliberately excluded from
            // Course's fillable list (server-set only, never client input).
            // forceFill the whole record in one insert: the request side is
            // already validated/whitelisted via $request->safe(), and the
            // generated side never comes from the client at all.
            $course = new Course;
            $course->forceFill([
                ...$request->safe()->except(['category_id']),
                'category_id' => $category->id,
                ...$generated,
            ]);
            $course->save();

            return $course;
        });

        return new CourseResource($course->load(['category', 'instructor']));
    }

    public function show(Course $course): CourseResource
    {
        $this->authorize('view', $course);

        return new CourseResource($course->load(['category', 'instructor'])->loadCount('enrollments'));
    }

    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $course->update($request->validated());

        return new CourseResource($course->load(['category', 'instructor']));
    }

    public function destroy(Course $course): Response
    {
        $this->authorize('delete', $course);

        $course->delete();

        return response()->noContent();
    }
}
