# Flow trace — step template

Output: `docs/flow-trace/{flow-slug}.md` · hub: `docs/flow-trace/index.md`

## Sections only

`# title` · `## Steps` · `## Diagram` (one mermaid) · `## Gaps` (optional)

## Step types

```
page [{system}] {route}
api [{system}] {METHOD} {path}
call [{from} → {to}] {METHOD} {path} — note
persist [{system}] {table/model}
job [{system}] dispatch({ClassName})
mail [{system}] template: {relative/path}
```

Systems: `portal` | `api` | `mairy-backend` | `mairy-fullsco` | `mairy-scenario` | `customer`

Resolve roots: `legacy/project-config.md` (progressive — needed ids only, no full JSON dump)

## Example shape (verify in code)

```markdown
# Survey notification

## Steps
1. page [mairy-fullsco] `/admin/surveys/create`
2. api [mairy-backend] `POST /api/admin/surveys`
3. call [mairy-backend → mairy-scenario] `POST /api/sync/survey`
4. job [mairy-backend] `dispatch(SendSurveyThankYouMail)`
5. mail [mairy-backend] `template: resources/views/emails/survey/thank-you.blade.php`

## Diagram
\`\`\`mermaid
sequenceDiagram
  ...
\`\`\`
```

No UI colors/layout. After publish: link in `docs/flow-trace/index.md`.
