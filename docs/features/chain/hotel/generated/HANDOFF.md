# HANDOFF — chain-hotel

Generated: 2026-06-27T11:29:39.056Z
Spec: `docs/features/chain/hotel/01-backend-spec.yaml`
Manifest: `generated/codegen.manifest.json`

## Generator commands

- _(none — check codegen block)_

## Tag plan

| Tag | Phase | Status | Next |
|-----|-------|--------|------|
| `#gen:module` | codegen | skipped | `m:module Chain` |
| `#gen:model-platform` | codegen | skipped | `m:model Hotel Platform --create-model=yes --create-migration=yes --create-factory=yes --create-seeder=no --skip-questions` |
| `#gen:crud` | codegen | skipped | `m:controller Chain Hotel ...` |
| `#gen:test-module` | codegen | skipped | `m:module-test Chain --type=controller --class=Hotel --skip-questions` |
| `#manual-action:relationships` | handoff | handoff | HANDOFF: relationships |
| `#manual-action:chain-scope` | handoff | handoff | HANDOFF: chain-scope |
| `#manual-action:default-per-page` | handoff | handoff | HANDOFF: default-per-page |
| `#manual-action:nested-managers-resource` | handoff | handoff | HANDOFF: nested-managers-resource |

## Execution log

- **module** [OK]: `php artisan m:module Chain`
- **model** [OK]: `php artisan m:model Hotel Platform --create-model=yes --create-migration=yes --create-factory=yes --create-seeder=no --skip-questions`
- **controller-wizard** [OK]: `php artisan m:controller Chain Hotel ...`
- **module-test** [OK]: `php artisan m:module-test Chain --type=controller --class=Hotel --skip-questions`

## Agent next — #manual-action

- [ ] **relationships** — Sync Eloquent relationships in Action — `entity-relationship.md`
- [ ] **chain-scope** — Scope Query by session chain_id — tenant/chain context in Query
- [ ] **default-per-page** — Set default per_page (e.g. 100) in SearchRequest or Query
- [ ] **nested-managers-resource** — Map nested managers in Resource — align with OpenAPI `02-openapi.yaml`

## Verify

```bash
cd src && php artisan test --filter=Chain
```

## OpenAPI

Resource fields must match `02-openapi.yaml`. Update YAML if contract fix found.
