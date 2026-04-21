# fpp-plugin-leviton-direct

FPP script plugin that controls Leviton smart switches directly using myLeviton.

Maintained by Holiday Pixel Zone: https://holidaypixelzone.com

## What this plugin provides

- One FPP command: `Leviton Switch Action`
- Script: `commands/leviton_action.sh`
- Backend: `commands/leviton_control.py` using `decora-wifi`
- Plugin UI Configuration page to manage credentials/device settings
- Device discovery button in UI

## Install

1. Put this plugin in a git repo and update URLs in `pluginInfo.json`.
2. Install from FPP Plugins UI using that repo's `pluginInfo.json`.
3. Configure the plugin from UI:

- Plugin -> Leviton Direct Control -> Configuration

You can also set values manually in:

`/home/fpp/media/config/plugin.fpp-plugin-leviton-direct`

Example:

```ini
LEVITON_EMAIL = you@example.com
LEVITON_PASSWORD = yourpassword
LEVITON_DEFAULT_SWITCH_ID = switch-id-here
LEVITON_LEVEL_KEY = brightness
LEVITON_ON_PAYLOAD = {"status":"on"}
LEVITON_OFF_PAYLOAD = {"status":"off"}
LEVITON_LEVEL_PAYLOAD = {"brightness":"__LEVEL__"}
LEVITON_DEVICE_NOTES = Optional notes
```

## Command usage

Script call format used by FPP command preset:

```bash
commands/leviton_action.sh <switch_id> <on|off|level|raw> [value]
commands/leviton_action.sh <on|off|level|raw> [value]
commands/leviton_action.sh --list
```

Examples:

```bash
commands/leviton_action.sh abc123 on
commands/leviton_action.sh abc123 off
commands/leviton_action.sh abc123 level 60
commands/leviton_action.sh abc123 raw '{"status":"on"}'
commands/leviton_action.sh --list
```

## Notes

- Payload fields can vary by Leviton model/firmware.
- If `status` or `brightness` does not work for your device, use `raw` action.
- For dimming, use `LEVITON_LEVEL_PAYLOAD` with `__LEVEL__` token (for example `{"power":"ON","brightness":"__LEVEL__"}`).
- You can discover switch IDs by temporarily using your bridge app (`/api/switches`) or by adding your own listing script.

## Troubleshooting

If `--list` returns `ModuleNotFoundError: No module named 'decora_wifi'`, run:

```bash
bash /home/fpp/media/plugins/fpp-plugin-leviton-direct/scripts/fpp_install.sh
```

If that still fails, install manually:

```bash
python3 -m pip install --target /home/fpp/media/plugins/fpp-plugin-leviton-direct/python_libs decora-wifi
```

If you see `Permission denied` under `python_libs`, fix ownership first:

```bash
sudo chown -R fpp:fpp /home/fpp/media/plugins/fpp-plugin-leviton-direct/python_libs
```

## License

MIT License. See `LICENSE`.
