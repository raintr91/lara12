---
name: grill-unit
description: >-
  /grill-unit command for auditing PHPUnit behavioral coverage after /unit.
  Standalone API rule — not in team workflow diagram or portal testcase flow.
disable-model-invocation: true
---

# /grill-unit — Unit Coverage Audit (standalone)

After `/unit`. API-only — không testcase YAML, không team diagram.

Diagram: `docs/operational/UNIT-PHASE-DIAGRAM.md` — grill **audit** coverage/reqIds; không loop săn 100%, không gọi `api:unit-gen` (quay `/unit` nếu thiếu file).

Shared extracts: `.cursor/extracts/unit-coverage.md`, `verify-gate.md`

Align with `.cursor/skills/unit/SKILL.md` Done criteria.

## Input

```text
docs/features/{slug}/01-backend-spec.yaml
docs/features/{slug}/02-openapi.yaml
src/Modules/{Module}/Tests/
src/tests/Unit/Models/
Latest php artisan test / coverage output
```

## Checklist

### Requirements traceability

- [ ] Each `requirements.covered[]` id has ≥1 behavioral test (name or comment references REQ id)
- [ ] Each `api.endpoints[]` action has controller/route coverage or documented Feature test
- [ ] OpenAPI response fields asserted in Resource or Feature test

### Layer matrix (`unit-coverage.md`)

- [ ] Request — rules + authorize (+ defaults if HANDOFF says so)
- [ ] Query — scope/filters/sort/pagination per spec
- [ ] Action — relationship sync if `#manual-action:relationships`
- [ ] Resource — nested fields vs OpenAPI
- [ ] Controller — wired endpoints match `codegen.wire`
- [ ] Model — relationships if Platform/Tenant shared model
- [ ] Service — if `#manual-service` or `#call-external`

### Anti-patterns (fail grill)

- Stub-only file (only `test_target_file_exists` / loadable / extends base)
- Skipped tests without reason in HANDOFF or spec `openQuestions`
- Testing private methods or framework internals
- No fresh test run evidence in session

### Coverage (when pcov available)

```bash
cd src && php artisan test --testsuite=Module{Module} --coverage-filter=Modules/{Module} --coverage-text
```

- [ ] Name uncovered classes/lines for Action, Query, Request, Resource under test
- [ ] Target: 100% on **feature-scoped** classes (not whole monorepo)

## Output

```markdown
## grill-unit — {slug}

| Check | Status |
|-------|--------|
| REQ-… | pass / gap |

### Gaps
- {file} — {missing behavior}

### Verdict
ready | needs /unit pass
```

## Done

Gap list delivered; verdict `ready` only when checklist passes and scoped tests green.
