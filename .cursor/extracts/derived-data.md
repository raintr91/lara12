# Derived / Denormalized Data

Use when BE adds fields/tables not in FE spec for search, sort, compute, or static snapshots.

Requirements in backend spec:

- `backendOnly: true`
- `sourceOfTruth`, `refresh` strategy (Action/Observer/Job/Command/Scheduler)
- `staleness` if not realtime
- Do not expose in OpenAPI unless FE needs it; mark `derived/computed`
- Do not hide wrong relationship design with derived tables

Implementation: field on main entity vs separate snapshot model; index for search/sort; no seed data in migrations.

See `src/docs/BACKEND_API_SPEC_GUIDE.md` for YAML examples.
