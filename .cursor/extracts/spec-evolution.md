# Spec Evolution (Backend YAML)

When Portal spec/testcases change after backend spec exists:

1. Read latest Portal spec/testcases and existing `01-backend-spec.yaml`
2. Diff added/changed/removed requirements
3. Update same feature slug in place (same bounded context)
4. Add `changeLog` entry: source, summary, impact, breaking or not
5. Update OpenAPI + mock data to match
6. Keep stable endpoints unless breaking change approved

New spec file only for: new bounded feature, different actor/context lifecycle, new API version, or user asks to split.

Example: add chain setting to chain module → extend chain spec, do not create unrelated `setting-chain` spec.
