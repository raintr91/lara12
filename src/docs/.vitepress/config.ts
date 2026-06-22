import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'API Docs',
  description: 'Backend API workflow, contracts, OpenAPI YAML, and Laravel API Base conventions',
  cleanUrls: true,
  themeConfig: {
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Workflow', link: '/TEAM-AI-BACKEND-WORKFLOW' },
      { text: 'OpenAPI', link: '/OPENAPI-YAML-SWAGGER' }
    ],
    sidebar: [
      {
        text: 'Team Workflow',
        items: [
          { text: 'Backend Workflow', link: '/TEAM-AI-BACKEND-WORKFLOW' },
          { text: 'Backend Spec Guide', link: '/BACKEND_API_SPEC_GUIDE' },
          { text: 'OpenAPI YAML + Swagger', link: '/OPENAPI-YAML-SWAGGER' }
        ]
      },
      {
        text: 'API Base',
        items: [
          { text: 'Conventions', link: '/CONVENTIONS' },
          { text: 'Generators', link: '/GENERATORS' },
          { text: 'Admin Route Matrix', link: '/ADMIN_ROUTE_METHOD_MATRIX' },
          { text: 'API Admin Foundation', link: '/API_ADMIN_FOUNDATION' },
          { text: 'DB Final Schema Plan', link: '/DB_FINAL_SCHEMA_PLAN' }
        ]
      },
      {
        text: 'Generated Contracts',
        items: [
          { text: 'Feature Index', link: '/generated' },
          { text: 'Backend API Template', link: '/features/_template/generated/backend-spec' }
        ]
      },
      {
        text: 'Presentations',
        items: [
          { text: 'Backend Phase 3b Slides', link: '/presentations/team-backend-phase3b-slides' }
        ]
      }
    ],
    search: {
      provider: 'local'
    }
  }
})
