# #call-external

When spec/prompt has `#call-external`, payment, webhook, OAuth, SMS, shipping, ERP, or third-party API.

## Backend spec

- Tag `call-external`, document `externalCalls`, endpoint `externalCallRefs`
- Secrets from config/env only

## OpenAPI

- Tag `call-external`, extension `x-external-calls`

## Code

- Service/integration client only
- No external HTTP in Action, Query, Controller, Request, Resource
- Log provider request id, status, failure reason
- Idempotency/retry documented in spec when required
