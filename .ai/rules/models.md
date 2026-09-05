---
paths:
  - 'app/Models/**'
---

# Models

## Server-set fields excluded from Fillable need forceFill(), not create()/update()
When a column is deliberately left out of a model's #[Fillable] list (e.g. Course::code/jalali_year/jalali_month, User::is_active before it was added) so API requests can't set it directly, mass-assignment via create()/update() SILENTLY drops it — no exception, just a NULL/unchanged column, which surfaces as a confusing NOT NULL constraint failure or a value that never persists. When a controller legitimately needs to set one of these (course code generation, admin toggling is_active), use $model->forceFill([...])->save() for that specific write, keeping the client-supplied data going through $request->safe()/validated() as normal.
