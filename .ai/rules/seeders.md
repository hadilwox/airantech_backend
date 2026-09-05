---
paths:
  - database/seeders/RolePermissionSeeder.php
---

# Seeders

## Permission naming convention
Permission strings are always "resource.action" (e.g. students.view, courses.create), defined centrally in RolePermissionSeeder. Never invent ad-hoc permission name shapes elsewhere — add new permissions to that seeder so the full set stays discoverable in one place.
