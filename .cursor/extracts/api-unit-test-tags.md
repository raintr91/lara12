# API unit test tags

> Registry: `shared/api-unit-test.registry.json` · validate: `pnpm api:unit-registry`  
> Manifest: `docs/features/{slug}/generated/unit.manifest.json`  
> Diagram: [`docs/operational/UNIT-PHASE-DIAGRAM.md`](../../docs/operational/UNIT-PHASE-DIAGRAM.md) — unit lane + `#needs-unit-test` lifecycle  
> App concerns: `src/tests/Unit/Concerns/` (commonBaselines — never per-feature gen)

## Who adds what

| Phase | Adds |
|-------|------|
| `api:gen` | `#gen:test-module` → stubs; `#gen:test-unit` → `api:unit-gen` (crud-standard default, no `--force` unless `api:gen --force`) |
| `api:unit-gen` | enriched + `*BehaviorTest.php`; **skips** `m:module-test` when codegen/workspace stubs exist (`phase=all`) |
| `/unit` | Verify manifest green; **not** hand-implement planned patterns at base phase |
| `/grill-unit` | Coverage audit scoped to module |

## manualTopicMap → patternId

Grill `#manual-action:*` topics route to registry patterns (no duplicate needsUnit):

| Topic | Pattern |
|-------|---------|
| `chain-scope` | `query.chainScope` |
| `default-per-page` | `request.defaultPerPage` |
| `nested-managers-resource` | `resource.nestedRelations` |
| `relationships` | `action.relationshipSync` |

## Hashtags

| Tag | Behavior |
|-----|----------|
| `#gen:test-module` | `m:module-test` structural stubs (via `api:gen`) |
| `#gen:test-module-stub` | Force `m:module-test` in `api:unit-gen` (bypass stub dedupe) |
| `#gen:test-unit` | Auto `api:unit-gen` after `api:gen` (profile default; inherits `--force` only from `api:gen --force`) |
| `#gen:test-module-test-support` | `ModuleTestSupport.php` |
| `#gen:test-request-validation-hooks` | Enriched request tests |
| `#gen:test-controller-invoke` | `{Entity}ControllerInvokeTest` |
| `#needs-unit-test:*` | Registry debt (`status: planned`) — promote to template |
| `#skip-unit-test:{layer}` | Skip pattern layer |

## Stub dedupe

`api:unit-gen --phase all` skips `moduleTest.stub` when `codegen.manifest.json` or workspace already has structural `*Test.php` for the entity. Override: `--phase stub`, `#gen:test-module-stub`, or `--force`.

## Commands

```bash
pnpm api:unit-registry
pnpm api:unit-gen --spec docs/features/chain/hotel/01-backend-spec.yaml --force
cd src && php artisan test --testsuite=ModuleChain --filter=Hotel
```
