# #cross-entity-service

Rare: one API orchestrates two **independent** internal entities synchronously.

Reject first if:

1. Eloquent relationship exists → parent Action/Query
2. Second entity is side effect → Event/Observer/Job
3. Safe to split → separate APIs

## Backend spec

- Tag `cross-entity-service`, `services`, `serviceRefs`, `alternativesConsidered`

## OpenAPI

- Tag `cross-entity-service`, extension `x-services`

## Code

- Action/Query remain API entry; may call Service for orchestration
- Service does not replace single-entity CRUD Action/Query
