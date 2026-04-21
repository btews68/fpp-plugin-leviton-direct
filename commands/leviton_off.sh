#!/bin/bash
# Usage:
#   leviton_off.sh [switch_id_or_alias]
#
# If no switch/alias is provided, LEVITON_DEFAULT_SWITCH_ID is used.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ACTION_SCRIPT="$SCRIPT_DIR/leviton_action.sh"

TARGET="${1:-}"

if [[ -n "$TARGET" ]]; then
  exec "$ACTION_SCRIPT" "$TARGET" off
fi

exec "$ACTION_SCRIPT" off
