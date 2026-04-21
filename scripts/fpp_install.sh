#!/bin/bash

# fpp-plugin-leviton-direct install script

. ${FPPDIR}/scripts/common

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PY_LIB_DIR="$PLUGIN_DIR/python_libs"

mkdir -p "$PY_LIB_DIR"
python3 -m pip install --upgrade --target "$PY_LIB_DIR" decora-wifi >/dev/null 2>&1 || true

chmod +x "$PLUGIN_DIR/commands/leviton_action.sh"
chmod +x "$PLUGIN_DIR/commands/leviton_control.py"

echo "Installed fpp-plugin-leviton-direct"
