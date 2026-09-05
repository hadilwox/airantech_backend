---
paths:
  - 'lang/**'
---

# Lang

## Persian lang files are real translations, not just placeholders
APP_LOCALE and APP_FALLBACK_LOCALE are both 'fa', and Laravel 11+ ships no lang/ directory by default — without lang/fa/{validation,auth,passwords,pagination}.php, every translated message (validation errors, auth.failed, etc.) silently renders as the raw key (e.g. 'validation.required') instead of text. These files now exist with full Persian translations and a validation.attributes map for our domain fields (national_id, tuition_fee, etc.) — keep it in sync whenever a new field needs a validation rule, so messages stay readable instead of falling back to raw keys.
