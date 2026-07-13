# Unit Test Coverage (API)

Standalone rule — **not** in `TEAM-AI-BACKEND-WORKFLOW` diagram · **no** portal `testcases/*.yaml`.

Diagram: `docs/operational/UNIT-PHASE-DIAGRAM.md` (unit lane + `#needs-unit-test` lifecycle)

Skill: `.cursor/skills/unit/SKILL.md` · Grill: `.cursor/skills/grill-unit/SKILL.md`  
Rule: `.cursor/rules/team-flow-unit.mdc`

`m:module-test` stubs are **scaffolding only** — `/unit` uses `api:unit-gen` + clears `needsUnit[]` with **behavioral** coverage tied to spec + OpenAPI.

Tag DSL: `.cursor/extracts/api-unit-test-tags.md` · Manifest: `generated/unit.manifest.json`

## Test layout

| Class | Generated stub path | Layer |
|-------|---------------------|-------|
| `{Entity}Controller` | `Modules/{M}/Tests/Feature/Http/Controllers/{Entity}ControllerTest.php` | Feature (default) |
| `{Entity}Action` | `Modules/{M}/Tests/Unit/Http/Actions/{Entity}ActionTest.php` | Unit |
| `{Entity}Query` | `Modules/{M}/Tests/Unit/Http/Queries/{Entity}QueryTest.php` | Unit |
| `{Entity}*Request` | `Modules/{M}/Tests/Unit/Http/Requests/{Entity}*Request.php` | Unit |
| `{Entity}Resource` | `Modules/{M}/Tests/Unit/Http/Resources/{Entity}ResourceTest.php` | Unit |
| App model | `tests/Unit/Models/{Platform\|Tenant}/{Entity}ModelTest.php` | Unit |
| Cross-domain Service | `tests/Unit/Services/...` or module-local | Unit |

Controller isolated checks: `m:module-test {M} --type=controller --class={Entity} --controller-layer=unit`

## Coverage matrix (fill per feature)

Map each `requirements.covered[]` + HANDOFF topic → at least one test.

| Layer | Must cover (behavior) | Mock boundary |
|-------|----------------------|---------------|
| **Request** | `rules()`, `authorize()`, defaults (e.g. `per_page`) | None |
| **Query** | scopes (`chain_id`), filters, sort, pagination defaults | Eloquent: `Model::factory()` or query spy; no HTTP |
| **Action** | create/update/delete + **relationship sync** | DB `RefreshDatabase` or model doubles |
| **Resource** | JSON shape vs `02-openapi.yaml`, nested relations | `Model::factory()->make()` |
| **Controller** | wired traits, envelope (`data` + `meta.pagination`), status codes | Mock Action/Query at constructor; Feature test optional |
| **Model** | fillable, casts, relationships, scopes | Factory |
| **Service** | orchestration, error mapping | HTTP client / external API mock only |

Stub tests (`test_target_file_exists`, `test_*_is_loadable`) **do not** count as done for `/unit`.

## Script-first (missing stubs)

From repo root or `src/`:

```bash
cd src
php artisan m:module-test Chain --type=all --skip-questions
# or per class:
php artisan m:module-test Chain --type=query --class=Hotel --skip-questions
php artisan m:module-test Chain --type=action --class=Hotel --skip-questions
php artisan m:module-test Chain --type=request --class=HotelSearch --skip-questions
php artisan m:module-test Chain --type=resource --class=Hotel --skip-questions
php artisan m:module-test Chain --type=controller --class=Hotel --skip-questions
```

Use `--force` only to overwrite an existing test file intentionally.

## Verify (scoped)

```bash
cd src
php artisan test --testsuite=ModuleChain
php artisan test --filter=Hotel
php artisan test Modules/Chain/Tests/Unit
```

Coverage (pcov — see `phpunit.xml`):

```bash
cd src
composer test:coverage:module:chain-html   # when testsuite exists
# generic:
php artisan test --testsuite=ModuleChain --coverage-filter=Modules/Chain --coverage-text
```

Report HTML under `src/coverage/module-{name}/`. State line/branch gaps in `/grill-unit`.

## Spec inputs

```text
docs/features/{slug}/01-backend-spec.yaml   # requirements, relationships, scope
docs/features/{slug}/02-openapi.yaml          # Resource contract
docs/features/{slug}/03-mock-data.yaml        # fixtures
docs/features/{slug}/generated/HANDOFF.md     # #manual-test topics
```

## `#manual-test` topics → test focus

| Topic | Tests to add |
|-------|----------------|
| `relationships` | Action sync, Query `with()`, Resource nested |
| `chain-scope` | Query scoped by session `chain_id` |
| `default-per-page` | SearchRequest default + Query paginate |
| `nested-managers-resource` | Resource array shape vs OpenAPI |
| `export-open-rate-report` | Service unit + optional Feature |
| Service id from `#manual-service` | Service class + HTTP mock |

## Rules

- One behavior → one test method; name `test_{behavior}_when_{condition}`.
- Prefer Unit for Query/Action/Request/Resource; Feature for full HTTP only when trait wiring needs it.
- `RefreshDatabase` for DB assertions; factories from `database/factories`.
- No testing framework internals; assert public outcomes only (`verify-gate.md`).
