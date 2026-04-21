#!/usr/bin/env python3
import json
import os
import sys
from pathlib import Path

PLUGIN_NAME = "fpp-plugin-leviton-direct"
PLUGIN_CONFIG = Path("/home/fpp/media/config") / f"plugin.{PLUGIN_NAME}"
PYTHON_LIB_DIR = Path(__file__).resolve().parents[1] / "python_libs"

if PYTHON_LIB_DIR.exists():
    sys.path.insert(0, str(PYTHON_LIB_DIR))

try:
    from decora_wifi import DecoraWiFiSession, ApiCallFailedError  # type: ignore
    from decora_wifi.models.iot_switch import IotSwitch  # type: ignore
except ModuleNotFoundError:
    print(
        "Missing Python dependency 'decora_wifi'. "
        "Run the plugin install script or install manually: "
        "python3 -m pip install --target /home/fpp/media/plugins/fpp-plugin-leviton-direct/python_libs decora-wifi"
    )
    raise SystemExit(5)


def read_plugin_config(path: Path) -> dict:
    cfg = {}
    if not path.exists():
        return cfg
    for line in path.read_text(encoding="utf-8", errors="ignore").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        cfg[key.strip()] = value.strip().strip('"').strip("'")
    return cfg


def die(msg: str, code: int = 1) -> None:
    print(msg)
    raise SystemExit(code)


def parse_json_setting(cfg: dict, key: str, default: dict) -> dict:
    raw = cfg.get(key, "").strip()
    if not raw:
        return default
    try:
        parsed = json.loads(raw)
    except json.JSONDecodeError as exc:
        die(f"Invalid JSON in {key}: {exc}", 4)
    if not isinstance(parsed, dict):
        die(f"{key} must be a JSON object", 4)
    return parsed


def list_switches(session: DecoraWiFiSession) -> int:
    diagnostics = []

    # Preferred path in decora-wifi, works on many accounts.
    try:
        devices = IotSwitch.find(session)
        diagnostics.append("IotSwitch.find() success")
    except Exception as exc:
        diagnostics.append(f"IotSwitch.find() failed: {exc}")
        devices = []

    # Fallback path for accounts where /IotSwitches endpoint is unauthorized.
    if not devices:
        try:
            discovered = _discover_switches_fallback(session, diagnostics)
            devices = [_RawSwitch(d) for d in discovered]
        except Exception as exc:
            diagnostics.append(f"Fallback discovery failed: {exc}")
            print(
                json.dumps(
                    {
                        "ok": False,
                        "error": "Unable to discover switches.",
                        "details": diagnostics,
                    },
                    indent=2,
                )
            )
            return 1

    result = []
    for device in devices:
        data = getattr(device, "data", {}) or {}
        result.append(
            {
                "id": data.get("id", getattr(device, "_id", "")),
                "name": data.get("name") or data.get("displayName") or data.get("serialNumber") or "",
                "roomId": data.get("residentialRoomId"),
                "residenceId": data.get("residenceId"),
                "raw": data,
            }
        )

    print(json.dumps({"ok": True, "count": len(result), "devices": result, "details": diagnostics}, indent=2))
    return 0


class _RawSwitch:
    def __init__(self, data: dict):
        self.data = data
        self._id = data.get("id", "")


def _api_get(session: DecoraWiFiSession, path: str):
    return session.call_api(path, {}, "get")


def _discover_switches_fallback(session: DecoraWiFiSession, diagnostics: list) -> list:
    switches = []

    # Path A: logged-in user -> residential permissions -> residence -> iot switches
    try:
        if getattr(session, "user", None) is not None and hasattr(session.user, "get_residential_permissions"):
            perms = session.user.get_residential_permissions() or []
            diagnostics.append(f"user.get_residential_permissions() returned {len(perms)} permission(s)")
            for perm in perms:
                try:
                    residence = perm.get_residence()
                    residence_id = getattr(residence, "_id", None) or (residence.data or {}).get("id")
                    if not residence_id:
                        continue
                    found = residence.get_iot_switches() or []
                    diagnostics.append(
                        f"permission->residence {residence_id} get_iot_switches() returned {len(found)} switch(es)"
                    )
                    for item in found:
                        switches.append(getattr(item, "data", {}) or {})
                except Exception as exc:
                    diagnostics.append(f"permission-based residence discovery failed: {exc}")
    except Exception as exc:
        diagnostics.append(f"user.get_residential_permissions() failed: {exc}")

    # Try ResidentialAccounts -> residences -> iotSwitches
    accounts = []
    try:
        accounts = _api_get(session, "/ResidentialAccounts") or []
        diagnostics.append(f"/ResidentialAccounts returned {len(accounts)} account(s)")
    except Exception as exc:
        diagnostics.append(f"/ResidentialAccounts failed: {exc}")

    for account in accounts:
        account_id = account.get("id")
        if not account_id:
            continue
        residences = _api_get(session, f"/ResidentialAccounts/{account_id}/residences") or []
        diagnostics.append(
            f"/ResidentialAccounts/{account_id}/residences returned {len(residences)} residence(s)"
        )
        for residence in residences:
            residence_id = residence.get("id")
            if not residence_id:
                continue
            try:
                found = _api_get(session, f"/Residences/{residence_id}/iotSwitches") or []
                diagnostics.append(
                    f"/Residences/{residence_id}/iotSwitches returned {len(found)} switch(es)"
                )
                switches.extend(found)
            except Exception as exc:
                diagnostics.append(f"/Residences/{residence_id}/iotSwitches failed: {exc}")

    # Last-chance direct residence call.
    if not switches:
        try:
            residences = _api_get(session, "/Residences") or []
            diagnostics.append(f"/Residences returned {len(residences)} residence(s)")
            for residence in residences:
                residence_id = residence.get("id")
                if not residence_id:
                    continue
                found = _api_get(session, f"/Residences/{residence_id}/iotSwitches") or []
                diagnostics.append(
                    f"/Residences/{residence_id}/iotSwitches returned {len(found)} switch(es)"
                )
                switches.extend(found)
        except Exception as exc:
            diagnostics.append(f"/Residences fallback failed: {exc}")

    # De-duplicate by id.
    seen = set()
    deduped = []
    for sw in switches:
        sid = sw.get("id")
        if not sid or sid in seen:
            continue
        seen.add(sid)
        deduped.append(sw)

    if not deduped:
        raise ApiCallFailedError("No switches discovered via fallback endpoints")

    return deduped


def main() -> int:
    cfg = read_plugin_config(PLUGIN_CONFIG)
    email = cfg.get("LEVITON_EMAIL", "").strip()
    password = cfg.get("LEVITON_PASSWORD", "").strip()
    default_switch_id = cfg.get("LEVITON_DEFAULT_SWITCH_ID", "").strip()
    level_key = cfg.get("LEVITON_LEVEL_KEY", "brightness").strip() or "brightness"
    on_payload = parse_json_setting(cfg, "LEVITON_ON_PAYLOAD", {"status": "on"})
    off_payload = parse_json_setting(cfg, "LEVITON_OFF_PAYLOAD", {"status": "off"})

    if not email or not password:
        die(
            f"Missing LEVITON_EMAIL/LEVITON_PASSWORD in {PLUGIN_CONFIG}. "
            "Set plugin config values first.",
            3,
        )

    session = DecoraWiFiSession()
    session.login(email, password)

    if len(sys.argv) >= 2 and sys.argv[1] == "--list":
        return list_switches(session)

    if len(sys.argv) < 3:
        die("Usage: leviton_control.py <switch_id> <on|off|level|raw> [value]", 2)

    switch_id = sys.argv[1].strip() or default_switch_id
    action = sys.argv[2].strip().lower()
    value = sys.argv[3] if len(sys.argv) > 3 else ""

    if not switch_id:
        die("No switch_id supplied and LEVITON_DEFAULT_SWITCH_ID is not set", 2)

    device = IotSwitch(session, switch_id)
    device.refresh()

    if action == "on":
        payload = on_payload
    elif action == "off":
        payload = off_payload
    elif action == "level":
        try:
            level = int(value)
        except ValueError:
            die("For action=level, value must be an integer 0-100", 4)
        level = max(0, min(100, level))
        payload = {level_key: level}
    elif action == "raw":
        if not value:
            die("For action=raw, value must be a JSON object", 4)
        try:
            payload = json.loads(value)
        except json.JSONDecodeError as exc:
            die(f"Invalid JSON payload: {exc}", 4)
        if not isinstance(payload, dict):
            die("Raw payload must be a JSON object", 4)
    else:
        die("Action must be one of: on, off, level, raw", 2)

    device.update_attributes(payload)
    device.refresh()

    print(json.dumps({"ok": True, "switchId": switch_id, "action": action, "payload": payload, "result": device.data}, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
