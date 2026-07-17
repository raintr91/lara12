---
name: grill-api
extractBundle: grill-api
description: /grill-api — review API spec vs legacy.
disable-model-invocation: true
---

# /grill-api — API Implementation Check (Portal)

After backend `/api-code` in `api/` repo, before Portal `/wire`.

For **contract YAML** audit before coding, use `/grill-api-spec` in `api/` repo (not this command).

**Extracts:** `extractBundle: grill-api` → `.cursor/extracts/extract-registry.json`

## Checklist

- Endpoints cover spec actions (CRUD/import/export/login-as/etc.).
- No legacy page-init APIs; create/login SPA-init; detail API for edit/copy.
- Request/response keys, relationships, pagination match FE `models/`.
- Validation, permission, error shapes documented for `/wire`.
- Backend test or verification status recorded.

## Guardrails

- No Portal UI edits; no contract renames for FE convenience.
- No "complete" without backend evidence.
