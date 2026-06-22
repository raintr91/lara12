---
name: team-phase3-backend
description: >-
  Router for backend phase 3b. Prefer Step 1 API contract generation before
  Step 2 Laravel implementation.
disable-model-invocation: true
---

# /api — Backend Phase 3b Router

Backend phase 3b has two steps:

```text
Step 1: /api-spec
  Portal spec/testcases → backend API spec + OpenAPI YAML + mock data

Step 2: /api-code
  Approved backend spec → Laravel API Base implementation
```

## Routing

- `/api-spec`, "phase 3b spec", "bóc API từ spec" → read `.cursor/skills/team-phase3-backend-spec/SKILL.md`.
- `/api-code`, "implement backend theo spec" → read `.cursor/skills/team-phase3-backend-code/SKILL.md`.
- `/api` without an approved `src/docs/features/{slug}/01-backend-spec.yaml` → default to Step 1.
- `/api` with an approved backend spec and explicit implement request → Step 2.

## Rule

Do not skip Step 1 for new features. API contract, Swagger/OpenAPI YAML, and mock data must be reviewable before Laravel code is generated.
