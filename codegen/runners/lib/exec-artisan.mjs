import { spawn } from 'node:child_process'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const repoRoot = path.join(path.dirname(fileURLToPath(import.meta.url)), '../../..')
const laravelRoot = path.join(repoRoot, 'src')

/**
 * @param {string} artisanLine e.g. "m:module Admin" or full "php artisan m:module Admin"
 * @param {{ dryRun?: boolean }} options
 */
export function runArtisan(artisanLine, options = {}) {
  const line = artisanLine.replace(/^php artisan\s+/, '').trim()
  const [command, ...rest] = line.split(/\s+/)

  if (options.dryRun) {
    return Promise.resolve({ code: 0, stdout: `[dry-run] php artisan ${line}`, stderr: '' })
  }

  return new Promise((resolve, reject) => {
    const child = spawn('php', ['artisan', command, ...rest], {
      cwd: laravelRoot,
      stdio: ['ignore', 'pipe', 'pipe'],
      env: process.env
    })

    let stdout = ''
    let stderr = ''
    child.stdout.on('data', (d) => { stdout += d })
    child.stderr.on('data', (d) => { stderr += d })
    child.on('error', reject)
    child.on('close', (code) => resolve({ code: code ?? 1, stdout, stderr }))
  })
}

/**
 * @param {{ artisan: string, id: string }[]} commands
 * @param {{ dryRun?: boolean, stopOnError?: boolean }} options
 */
export async function runCommandPlan(commands, options = {}) {
  const results = []

  for (const item of commands) {
    const result = await runArtisan(item.artisan, { dryRun: options.dryRun })
    results.push({ ...item, ...result })
    if (result.code !== 0 && options.stopOnError !== false) {
      break
    }
  }

  return results
}
