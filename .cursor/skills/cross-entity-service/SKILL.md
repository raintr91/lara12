---
name: cross-entity-service
description: >-
  Marks rare backend APIs that must orchestrate two independent internal
  entities in one use-case. Use when the spec mentions #cross-entity-service,
  cross-aggregate orchestration, or one API mutating multiple unrelated entities.
disable-model-invocation: true
---

# #cross-entity-service

Use only when one API must orchestrate two independent internal entities.

## Before Using

Reject this pattern first if possible:

1. If entities have Eloquent relationship, use parent Action/Query and Laravel relationship APIs.
2. If the second entity is a side effect not needed in the FE response, prefer Event/Observer/Job.
3. If the operation can be split safely, use separate APIs.
4. Use Service only when synchronous orchestration is simpler, justified, and reviewable.

## Spec

Add `cross-entity-service` tag and document the service decision:

```yaml
tags:
  - cross-entity-service

services:
  - id: chain.activateWithDefaultStore
    class: Modules\Chain\Services\ActivateChainWithDefaultStoreService
    reason: Chain and Store are independent aggregates but must be activated in one operation.
    alternativesConsidered:
      - Eloquent relationship: not applicable
      - Event/Observer/Job: rejected because FE needs synchronous result
      - Split APIs: rejected because operation must be atomic
    entities:
      - Chain
      - Store
```

Endpoint references:

```yaml
api:
  endpoints:
    - id: chain.activate
      tags:
        - cross-entity-service
      action: ChainAction.activate
      serviceRefs:
        - chain.activateWithDefaultStore
```

## OpenAPI

Add tag `cross-entity-service` and vendor extension:

```yaml
x-services:
  - class: ActivateChainWithDefaultStoreService
    entities:
      - Chain
      - Store
    reason: Synchronous cross-aggregate orchestration.
```

## Implementation

- Keep Action/Query for API entry behavior.
- Action/Query may call Service for cross-entity orchestration.
- Service must not replace normal single-entity CRUD Action/Query.
- Service should expose one use-case method and stay small.
- Record why relationship/event/split API was not used.
