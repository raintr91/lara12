---
name: wire
extractBundle: wire
description: /wire — replace mocks with real API services.
disable-model-invocation: true
---

# /wire — Portal API Integration

Doc hub: `base-docs/platform/toolchain/WIRE-PHASE-DIAGRAM.md` · `base-docs/platform/toolchain/FEATURE-ARTIFACT-FLOWS.md`

**Extracts:** `extractBundle: wire` → `.cursor/extracts/wire/integration.md`

## Ngôn ngữ & Legacy Config

- Viết docs, integration note và handoff bằng tiếng Việt.
- Giữ nguyên contract key, route path, API field, model name và code identifier.
- Nếu nhắc `legacy` / cross-repo: resolve **one** root per `legacy/project-config.md` (progressive). **Không** dump full map. Hub: `base-docs/platform/toolchain/PROJECT-MAPS.md`.

## Load policy

| Load | Do not load |
|------|-------------|
| `{function}/ir/spec.yaml` — `api.endpoints`, contract | Legacy archaeology |
| `{function}/*.bundle.yaml` — tags, `specRevision`, lifecycle | `ir/legacy.yaml` unless `#legacy-recheck` |
| `{function}/*.test.yaml` — E2E expectations | Full module trace |
| Backend contract / staging response (`/api` handoff) | |

## Inputs

- `base-docs Code / `--id`` — approved API contract
- `base-docs Code / `--id`` — `#update:*`, lifecycle fields
- `base-docs Code / `--id``
- Prototype code (mock boundary to remove)

## Order

1. Align `models/` schemas/types with real API.
2. Add/update `services/*` using `$apiFetch`.
3. Update composables to call services.
4. Update validations when form API errors require it.
5. Bind pages/components to composables.
6. Remove production mock imports.
7. Clear **all** `#update:*` tags; set `lastSynced.wire = specRevision`; `featureStatus: wire`; `wireCount += 1`.
8. If bundle edited → run on **base-docs**: `pnpm spec:split` + `pnpm spec:split:check`.
9. Run scoped E2E; `pnpm portal:lifecycle set {route.path} wire`.

## Rules

- Preserve Portal 4-layer architecture.
- Do not rename contract fields just for FE convenience.
- Keep mocks only for tests or local fallback when explicitly needed.
- Do not edit backend unless the task explicitly includes the backend project.

## Done

- Real create/list/update/delete flow works for the scoped entity.
- Mock production path is removed.
- `#update:*` cleared; lifecycle fields updated.
- Lint/typecheck/scoped E2E status is reported.
