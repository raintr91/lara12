# API Base — Laravel API Base conventions

Docs for generators, layers, and admin foundation.

## Quick links

- [Conventions](./CONVENTIONS.md)
- [Generators](./GENERATORS.md)
- [Admin route matrix](./ADMIN_ROUTE_METHOD_MATRIX.md)
- [API admin foundation](./API_ADMIN_FOUNDATION.md)
- [DB schema plan](./DB_FINAL_SCHEMA_PLAN.md)
- [Generated feature contracts](./generated.md)

## Common contracts

| Fragment | Mô tả |
|----------|--------|
| [API response envelope](/features/common/generated/common-api-response) | Success/error payload |
| [Search / list query](/features/common/generated/common-search-request) | SearchRequest params |
| [Integration spec](/features/common/generated/common-integration-spec) | Webhook/partner — alias `/api-int-spec`, `/grill-int-spec` |

Source code: `src/` · Generators: `src/make_help.md`
