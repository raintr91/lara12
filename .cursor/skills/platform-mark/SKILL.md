---
name: platform-mark
extractBundle: platform-mark
description: /platform-mark — member marks tags/registries after grill confirm.
disable-model-invocation: true
---

# /platform-mark

Member annotation sau grill A/B/C — **không** bulk `/spec` / `/dev-grill-docs`.

**Hub policy:** `base-docs/platform/toolchain/PLATFORM-MARK.md`  
Technical marks SSOT: `product/shared/integrations/*`, `product/shared/data-model/derived-data.md`

## Registries (code)

| Layer | File | Tags |
|-------|------|------|
| UI | `registries/design.registry.json` | `#needs-component:` `#needs-ui:` `#shell:` |
| Logic | `registries/common.registry.json` | `#common:*` `#needs-common:*` |

```bash
pnpm portal:registry && pnpm platform-common:registry
# or artifactgraph_gen registryValidate / commonRegistry
```

## Workflow (ngắn)

1. Feature `ir/spec.yaml` + registries (optional `artifactgraph_analyze`)
2. Một mark / concern; member đã confirm ở grill
3. Upsert registry · planned → HANDOFF · implemented → promote path
4. `artifactgraph_remember` subject sau confirm B

## Do not

- Auto-tag không hỏi member  
- Mo* vào `common.registry` (chỉ design registry)  
- Full contract rewrite  

Handoff: `portal:gen:dry --id …` sau khi đổi marks.
