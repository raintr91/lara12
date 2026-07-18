# Project Instructions

## Code lane

Skills: `/api` · `/grill-api` · `/platform-ai` · `/business-impact-review`

Generation owner: **Codegenkit** (`laravel`).
Lane bootstrap: **Platform DNA** (`--type=be --adapter=laravel`).

```bash
platform-dna init --type=be --adapter=laravel --yes
codegenkit init --type=be --adapter=laravel --yes
codegenkit api-gen:dry --adapter=laravel -- --spec <path>
codegenkit api-gen --adapter=laravel -- --spec <path>
```

SSOT harness: `.cursor/` at this repository (Platform DNA + Codegenkit + Processkit).
