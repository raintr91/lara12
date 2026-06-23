---
name: team-phase3-backend-code
description: >-
  Step 2 of backend phase 3b. Implement Laravel API Base code from an approved
  backend API spec YAML, using make_help.md and m:* generators.
disable-model-invocation: true
---

# /api-code — Backend Implementation

Use when the user says `/api-code`, "implement backend theo spec", or asks to code Laravel from an approved `01-backend-spec.yaml`.

This step assumes Step 1 contract has been reviewed.

## Input

Read:

```text
src/docs/features/{slug}/01-backend-spec.yaml
src/docs/features/{slug}/02-openapi.yaml
src/docs/features/{slug}/03-mock-data.yaml
src/make_help.md
src/docs/CONVENTIONS.md
src/docs/GENERATORS.md
```

Also use `.cursor/skills/api-base/SKILL.md`.

If the spec contains special tags, read the matching skill:

- `call-external` → `.cursor/skills/call-external/SKILL.md`
- `cross-entity-service` → `.cursor/skills/cross-entity-service/SKILL.md`

## Required Order

1. Read `src/make_help.md` before creating classes.
2. Map backend spec to generator commands.
3. Use `m:*` / `add:*` generators where available.
4. Implement migrations, fillable fields, and relationships.
5. Implement Request rules from spec.
6. Implement Query filters/sorts/includes from spec.
7. Implement Action create/update/delete and relationship sync.
8. Implement Resource response aligned with OpenAPI YAML.
9. Update YAML docs if implementation reveals a contract correction.
10. Run scoped verification.

## API Base Rules

- Controller stays thin.
- Request contains rules only.
- Action mutates DB and syncs relationships.
- Query handles search/detail/filter/sort/include.
- Resource maps response shape and does not query DB.
- Service is for cross-domain or unrelated orchestration only.
- Pivot M-N has no model.
- S3/media stores path only, response may expose URL.

## Service Tags

- `call-external`: generate Service/integration client; do not put external HTTP calls in Action, Query, Controller, Request, or Resource.
- `cross-entity-service`: keep Action/Query as API entry; Action/Query may call Service for rare synchronous orchestration across independent entities.
- If two entities have an Eloquent relationship, do not use `cross-entity-service`; use parent Action/Query and Laravel relationship APIs.
- If the second entity is only a side effect not needed by FE response, prefer Event/Observer/Job before Service.

## Verify

Prefer scoped commands:

```bash
php artisan test --filter={Module}
```

If command cannot run locally, report the blocker and what was verified by static review.

## Done

- Laravel code follows approved backend spec.
- Resource/request/query/action match OpenAPI YAML.
- No Laravel OpenAPI decorators added.
- Verification result is reported.
