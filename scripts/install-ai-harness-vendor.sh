#!/usr/bin/env bash
# Deprecated — vendor clones replaced by .cursor/extracts/ (committed in repo).
# Runs remove-ai-harness-vendor.sh and exits.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "install-ai-harness-vendor.sh is deprecated. Removing old vendor install if present..."
bash "${SCRIPT_DIR}/remove-ai-harness-vendor.sh"
echo "Use .cursor/extracts/ + .cursor/skills/ — see src/docs/TEAM-AI-BACKEND-WORKFLOW.md"
