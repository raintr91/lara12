# Feature artifact — hub

SSOT: `docs/features/yaml/**/{id}.bundle.yaml`

```text
legacy source (external)
       ↓ legacy-spec
_legacy.trace.yaml + bundle.legacy
       ↓ pnpm spec:split
ir/{spec,legacy,design}.yaml
       ↓ dev-grill writes ir/spec (codegen)
portal:gen --spec .../ir/spec.yaml
       ↓ pnpm docs:render
docs/features/md/**/{id}.md
```

## One truth per concern

| Concern | File |
|---------|------|
| Legacy facts | trace + `ir/legacy.yaml` |
| Portal UI intent | `ir/design.yaml` |
| Codegen contract | `ir/spec.yaml` |
| BA prose | `bundle.review` → md |

Policy: `.cursor/rules/platform-ai.mdc` — feature docs = chuột bạch.

## Quy tắc chung (một concern = một nguồn)

| Concern | Nguồn | Không duplicate |
|---------|--------|-----------------|
| Legacy fact | `_legacy.trace.yaml` + `bundle.legacy` / `ir/legacy.yaml` | Không copy controller vào prose |
| Portal UI | `bundle.design` / `ir/design.yaml` | Không manifest riêng — chỉ design+zones |
| Codegen | `ir/spec.yaml` | Không đọc bundle/legacy trong portal:gen |
| BA prose | `bundle.review` → `md/` | Không prose trong `ir/*` |
| Agent policy | extract bundle theo command | Không load all extracts mọi phase |

**Cấm thêm:** `analysis.yaml`, `manifest.yaml`, `generation.yaml`, tag `#evidence:`.

**knowledge.level:** `observed` | `normalized` | `canonical` — không reuse `status: draft`.

**Pipeline:** `legacy-spec` | `/spec` → `bqa-grill` → `dev-grill` → [`grill-with-docs`] → `prototype`.

Flow hub: `docs/operational/FEATURE-ARTIFACT-FLOWS.md` · `docs/operational/FEATURE-ARTIFACT-COMMANDS.md`
