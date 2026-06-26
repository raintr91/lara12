# S3 / Public Assets — Path Only

## Persist (DB, Action)

- Store **path only**, not full URL
- Strip domain if FE sends full URL before save

## Expose (Resource)

- Compose URL at response time from `config('public_assets.base_url')` or env
- DB field `logo_path` → API field `logo_url`

Env: `PUBLIC_ASSET_BASE_URL`, `APP_URL`, `AWS_URL`. Private S3: `temporaryUrl()`.

Third-party external URLs (redirects) — keep full URL; path-only does not apply.
