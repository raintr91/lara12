#!/usr/bin/env bash
# Go vendor AI cu trong .cursor/.
# Team workflow dung .cursor/extracts/ + .cursor/skills/ — commit trong repo.
# Chay: bash scripts/remove-ai-harness-vendor.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
PROJECT_CURSOR="${PROJECT_ROOT}/.cursor"

remove_if_exists() {
  local path="$1"
  if [[ -e "${path}" ]]; then
    rm -rf "${path}"
    echo "Removed: ${path}"
  fi
}

echo "==> Workspace: ${PROJECT_ROOT}"

remove_if_exists "${PROJECT_CURSOR}/skills-vendor"
remove_if_exists "${PROJECT_CURSOR}/rules-vendor"
remove_if_exists "${PROJECT_CURSOR}/skills/vendor"

echo "Done. Use .cursor/extracts/ and team skills in .cursor/skills/."
echo "Doc: src/docs/TEAM-AI-BACKEND-WORKFLOW.md"
