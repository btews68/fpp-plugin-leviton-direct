#!/bin/bash

# fpp-plugin-leviton-direct install script

. ${FPPDIR}/scripts/common

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PY_LIB_DIR="$PLUGIN_DIR/python_libs"

mkdir -p "$PY_LIB_DIR"

echo "Installing Python dependency: decora-wifi"

if python3 -m pip --version >/dev/null 2>&1; then
	PIP_CMD=(python3 -m pip)
elif command -v pip3 >/dev/null 2>&1; then
	PIP_CMD=(pip3)
else
	echo "ERROR: pip is not available. Install python3-pip on FPP and rerun plugin install."
	exit 1
fi

if ! "${PIP_CMD[@]}" install --upgrade --target "$PY_LIB_DIR" decora-wifi; then
	# Some systems require this flag due to externally-managed Python policy.
	if ! "${PIP_CMD[@]}" install --break-system-packages --upgrade --target "$PY_LIB_DIR" decora-wifi; then
		echo "ERROR: Failed to install decora-wifi into $PY_LIB_DIR"
		exit 1
	fi
fi

if ! python3 -c "import sys; sys.path.insert(0, '$PY_LIB_DIR'); import decora_wifi" >/dev/null 2>&1; then
	echo "ERROR: decora_wifi import test failed after install."
	exit 1
fi

chmod +x "$PLUGIN_DIR/commands/leviton_action.sh"
chmod +x "$PLUGIN_DIR/commands/leviton_control.py"

echo "Installed fpp-plugin-leviton-direct"
