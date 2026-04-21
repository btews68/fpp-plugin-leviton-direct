#!/bin/bash
# Usage:
#   leviton_on.sh [switch_id_or_alias]
#
# If no switch/alias is provided, LEVITON_DEFAULT_SWITCH_ID is used.

set -euo pipefail

SOURCE="${BASH_SOURCE[0]}"
while [[ -h "$SOURCE" ]]; do
  DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
  SOURCE="$(readlink "$SOURCE")"
  [[ "$SOURCE" != /* ]] && SOURCE="$DIR/$SOURCE"
done
SCRIPT_DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
ACTION_SCRIPT="$SCRIPT_DIR/leviton_action.sh"

TARGET="${*:-}"
if [[ "$TARGET" =~ ^\".*\"$ || "$TARGET" =~ ^\'.*\'$ ]]; then
  TARGET="${TARGET:1:-1}"
fi

if [[ -n "$TARGET" ]]; then
  exec "$ACTION_SCRIPT" "$TARGET" on
fi

exec "$ACTION_SCRIPT" on
