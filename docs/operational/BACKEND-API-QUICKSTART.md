# Backend API — quick reference (Laravel)

> **R2/R3:** Product Code + architecture → [`base-docs`](https://github.com/raintr91/base_docs) · E2E plans → [`base-tests`](https://github.com/raintr91/base_test) · gen: `pnpm portal:gen --id …` / `pnpm testcase:gen --id …` · [Hub split](https://github.com/raintr91/base_test/blob/main/docs/HUBS.md) / [Docs hub](https://github.com/raintr91/base_docs) / [Tests hub](https://github.com/raintr91/base_test/blob/main/docs/TESTS-HUB.md)

Laravel modular API — repository-local AI harness (code lane).
Contract SSOT (api spec · `02-openapi.yaml` · mock) → [`base-docs`](https://github.com/raintr91/base_docs); repo này chỉ giữ code thật. Xem `TODO-OPENAPI.md`.

---

## Repo layout

```text
api/
├── src/               # Laravel app (SSOT code)
├── docs/              # operational guides
└── .cursor/           # code-lane skills (gitignored — from toolkit init)
```

Codegen/unitgen/registries do **Codegenkit** sync khi `init` (gitignored):
`.codegenkit/` · `registries/` · `src/.codegenkit/` (PHP unitgen).

---

## Local dev

### 1. Laravel app (`src/`)

```bash
cd src
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Docker

Container stack: [docker/README.md](../../docker/README.md)

### 3. Codegen / unitgen

Do Codegenkit sở hữu — xem [BACKEND-CODEGEN.md](./BACKEND-CODEGEN.md).

---

## AI harness (code lane)

Skills: `.cursor/skills/` · rules/extracts: `.cursor/`.
SSOT harness = `.cursor/` at this repository.
