# Legacy trace module file

Path: `docs/features/yaml/{role}/{domain}/_legacy.trace.yaml`

Template: `docs/templates/legacy-trace.yaml`

- `index` — functionId → slice metadata
- `slices` — observed facts per child function
- `refs` — `legacy://` URI map
- Evidence: pointer only (`legacy/evidence.md`)

Per-function `ir/legacy.yaml` uses `legacyRef: { module, function, slice }` when trace exists.

## Cross-repo paths

- `legacy.repo` = slug `projects.*.repo` trong platform-repos
- `evidence.file` / `refs.*.file` = path **relative** legacy project root (resolve `root` từ config)
- `refs` keys: `legacy://{entity}/{action}`

## Stale trace

Legacy commit đổi → **không** auto re-mine. Trigger: tag `#legacy-recheck` trong bundle → `/update-spec-legacy` micro-read evidence → patch trace slice + `bundle.legacy` → `spec:split` + `legacy-trace:validate`.
