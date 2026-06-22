---
name: team-phase3-backend-spec
description: >-
  Step 1 of backend phase 3b. Analyze portal feature specs/testcases into
  backend API contracts, entity/module plans, OpenAPI YAML, and mock data before
  Laravel implementation.
disable-model-invocation: true
---

# /api-spec — Backend API Contract

Use when the user says `/api-spec`, "phase 3b spec", "bóc API từ spec", or asks to analyze FE specs into backend APIs.

This step does **not** implement Laravel code.

## Input

Read from Portal when a feature slug is provided:

```text
../portal/docs/features/{slug}/
├── spec.yaml
├── testcases/*.yaml
└── generated/
```

Also read:

```text
src/docs/BACKEND_API_SPEC_GUIDE.md
src/docs/templates/backend-api.yaml
src/docs/templates/openapi.yaml
src/docs/templates/mock-data.yaml
```

## Output

Create or update:

```text
src/docs/features/{slug}/
├── 01-backend-spec.yaml
├── 02-openapi.yaml
├── 03-mock-data.yaml
└── generated/
    └── backend-spec.md
```

If `src/docs/features/{slug}/01-backend-spec.yaml` already exists, update it in place unless the user explicitly asks for a new feature spec or the change is a separate bounded context.

## Required Analysis

1. Understand the feature as a group of related screens/use-cases.
2. Decide module/API prefix from actor, permission, portal layout, and bounded context.
3. Decide Platform vs Tenant entity mode.
4. Identify aggregate roots, child entities, and pivot M-N tables.
5. Identify relationships and sync strategy.
6. Split endpoints by lifecycle, permission, cache, source data, pagination, and payload weight.
7. Mark reused APIs explicitly, especially detail for detail + edit initial form data.
8. Define request/response shapes, validation, filters, sorts, includes, and errors.
9. Generate OpenAPI YAML and mock data from the backend spec.
10. Record open questions instead of guessing silently.

## Spec Evolution Rules

When Portal spec/testcases changed after BE spec was created:

1. Read the current Portal spec/testcases.
2. Read the existing backend spec/OpenAPI/mock data.
3. Identify added, changed, and removed requirements.
4. Update the existing backend spec for the same feature slug.
5. Add a `changeLog` entry with source, summary, impact, and whether it is breaking.
6. Add or update `decisions` for important API reuse/split choices.
7. Update OpenAPI YAML and mock data to match the changed backend spec.
8. Keep existing approved endpoints stable unless a breaking change is explicitly approved.

Create a new backend spec only when the change is a new bounded feature, a different actor/context with independent lifecycle, a new API version, or the user asks for a split.

Example: if FE adds "chain setting" to "admin chain management", update `docs/features/chain/*` and add ChainSetting relationship/endpoints there. Do not create `setting-chain` as a separate spec unless it is independent.

## Rules

- Do not scaffold Laravel classes in this step.
- Do not use Laravel OpenAPI decorators.
- OpenAPI source is YAML.
- Prefer multiple focused APIs over one large summary API when UI blocks have different lifecycles.
- Use `select-items` for dropdown/autocomplete contracts.
- Pivot M-N has no model.

## Done

- Backend spec YAML exists.
- OpenAPI YAML exists.
- Mock data YAML exists.
- Generated Markdown review exists.
- Contract can be reviewed by FE/BE before `/api-code`.
