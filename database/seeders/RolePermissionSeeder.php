<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions not granted to the Academy Manager role by default.
     *
     * @var list<string>
     */
    private const MANAGER_EXCLUDED_PERMISSIONS = [
        'roles.manage',
    ];

    /**
     * Seed roles and the granular, resource.action-named permission set.
     *
     * Permission names follow "resource.action" so future modules can
     * register more of the same shape without redesigning the RBAC system.
     */
    public function run(): void
    {
        $permissions = [
            'students.view', 'students.create', 'students.update', 'students.delete',
            'instructors.view', 'instructors.create', 'instructors.update', 'instructors.delete', 'instructors.approve',
            'courses.view', 'courses.create', 'courses.update', 'courses.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'enrollments.view', 'enrollments.create', 'enrollments.update', 'enrollments.delete',
            'attendance.manage',
            'payments.manage',
            'exams.manage',
            'reports.view',
            'roles.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Model events (which Spatie relies on to invalidate its permission
        // cache) may be muted by a WithoutModelEvents seeder up the call
        // chain, so clear the cache explicitly before assigning roles.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate(RoleName::Admin->value, 'web');
        $admin->syncPermissions($permissions);

        $manager = Role::findOrCreate(RoleName::AcademyManager->value, 'web');
        $manager->syncPermissions(array_diff($permissions, self::MANAGER_EXCLUDED_PERMISSIONS));

        // Instructor/Student get no blanket permissions — their access is
        // scoped to their own data via Policies, not permission grants.
        Role::findOrCreate(RoleName::Instructor->value, 'web');
        Role::findOrCreate(RoleName::Student->value, 'web');
    }
}
