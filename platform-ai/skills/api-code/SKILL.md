---
name: api-code
description: >-
  /api-code command for Laravel API Base implementation from an approved
  backend spec YAML. Script-first via pnpm api:gen, then agent HANDOFF only.
disable-model-invocation: true
---

# /api-code — Backend Implementation

Requires `approval.status: approved` on `01-backend-spec.yaml` (after `/grill-api-spec`).

Shared extracts: `.cursor/extracts/codegen.md`, `api-codegen-tags.md`, `http-layer.md`, `entity-relationship.md`, `media-s3.md`, `call-external.md`, `cross-entity-service.md`, `agent-discipline.md`, `verify-gate.md`

Reference: `.cursor/skills/api-base/SKILL.md` for deep API Base patterns.

## Input

```text
docs/features/{slug}/01-backend-spec.yaml
docs/features/{slug}/02-openapi.yaml
docs/features/{slug}/03-mock-data.yaml
docs/features/{slug}/generated/HANDOFF.md
docs/features/{slug}/generated/codegen.manifest.json
src/make_help.md
```

## Order (script-first)

1. Confirm grill gates passed (`api:gen:dry`, `openapi:lint`) and `approval.status: approved`
2. Repo root: `pnpm api:gen --spec docs/features/{slug}/01-backend-spec.yaml --write-spec`
3. Read `generated/codegen.manifest.json` + `generated/HANDOFF.md` — implement **only** `#manual-action` / `#manual-service` / `#manual-test` items
4. Do **not** hand-scaffold classes listed in `codegen.commands` — generators already ran
5. Align Resource with OpenAPI; update YAML if contract correction found
6. Verify smoke: `cd src && php artisan test --filter={Module}` (`verify-gate.md`)
7. Update `.harness/progress.md` when present

## Rules

- Controller thin; Action syncs relationships; Query filters; Resource maps only
- Pivot M-N: no model; path-only media (`media-s3.md`)
- Hashtag tags: follow `call-external.md` / `cross-entity-service.md`
- External HTTP only in Service classes

## Done

- Code matches approved spec + OpenAPI
- HANDOFF manual items checked off
- No Laravel OpenAPI decorators
- Test output reported with evidence
