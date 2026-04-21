#!/bin/bash
# Usage:
#   leviton_dim.sh <level_0_to_100> [switch_id_or_alias]
#
# Examples:
#   leviton_dim.sh 40
#   leviton_dim.sh 25 Dining

set -euo pipefail

SOURCE="${BASH_SOURCE[0]}"
while [[ -h "$SOURCE" ]]; do
  DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
  SOURCE="$(readlink "$SOURCE")"
  [[ "$SOURCE" != /* ]] && SOURCE="$DIR/$SOURCE"
done
SCRIPT_DIR="$(cd -P "$(dirname "$SOURCE")" && pwd)"
ACTION_SCRIPT="$SCRIPT_DIR/leviton_action.sh"

LEVEL="${1:-}"
TARGET=""

# Some UIs may pass args as one string: "40 Tesla Sign"
if [[ $# -eq 1 && "$LEVEL" == *" "* ]]; then
  TARGET="${LEVEL#* }"
  LEVEL="${LEVEL%% *}"
else
  TARGET="${*:2}"
fi

if [[ "$TARGET" =~ ^\".*\"$ || "$TARGET" =~ ^\'.*\'$ ]]; then
  TARGET="${TARGET:1:-1}"
fi

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
