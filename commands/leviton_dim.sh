#!/bin/bash
# Usage:
#   leviton_dim.sh <level_0_to_100> [switch_id_or_alias]
#
# Examples:
#   leviton_dim.sh 40
#   leviton_dim.sh 25 Dining

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ACTION_SCRIPT="$SCRIPT_DIR/leviton_action.sh"

LEVEL="${1:-}"
TARGET="${2:-}"

if [[ -z "$LEVEL" ]]; then
  echo "Usage: $0 <level_0_to_100> [switch_id_or_alias]"
  exit 2
fi

if ! [[ "$LEVEL" =~ ^[0-9]+$ ]]; then
  echo "Level must be an integer 0-100"
  exit 2
fi

if (( LEVEL < 0 || LEVEL > 100 )); then
  echo "Level must be between 0 and 100"
  exit 2
fi

if [[ -n "$TARGET" ]]; then
  exec "$ACTION_SCRIPT" "$TARGET" level "$LEVEL"
fi

exec "$ACTION_SCRIPT" level "$LEVEL"
