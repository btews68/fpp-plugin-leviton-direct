#!/bin/bash
# Usage:
#   leviton_action.sh <switch_id> <on|off|level|raw> [value]
#   leviton_action.sh <on|off|level|raw> [value]
#   leviton_action.sh --list
#
# Configuration:
#   /home/fpp/media/config/plugin.fpp-plugin-leviton-direct
# with entries:
#   LEVITON_EMAIL = you@example.com
#   LEVITON_PASSWORD = yourpassword
#   LEVITON_LEVEL_KEY = brightness
#   LEVITON_DEFAULT_SWITCH_ID = your-switch-id
#   LEVITON_ON_PAYLOAD = {"status":"on"}
#   LEVITON_OFF_PAYLOAD = {"status":"off"}
#
# For raw action, pass JSON object as value, for example:
#   '{"status":"on"}'

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PYTHON_SCRIPT="$SCRIPT_DIR/leviton_control.py"

ARG1="${1:-}"
ARG2="${2:-}"
ARG3="${3:-}"

if [[ "$ARG1" == "--list" ]]; then
  python3 "$PYTHON_SCRIPT" --list
  exit 0
fi

case "$ARG1" in
  on|off|level|raw)
    SWITCH_ID=""
    ACTION="$ARG1"
    VALUE="$ARG2"
    ;;
  *)
    SWITCH_ID="$ARG1"
    ACTION="$ARG2"
    VALUE="$ARG3"
    ;;
esac

if [[ -z "$ACTION" ]]; then
  echo "Usage: $0 <switch_id> <on|off|level|raw> [value]"
  echo "   or: $0 <on|off|level|raw> [value]"
  echo "   or: $0 --list"
  exit 2
fi

python3 "$PYTHON_SCRIPT" "$SWITCH_ID" "$ACTION" "$VALUE"
