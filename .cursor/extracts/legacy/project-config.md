# Platform & Legacy repo config (token-tight)

Hub: `docs/operational/PROJECT-MAPS.md` · IDE multi-root: `platform-bases.code-workspace` (R1) — **not** a substitute for this map.

## When to touch maps

| Need | Action |
|------|--------|
| Work only in **current** repo (same folder) | **Do not** Read `platform-repos.json` / `legacy-repos.json` |
| Sibling FE/BE / tooling path, or `contract.*` | Resolve **one** `projects.<id>` (or active `groups.<name>.contract`) |
| Legacy archaeology (`/legacy-spec`, flow-trace legacy) | Resolve `legacy-repos` **only if** file non-empty / checkout needed |
| List every base “for fun” | **Forbidden** — wastes tokens |

## Resolve order (first hit wins)

```text
platform-repos.local.json → platform-repos.json → platform-repos.example.json
  → ~/.cursor/platform-repos.json   (optional)

legacy-repos.local.json → legacy-repos.json → legacy-repos.example.json
  → ~/.cursor/legacy-repos.json
```

Deprecated: `team-projects.json`, `legacy-projects.json`, `.cursor/*-projects*`.

## Progressive read (agents) — bắt buộc

1. Prefer **Grep/jq-style** extract over pasting whole file into chat:
   - `defaultGroup` → `groups.<that>.projects` (ids only)
   - `projects.<id>.root` (+ `role`/`stack` if needed)
   - For FE↔BE: only `groups.<stack>.contract` `{ frontend, backend, sourceOfTruth }`
2. **Never** dump full JSON into the reply or into cloud prompts.
3. If multiple ids match and no default: **ask** which id (one line).
4. Missing map / missing checkout: say cross-repo unavailable — **do not guess** absolute paths.
5. Optional: `artifactgraph_projects` for a compact id/root table (still no full file dump).

## Output language

Vietnamese for member-facing docs; keep ids, roots, route/API keys, `data-testid` in English as in sources.
