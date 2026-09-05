<?php

namespace App\Http\Controllers;

use App\Enums\InstructorStatus;
use App\Enums\RoleName;
use App\Http\Requests\StoreInstructorRequest;
use App\Http\Requests\UpdateInstructorRequest;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstructorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Instructor::class);

        $instructors = Instructor::query()
            ->with('user')
            ->withCount('courses')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($query) use ($term) {
                    $query->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('national_id', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 15), 100));

        return InstructorResource::collection($instructors);
    }

    public function store(StoreInstructorRequest $request): InstructorResource
    {
        $instructor = DB::transaction(function () use ($request): Instructor {
            $user = User::create([
                'name' => trim("{$request->string('first_name')} {$request->string('last_name')}"),
                'email' => $request->string('email'),
                'phone' => $request->string('mobile_phone'),
                'password' => $request->filled('password') ? $request->string('password') : Str::password(20),
            ]);

            $user->assignRole(RoleName::Instructor->value);

            // Admin-created instructors are already vetted, unlike the
            // self-application flow (Phase 1) which starts as Pending.
            return Instructor::create([
                ...$request->safe()->except(['email', 'password', 'password_confirmation']),
                'user_id' => $user->id,
                'status' => InstructorStatus::Active,
            ]);
        });

        return new InstructorResource($instructor->load('user'));
    }

    public function show(Instructor $instructor): InstructorResource
    {
        $this->authorize('view', $instructor);

        return new InstructorResource($instructor->load('user')->loadCount('courses'));
    }

    public function update(UpdateInstructorRequest $request, Instructor $instructor): InstructorResource
    {
        $instructor->update($request->safe()->except(['email', 'is_active']));

        if ($request->filled('email')) {
            $instructor->user->update(['email' => $request->string('email')]);
        }

        if ($request->has('is_active')) {
            $instructor->user->update(['is_active' => $request->boolean('is_active')]);
        }

        return new InstructorResource($instructor->load('user'));
    }

    public function destroy(Instructor $instructor): Response
    {
        $this->authorize('delete', $instructor);

        $instructor->delete();

        return response()->noContent();
    }
}
