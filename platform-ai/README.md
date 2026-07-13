# platform-ai — SSOT cho Cursor (+ Kilo khi có `.kilo/`)

Skill, rule, extract **chỉ sửa trong folder này**. Sau đó:

```bash
./scripts/platform-ai-link
```

Script **mirror (copy)** sang `.cursor/` (và `.kilo/` nếu repo có folder `.kilo/`).

## Cấu trúc

```text
platform-ai/
  skills/
  rules/
  extracts/        # extract-registry.json paths dùng prefix platform-ai/extracts/
```

## Thêm skill

1. `platform-ai/skills/{name}/SKILL.md`
2. `./scripts/platform-ai-link`
3. Cập nhật `platform-ai/extracts/extract-registry.json` (nếu dùng extractBundle)
4. Cập nhật router rule trong `platform-ai/rules/` (nếu có)

## Git

Commit **`platform-ai/`** only. Mirror `.cursor/` + `.kilo/` gitignored — sau clone chạy `./scripts/platform-ai-link`.

Lần đầu migrate từ layout cũ: `./scripts/platform-ai-migrate-to-ssot` (một lần).
