# API Docs

Backend docs dùng hai lớp:

- YAML là source of truth cho AI/dev: backend spec, OpenAPI contract, mock data.
- Markdown generated là bản review cho BA/QA/FE/BE trên VitePress.

## Quick Links

- [Backend Team Workflow](./TEAM-AI-BACKEND-WORKFLOW.md)
- [Backend API Spec Guide](./BACKEND_API_SPEC_GUIDE.md)
- [OpenAPI YAML + Swagger](./OPENAPI-YAML-SWAGGER.md)
- [Backend Phase 3b Slides](./presentations/team-backend-phase3b-slides.md)
- [Generated Feature Docs](./generated.md)

## Commands

```bash
pnpm docs:dev
pnpm docs:build
pnpm docs:preview
```

Swagger/OpenAPI dùng YAML, không dùng Laravel decorator:

```bash
pnpm openapi:lint
pnpm openapi:preview
pnpm swagger:dev
```
