---
name: api
extractBundle: api
description: /api — implement Laravel API from portal ir/spec.
disable-model-invocation: true
---

# /api — Backend API

**Extracts:** `extractBundle: api` → `.cursor/extracts/extract-registry.json`

## Scope

Backend work in the configured backend project (`api/`) only — not Portal frontend files.

## Backend repo commands

In `api/` workspace (see `api/.cursor/skills/api/SKILL.md`):

| Command | Step |
|---------|------|
| `/api-spec` | Portal spec → backend spec + OpenAPI + mock YAML |
| `/grill-api-spec` | Audit backend contract before code (run in `api/` repo) |
| `/api-code` | Approved backend spec → Laravel implementation |
| `/api` | Router — defaults to spec if no approved backend spec |

Hashtags: `#call-external`, `#cross-entity-service` → policy on **base-docs** (`product/shared/integrations/`). Detect/mark only here.

## Before Work (from Portal)

1. Resolve backend id via `legacy/project-config.md` (Grep `contract.backend` / `projects.<id>.root` only — **do not** paste full `platform-repos.json`). Stop if missing.
2. Read feature `ir/spec.yaml` (and testcase only if needed for contract).
3. Align contract keys with Portal `models/`.

## Rules

- Do not implement backend in the portal workspace.
- Do not modify Portal UI unless explicitly requested.
- Endpoints, validation, resources, permissions, backend tests per backend repo conventions.
- Report every project read or changed.

## Handoff

Document paths, payloads, validation errors, permissions for `/wire`.

**Backend repo pipeline:** `/api-spec` → `/grill-api-spec` → `/api-code` in `api/`. Portal `/grill-api` runs after implementation, before `/wire`.
