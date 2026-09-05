---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## SPA auth is cookie-based only
Authentication is Laravel Sanctum SPA cookie/session auth (EnsureFrontendRequestsAreStateful + the web session guard). Never introduce API token or localStorage-based auth for the Vue frontend — it must stay cookie/CSRF based per the project spec.

## Use $this->authorize(), not authorizeResource()
The base Controller only has the AuthorizesRequests trait (no legacy Illuminate\Routing\Controller middleware() support), so authorizeResource() fails with 'Call to undefined method middleware()'. Call $this->authorize('ability', $model) explicitly in index/show/destroy; store/update authorization is handled by the Form Request's authorize() method instead.
