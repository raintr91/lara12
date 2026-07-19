# Backend codegen — Laravel (`api`)

> Product Code + architecture → [`base-docs`](https://github.com/raintr91/base_docs) · test plans → [`base-tests`](https://github.com/raintr91/base_test) · [tests-hub handbook](https://github.com/raintr91/base_test/blob/main/docs/TESTS-HUB.md)

Codegen owner: **Codegenkit**, profile `be`, adapter `laravel`. Generated code stays in this repo; product design remains in `base-docs`.

## Bootstrap

```bash
codegenkit init --type=be --adapter=laravel --yes
```

## Feature flow

```text
base-docs .../ir/spec.yaml
  → /api + /grill-api
  → codegenkit api-gen:dry --adapter=laravel
  → codegenkit api-gen --adapter=laravel
  → codegenkit api-unit-gen:dry --adapter=laravel
  → PHPUnit / OpenAPI verification
```

## Commands

```bash
codegenkit api-registry --adapter=laravel
codegenkit api-gen:dry --adapter=laravel -- --spec /path/to/ir/spec.yaml
codegenkit api-gen --adapter=laravel -- --spec /path/to/ir/spec.yaml
codegenkit api-unit-registry --adapter=laravel
codegenkit api-unit-gen:dry --adapter=laravel -- --spec /path/to/ir/spec.yaml
codegenkit api-unit-gen --adapter=laravel -- --spec /path/to/ir/spec.yaml
```

Codegen/unitgen do **Codegenkit** sở hữu. Node runner ở root repo đã gỡ; bản PHP
sẽ được `codegenkit init` sync vào `src/.codegenkit/` (chạy bằng `php`, không cần
Node ở root). Xem `codegenkit/TODO-UNITGEN-LARAVEL.md`.

> OpenAPI (`02-openapi.yaml`) SSOT nằm cạnh `01-backend-spec.yaml` trên base-docs; unitgen đọc trực tiếp từ `--spec`. Xem `TODO-OPENAPI.md`.

## Outputs

| Pipeline | Output |
|----------|--------|
| API generation | Laravel modules/models/controllers under `src/` plus `generated/codegen.manifest.json` and `HANDOFF.md` |
| Unit generation | PHPUnit files plus `generated/unit.manifest.json` and `UNIT-HANDOFF.md` |

## References

- [Laravel API quickstart](./BACKEND-API-QUICKSTART.md)
- [Backend API spec guide](./BACKEND_API_SPEC_GUIDE.md)
- [Codegenkit layout contract](https://github.com/raintr91/codegenkit/blob/main/docs/CODEGEN-LAYOUT.md)
