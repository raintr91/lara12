# OpenAPI YAML + Swagger

Backend API Base dùng OpenAPI YAML làm source, không dùng decorator/annotation trong Laravel.

## Vì Sao YAML

- Contract review được trước khi code.
- FE có thể dùng mock data và schemas sớm.
- Diff rõ hơn annotation rải trong controller/resource.
- Không buộc Laravel code phải chứa OpenAPI decorator.

## File Chính

Root OpenAPI để preview local:

```text
docs/openapi/api.yaml
```

Feature-level OpenAPI từ Step 1:

```text
docs/features/{slug}/02-openapi.yaml
```

Khi cần publish hoặc preview chung, bundle/merge về:

```text
docs/public/openapi/openapi.yaml
```

## Commands

```bash
pnpm openapi:lint
pnpm openapi:bundle
pnpm openapi:preview
pnpm swagger:build
pnpm swagger:dev
```

## Redocly

Redocly dùng để lint, bundle và preview OpenAPI YAML:

```bash
pnpm openapi:lint
pnpm openapi:preview
```

`openapi:preview` mở docs API ở port `8081`.

## Swagger UI Static

Swagger UI build local từ `swagger-ui-dist`:

```bash
pnpm openapi:bundle
pnpm swagger:build
```

Output:

```text
docs/public/swagger/
├── index.html
├── swagger-ui.css
├── swagger-ui-bundle.js
└── swagger-ui-standalone-preset.js
```

Khi chạy VitePress:

```bash
pnpm docs:dev
```

Mở:

```text
/swagger/
```

## Contract Rule

Nếu implementation Laravel làm contract đổi, phải cập nhật YAML trước hoặc cùng PR:

```text
01-backend-spec.yaml
02-openapi.yaml
03-mock-data.yaml
generated/backend-spec.md
```

Không cập nhật Swagger bằng decorator trong Controller.
