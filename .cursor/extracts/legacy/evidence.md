# Legacy evidence pointers

Use in `_legacy.trace.yaml`, `bundle.legacy`, `ir/legacy.yaml` — not in `tags:`.

## Shape

```yaml
evidence:
  - file: app/Http/Controllers/Admin/HotelController.php
    symbol: index
    lines: 45-62

refs:
  legacy://hotel/index:
    file: app/Http/Controllers/Admin/HotelController.php
    symbol: index
    summary:
      - parse query
      - paginate
      - render view
```

## Rules

- Pointer only — no multi-line code snippets in YAML.
- Open legacy repo only when `confidence < 0.85` or `#legacy-recheck`.
- Grill loads IR — does not reconstruct from source.
