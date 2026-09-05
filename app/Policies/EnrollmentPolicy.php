<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('enrollments.view');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->can('enrollments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('enrollments.create');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->can('enrollments.update');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->can('enrollments.delete');
    }
}
