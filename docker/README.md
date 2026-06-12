# API Docker

Một **`Dockerfile`** (multi-stage) + **`docker-compose.yml`** (chỉ local dev).

## Local

```bash
# Gateway + network trước (repo workspace docker/)
make d-up-all

cd api/docker && docker compose --env-file .env up -d --build
```

- `API_STACK_PREFIX` khớp `api.stack` trong `docker/routes.json`
- Truy cập: `https://api-base.local.com` (gateway)

## CI deploy

```bash
cd api
docker build -f docker/Dockerfile --target deploy -t my-api-php:tag .
docker build -f docker/Dockerfile --target nginx-deploy -t my-api-nginx:tag .
```

Laravel `.env` inject lúc **runtime** (ECS/SSM), không bake vào image.
