#!/bin/bash

# fpp-plugin-leviton-direct uninstall script

FPP_MEDIA_DIR="${MEDIADIR:-${MEDIA_DIR:-/home/fpp/media}}"
FPP_SCRIPTS_DIR="$FPP_MEDIA_DIR/scripts"

rm -f "$FPP_SCRIPTS_DIR/leviton_on.sh"
rm -f "$FPP_SCRIPTS_DIR/leviton_off.sh"
rm -f "$FPP_SCRIPTS_DIR/leviton_dim.sh"

echo "Uninstalling fpp-plugin-leviton-direct"
