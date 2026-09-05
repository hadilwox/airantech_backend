---
paths:
  - 'app/Policies/**'
---

# Policies

## Ownership-scoped access uses Policies, not permission strings
Broad role/permission grants (spatie/laravel-permission) control which resource types a role can touch. A user's access to their OWN record (a student viewing their own profile, an instructor managing their own course) must be enforced with a Laravel Policy, not a permission string — permissions are not scoped to record ownership.
