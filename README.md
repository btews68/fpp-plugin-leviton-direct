# fpp-plugin-leviton-direct

FPP script plugin that controls Leviton smart switches directly using myLeviton.

Created and maintained by Bill Tews at Holiday Pixel Zone: https://holidaypixelzone.com

## What this plugin provides

- One FPP command: `Leviton Switch Action`
- Script: `commands/leviton_action.sh`
- Backend: `commands/leviton_control.py` using `decora-wifi`
- Plugin UI Configuration page to manage credentials/device settings
- Device discovery table with model detection and profile auto-mapping
- Friendly-name alias mapping (name -> device ID)
- Playlist helper scripts: `leviton_on.sh`, `leviton_off.sh`, `leviton_dim.sh`

## Install

1. Install from FPP Plugins UI using this repo's `pluginInfo.json`.
2. Configure the plugin from UI:

- Plugin -> Leviton Direct Control -> Configuration

3. Click `Discover Devices` and optionally save Friendly Name aliases.

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

You can also target by alias or discovered device name/model when cached:

```bash
commands/leviton_action.sh Dining on
commands/leviton_action.sh "Tesla Sign" off
```

## Playlist helper scripts

These are linked into `/home/fpp/media/scripts` by install, so they appear in FPP Script dropdowns.

```bash
leviton_on.sh [alias_or_id]
leviton_off.sh [alias_or_id]
leviton_dim.sh <0-100> [alias_or_id]
```

## Notes

- Payload fields can vary by Leviton model/firmware.
- If `status` or `brightness` does not work for your device, use `raw` action.
- For dimming, use `LEVITON_LEVEL_PAYLOAD` with `__LEVEL__` token (for example `{"power":"ON","brightness":"__LEVEL__"}`).
- Device profile dropdown includes built-in profiles and discovered-model auto profiles.

## Troubleshooting

If `--list` returns `ModuleNotFoundError: No module named 'decora_wifi'`, run:

```bash
bash /home/fpp/media/plugins/fpp-plugin-leviton-direct/scripts/fpp_install.sh
```

If that still fails, install manually:

```bash
python3 -m pip install --target /home/fpp/media/plugins/fpp-plugin-leviton-direct/python_libs decora-wifi
```

If you update by `git pull` (instead of reinstalling from Plugin Manager), run install script again so script links and permissions are refreshed:

```bash
bash /home/fpp/media/plugins/fpp-plugin-leviton-direct/scripts/fpp_install.sh
```

If you see `Permission denied` under `python_libs`, fix ownership first:

```bash
sudo chown -R fpp:fpp /home/fpp/media/plugins/fpp-plugin-leviton-direct/python_libs
```

## License

MIT License. See `LICENSE`.
