# Legacy Blade/HTML → API + SPA

Legacy Laravel Blade, server-rendered HTML, or old web UI is **evidence only**, not the new API contract.

- Do not recreate page-init APIs from legacy GET render routes (`GET /auth/login`, `GET /{entity}/create`).
- Login/create forms init on the frontend with schema/defaults; add lookup/options/permission APIs only when server data is required.
- Detail/update/edit/copy/duplicate share one detail API (e.g. `GET /{entity}/{id}`) for old data.
- Split auxiliary data into explicit endpoints (`form-options`, lookup/config, permissions), not page-render routes.
- Spec and prototype mocks must follow the new API contract, not legacy render routes.
- Keep SPA UI routes separate from backend API routes; do not flatten server-render flows into one giant payload.
