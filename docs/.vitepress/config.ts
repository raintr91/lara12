import { existsSync, readdirSync, readFileSync } from 'node:fs'
import { dirname, join, relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { withMermaid } from 'vitepress-plugin-mermaid'
import { defineConfig } from 'vitepress'
import type { DefaultTheme } from 'vitepress'

const docsRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const featureSidebarItems = buildFeatureSidebar()

export default withMermaid(defineConfig({
  title: 'API Docs',
  description: 'Backend API contracts, OpenAPI, API Base conventions, and team workflow',
  cleanUrls: true,
  ignoreDeadLinks: [/^https?:\/\/localhost(:\d+)?/, /^\/swagger\//],
  vite: {
    optimizeDeps: {
      include: ['dayjs', 'mermaid'],
    },
    resolve: {
      alias: {
        dayjs: 'dayjs/',
      },
    },
    build: {
      commonjsOptions: {
        include: [/dayjs/, /node_modules/],
      },
    },
  },
  themeConfig: {
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Workflow', link: '/operational/TEAM-AI-BACKEND-WORKFLOW' },
      { text: 'Contracts', link: '/api-base/generated' },
      { text: 'OpenAPI', link: '/openapi/' }
    ],
    sidebar: [
      {
        text: 'Operational',
        collapsed: false,
        items: [
          { text: 'Team AI Backend Workflow', link: '/operational/TEAM-AI-BACKEND-WORKFLOW' },
          { text: 'Unit phase — PHPUnit dev lane', link: '/operational/UNIT-PHASE-DIAGRAM' },
          { text: 'Backend API Spec Guide', link: '/operational/BACKEND_API_SPEC_GUIDE' },
          { text: 'Integration / Webhook', link: '/operational/INTEGRATION-API-SPEC' }
        ]
      },
      {
        text: 'Onboarding',
        collapsed: true,
        items: [
          { text: 'Backend Phase 3b Slides', link: '/onboarding/team-backend-phase3b-slides' }
        ]
      },
      {
        text: 'API Base',
        collapsed: true,
        items: [
          { text: 'Overview', link: '/api-base/' },
          { text: 'Conventions', link: '/api-base/CONVENTIONS' },
          { text: 'Generators', link: '/api-base/GENERATORS' },
          { text: 'Admin Route Matrix', link: '/api-base/ADMIN_ROUTE_METHOD_MATRIX' },
          { text: 'API Admin Foundation', link: '/api-base/API_ADMIN_FOUNDATION' },
          { text: 'DB Schema Plan', link: '/api-base/DB_FINAL_SCHEMA_PLAN' },
          { text: 'Generated contracts', link: '/api-base/generated' }
        ]
      },
      {
        text: 'OpenAPI',
        collapsed: true,
        items: [{ text: 'YAML + Swagger', link: '/openapi/' }]
      },
      {
        text: 'Features',
        collapsed: false,
        items: [
          { text: 'Contract index', link: '/api-base/generated' },
          ...featureSidebarItems
        ]
      }
    ],
    search: {
      provider: 'local'
    }
  }
}))

function buildFeatureSidebar() {
  const featuresRoot = join(docsRoot, 'features')
  if (!existsSync(featuresRoot)) return []

  return listFeatureGroups(featuresRoot)
}

function listFeatureGroups(dir: string): DefaultTheme.SidebarItem[] {
  return readdirSync(dir, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => {
      const entryPath = join(dir, entry.name)
      const specItems = listGeneratedMarkdown(entryPath).map((file) => ({
        text: readTitle(file),
        link: docLink(file)
      }))
      const childGroups = entry.name === 'common' ? [] : listFeatureGroups(entryPath)

      return {
        text: titleCase(entry.name),
        collapsed: true,
        items: [...specItems, ...childGroups]
      }
    })
    .filter((group) => group.items.length > 0)
    .sort((a, b) => a.text.localeCompare(b.text))
}

function listGeneratedMarkdown(dir: string): string[] {
  const generatedDir = join(dir, 'generated')
  if (!existsSync(generatedDir)) return []

  return readdirSync(generatedDir, { withFileTypes: true })
    .filter((item) => item.isFile() && item.name.endsWith('.md'))
    .map((item) => join(generatedDir, item.name))
    .sort()
}

function readTitle(file: string) {
  const firstHeading = readFileSync(file, 'utf8').match(/^#\s+(.+)$/m)?.[1]
  return firstHeading ?? relative(docsRoot, file).replace(/\.md$/, '')
}

function docLink(file: string) {
  const relativePath = relative(docsRoot, file).split('/').join('/')
  return `/${relativePath.replace(/\.md$/, '')}`
}

function titleCase(value: string) {
  return value
    .split(/[-_/]/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}
