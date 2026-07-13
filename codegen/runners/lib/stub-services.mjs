import { mkdir, writeFile } from 'node:fs/promises'
import path from 'node:path'

/**
 * @param {string} fqcn
 */
function splitClass(fqcn) {
  const parts = fqcn.replace(/^Modules\\/, '').split('\\')
  const short = parts.pop()
  const namespace = `Modules\\${parts.join('\\')}`
  const filePath = `src/Modules/${parts.join('/')}/${short}.php`
  return { namespace, short, filePath }
}

/**
 * @param {object} spec
 * @param {string} featureDir docs/features/{slug}
 * @param {{ dryRun?: boolean }} options
 */
export async function writeServiceStubs(spec, featureDir, options = {}) {
  const written = []
  const module = spec.codegen?.module ?? spec.modules?.[0]?.name
  const repoRoot = path.resolve(featureDir, '../../..')
  const slug = spec.feature?.id ?? 'feature'

  const stubs = []

  for (const call of spec.externalCalls ?? []) {
    const fqcn = call.service ?? `Modules\\${module}\\Services\\External\\${pascal(call.id)}Service`
    stubs.push({ fqcn, kind: 'external', ref: call.id })
  }

  for (const svc of spec.services ?? []) {
    const fqcn = svc.class ?? `Modules\\${module}\\Services\\${pascal(svc.id)}Service`
    stubs.push({ fqcn, kind: 'cross-entity', ref: svc.id })
  }

  for (const stub of stubs) {
    const { namespace, short, filePath } = splitClass(stub.fqcn)
    const target = path.join(repoRoot, filePath)

    const body = `<?php

namespace ${namespace};

/**
 * ${stub.kind} service — implement per HANDOFF (#manual-service:${stub.ref}).
 *
 * @see docs/features/${slug}/generated/HANDOFF.md
 */
class ${short}
{
    public function __invoke(mixed ...$args): mixed
    {
        throw new \\RuntimeException('TODO: implement ${stub.ref}');
    }
}
`

    if (options.dryRun) {
      written.push(`[dry-run] ${filePath}`)
      continue
    }

    await mkdir(path.dirname(target), { recursive: true })
    try {
      await writeFile(target, body, { flag: 'wx' })
      written.push(filePath)
    } catch (err) {
      if (err.code === 'EEXIST') {
        written.push(`${filePath} (exists, skipped)`)
      } else {
        throw err
      }
    }
  }

  return written
}

/**
 * @param {string} value
 */
function pascal(value) {
  return String(value)
    .replace(/[^a-zA-Z0-9]+/g, ' ')
    .trim()
    .split(/\s+/)
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join('')
}
