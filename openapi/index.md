# OpenAPI YAML + Swagger

Backend API Base dùng OpenAPI YAML làm source — không decorator/annotation trong Laravel.

## Layout (repo này)

```text
openapi/
  base.yaml              # health fragment (hand-edited)
  api.yaml               # entry for Redocly preview/bundle
public/
  openapi/openapi.yaml   # bundled output
  swagger/               # optional static Swagger UI assets
```

## Commands

Chạy từ **repo root** (`api/`):

```bash
pnpm openapi:bundle
pnpm openapi:preview   # port 8081
```

## Contract rule

Implementation Laravel đổi contract → cập nhật YAML trong cùng PR. Không cập nhật Swagger bằng decorator trong Controller.

## Service tags

Một số endpoint cần metadata ngoài OpenAPI chuẩn:

### `#call-external`

API gọi/nhận dữ liệu từ hệ thống bên thứ ba (`x-external-calls`).

### `#cross-entity-service`

Orchestration 2 entity nội bộ không tách được bằng relationship/event/split API (`x-services`).
