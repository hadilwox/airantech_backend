<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseCategoryRequest;
use App\Http\Requests\UpdateCourseCategoryRequest;
use App\Http\Resources\CourseCategoryResource;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CourseCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CourseCategory::class);

        $categories = CourseCategory::query()
            ->withCount('courses')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(min((int) $request->integer('per_page', 15), 100));

        return CourseCategoryResource::collection($categories);
    }

    public function store(StoreCourseCategoryRequest $request): CourseCategoryResource
    {
        $category = CourseCategory::create($request->validated());

        return new CourseCategoryResource($category);
    }

    public function show(CourseCategory $courseCategory): CourseCategoryResource
    {
        $this->authorize('view', $courseCategory);

        return new CourseCategoryResource($courseCategory->loadCount('courses'));
    }

    public function update(UpdateCourseCategoryRequest $request, CourseCategory $courseCategory): CourseCategoryResource
    {
        $courseCategory->update($request->validated());

        return new CourseCategoryResource($courseCategory);
    }

    public function destroy(CourseCategory $courseCategory): Response
    {
        $this->authorize('delete', $courseCategory);

        $courseCategory->delete();

        return response()->noContent();
    }
}
