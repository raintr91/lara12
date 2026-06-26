# Codegen — make_help Required

Run artisan from `src/`. Read `src/make_help.md` before creating classes.

| Need | Command |
|------|---------|
| Module | `m:module {Module}` |
| Model | `m:model {Name} Platform\|Tenant` |
| CRUD stack | `m:controller {Module} {Entity}` + `--path-model` + `--shared-model` |
| One endpoint | `add:action {Module} {Entity} {action}` |
| Select FE | `add:select-item {Module} {Entity}` |
| 1-1 setting | `m:add-createOrUpdate {Module} {Parent} {rel} {method}` |
| Tests | `m:module-test {Module}` |

- `--skip-questions` + yes/no for non-interactive
- `m:model-module` deprecated → `m:model`

Do not hand-scaffold when a generator exists.
