---
name: api-code
description: >-
  /api-code command for Laravel API Base implementation from an approved
  backend spec YAML, using make_help.md and m:* generators.
disable-model-invocation: true
---

# /api-code — Backend Implementation

Requires reviewed `01-backend-spec.yaml` (ideally after `/grill-api-spec`).

Shared extracts: `.cursor/extracts/codegen.md`, `http-layer.md`, `entity-relationship.md`, `media-s3.md`, `call-external.md`, `cross-entity-service.md`, `verify-gate.md`

Reference: `.cursor/skills/api-base/SKILL.md` for deep API Base patterns.

## Input

```text
src/docs/features/{slug}/01-backend-spec.yaml
src/docs/features/{slug}/02-openapi.yaml
src/docs/features/{slug}/03-mock-data.yaml
src/make_help.md
```

## Order

1. Map spec → generator commands (`codegen.md`)
2. Migrations, models, relationships, Request, Query, Action, Resource
3. Align Resource with OpenAPI; update YAML if contract correction found
4. Scoped verify: `php artisan test --filter={Module}`

## Rules

- Controller thin; Action syncs relationships; Query filters; Resource maps only
- Pivot M-N: no model; path-only media (`media-s3.md`)
- Hashtag tags: follow `call-external.md` / `cross-entity-service.md`

## Done

- Code matches approved spec + OpenAPI
- No Laravel OpenAPI decorators
- Verification reported with evidence
