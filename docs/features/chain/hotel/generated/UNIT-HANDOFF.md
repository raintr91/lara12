# UNIT HANDOFF — Chain — Hotel list (施設一覧)

Generated from `01-backend-spec.yaml` (profile: **crud-standard**, phase: **behavioral**).

Prerequisite: `pnpm api:gen` + `generated/codegen.manifest.json`.

## Commands (stub layer)

- _No artisan commands planned._

## Test files

- `src/Modules/Chain/Tests/Unit/Http/Requests/HotelSearchRequestRulesKeysBehaviorTest.php`
- `src/Modules/Chain/Tests/Unit/Http/Requests/HotelSearchRequestDefaultPerPageBehaviorTest.php`
- `src/Modules/Chain/Tests/Unit/Http/Queries/HotelQueryChainScopeBehaviorTest.php`
- `src/Modules/Chain/Tests/Unit/Http/Actions/HotelActionRelationshipsBehaviorTest.php`
- `src/Modules/Chain/Tests/Unit/Http/Resources/HotelResourceOpenApiShapeBehaviorTest.php`
- `src/Modules/Chain/Tests/Unit/Http/Resources/HotelResourceNestedRelationsBehaviorTest.php`

## Verify

```bash
cd src && php artisan test --testsuite=ModuleChain
cd src && php artisan test Modules/Chain/Tests/Unit/Http/Requests/HotelSearchRequestRulesKeysBehaviorTest.php Modules/Chain/Tests/Unit/Http/Requests/HotelSearchRequestDefaultPerPageBehaviorTest.php Modules/Chain/Tests/Unit/Http/Queries/HotelQueryChainScopeBehaviorTest.php Modules/Chain/Tests/Unit/Http/Actions/HotelActionRelationshipsBehaviorTest.php Modules/Chain/Tests/Unit/Http/Resources/HotelResourceOpenApiShapeBehaviorTest.php Modules/Chain/Tests/Unit/Http/Resources/HotelResourceNestedRelationsBehaviorTest.php
```
