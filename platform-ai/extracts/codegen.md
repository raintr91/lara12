# Codegen — make_help + api-gen

Run artisan from `src/`. Read `src/make_help.md` before creating classes.

**Script-first:** `pnpm api:gen` (repo root) runs planned `m:*` / `add:*` from `01-backend-spec.yaml`. Agent fills HANDOFF only.

| Need | Command |
|------|---------|
| Plan / gate | `pnpm api:gen:dry --spec docs/features/{slug}/01-backend-spec.yaml` |
| Execute | `pnpm api:gen --spec docs/features/{slug}/01-backend-spec.yaml --write-spec` |
| Module | `m:module {Module}` |
| Model | `m:model {Name} Platform\|Tenant` |
| CRUD stack | `m:controller {Module} {Entity}` + `--path-model` + `--shared-model` |
| One endpoint | `add:action {Module} {Entity} {action}` |
| Select FE | `add:select-item {Module} {Entity}` |
| 1-1 setting | `m:add-createOrUpdate {Module} {Parent} {rel} {method}` |
| Tests | `m:module-test {Module}` |

Tags & readiness: `.cursor/extracts/api-codegen-tags.md`, `api-codegen-readiness.md`

- `--skip-questions` + yes/no for non-interactive
- `m:model-module` deprecated → `m:model`

Do not hand-scaffold when generator or `api:gen` plan exists.
