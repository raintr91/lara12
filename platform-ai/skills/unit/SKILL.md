---
name: unit
description: >-
  /unit command for Laravel API Base PHPUnit tests. Standalone API rule — not
  part of team workflow diagram or portal testcase YAML. Manifest-first: run
  api:unit-gen, clear needsUnit, behavioral coverage for Action, Query, Request,
  Resource, Controller, Model, and Service classes.
disable-model-invocation: true
---

# /unit — API PHPUnit (standalone)

**API-only** — không nằm diagram `TEAM-AI-BACKEND-WORKFLOW.md`, không đọc `testcases/*.yaml`, không qua `/api` router.

Diagram: `docs/operational/UNIT-PHASE-DIAGRAM.md` (unit lane + `#needs-unit-test` lifecycle)

Invoke trực tiếp: `/unit {slug}` hoặc `/unit {Module} {Entity}`.

Shared extracts: `.cursor/extracts/unit-coverage.md`, `.cursor/extracts/api-unit-test-tags.md`, `agent-discipline.md`, `verify-gate.md`, `http-layer.md`, `entity-relationship.md`

Reference: `unitgen/runners/README.md`, `docs/api-base/GENERATORS.md`, `src/make_help.md` (`m:module-test`)

## Input (manifest-first)

```text
docs/features/{slug}/generated/unit.manifest.json   # needsUnit[], files[], written[]
docs/features/{slug}/generated/UNIT-HANDOFF.md
docs/features/{slug}/generated/codegen.manifest.json
docs/features/{slug}/01-backend-spec.yaml
docs/features/{slug}/02-openapi.yaml
src/Modules/{Module}/Tests/
```

Resolve `{Module}` / `{Entity}` from args, manifest, or spec `codegen`.

## Order

1. Read `unit.manifest.json` — `needsUnit[]`, `files[]`, `mocks[]`
2. If missing manifest → `pnpm api:unit-gen --spec docs/features/{slug}/01-backend-spec.yaml`
3. Re-run gen: `pnpm api:unit-gen --spec ... --phase enriched --force` (overwrite enriched) or `--phase stub --force` (refresh stubs)
4. Check `skippedPatterns[]` in manifest — `moduleTest.stub` skipped = stubs already from `api:gen`
5. Process `needsUnit[]` — **registry debt only** (`status: planned`). Do not hand-write per feature at base phase; promote pattern in registry instead.
6. **Verify** manifest green (do not implement gaps manually):
   ```bash
   cd src && php artisan test --testsuite=Module{Module}
   cd src && php artisan test --filter={Entity}
   ```
7. Hand off to `/grill-unit` for coverage audit

## Rules

- App concerns live in `src/tests/Unit/Concerns/` — module only gets `ModuleTestSupport.php`
- Do **not** count structural stub methods as finished coverage
- Map `reqIds` when behavioral patterns are promoted to `implemented`

## Done

- `needsUnit: []` means all registry patterns are `implemented` for this feature's `when`
- Scoped `php artisan test` passes (UnitTestCase for enriched unit layer)
