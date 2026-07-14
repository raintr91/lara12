# Spec Evolution (Backend YAML)

When Portal spec/testcases change after backend spec exists → **`/api-update-spec`** (see `api-spec-sync.md`).

Quick rules:

1. Read latest Portal `*.spec.yaml` + testcases and existing `01-backend-spec.yaml`
2. Diff added/changed/removed requirements
3. Update same feature slug in place (same bounded context)
4. Add `changeLog` entry: source, summary, impact, breaking or not
5. Bump `feature.version` (semver — `api-spec-sync.md`)
6. Update `source.portalRefs` + `pendingTechDebt`
7. Update OpenAPI + mock data to match
8. Keep stable endpoints unless breaking change approved → re-`/grill-api-spec`

New spec file only for: new bounded feature, different actor/context lifecycle, new API version, or user asks to split.

Example: add chain setting to chain module → extend chain spec, do not create unrelated `setting-chain` spec.

**Deferred portal functions** not yet in contract → `pendingTechDebt[]`, not silent omit.

**BE-only changes** (no FE impact) → `/api-update-spec --be-only` + `beOnlyRequirements[]`.
