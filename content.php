<?php
$pluginName = 'fpp-plugin-leviton-direct';
?>

<h2>Leviton Direct Configuration</h2>
<p>Set login credentials, default switch behavior, and payload mappings for your devices.</p>

<div class='container-fluid' style='max-width: 1000px; margin-left: 0;'>
  <div class='row mb-2'>
    <div class='col-md-4'><label for='levitonEmail'><b>Username / Email</b></label></div>
    <div class='col-md-8'><input id='levitonEmail' class='form-control' type='text'></div>
  </div>

  <div class='row mb-2'>
    <div class='col-md-4'><label for='levitonPassword'><b>Password</b></label></div>
    <div class='col-md-8'><input id='levitonPassword' class='form-control' type='password'></div>
  </div>

  <div class='row mb-2'>
    <div class='col-md-4'><label for='quickSelectDevice'><b>Quick Select Device</b></label></div>
    <div class='col-md-8'>
      <select id='quickSelectDevice' class='form-control'>
        <option value=''>-- Discover Devices first --</option>
      </select>
      <small class='form-text text-muted'>After discovery, select a device to auto-fill Default Switch ID and apply matching profile.</small>
    </div>
  </div>

  <div class='row mb-2'>
    <div class='col-md-4'><label for='defaultSwitch'><b>Default Switch ID</b></label></div>
    <div class='col-md-8'><input id='defaultSwitch' class='form-control' type='text' placeholder='Optional: used when no switch ID is passed to command'></div>
  </div>

  <div class='row mb-2'>
    <div class='col-md-4'><label for='levelKey'><b>Level Key</b></label></div>
    <div class='col-md-8'><input id='levelKey' class='form-control' type='text' placeholder='brightness'></div>
  </div>

  <div class='row mb-2'>
    <div class='col-md-4'><label for='deviceProfile'><b>Device Profile</b></label></div>
    <div class='col-md-8'>
      <select id='deviceProfile' class='form-control'>
        <option value='custom'>Custom (manual payloads)</option>
        <option value='default'>Default Leviton (status)</option>
        <option value='d26hd'>Leviton D26HD Dimmer (power)</option>
        <option value='dw15p'>Leviton DW15P Outlet (power)</option>
      </select>
      <small class='form-text text-muted'>Selecting a profile auto-fills Level Key and On/Off payloads.</small>
    </div>
  </div>

  <div class='row mb-2'>
    <div class='col-md-4'><label for='onPayload'><b>On Payload JSON</b></label></div>
    <div class='col-md-8'><textarea id='onPayload' class='form-control' rows='3' placeholder='{"status":"on"}'></textarea></div>
  </div>

  <div class='row mb-2'>
    <div class='col-md-4'><label for='offPayload'><b>Off Payload JSON</b></label></div>
    <div class='col-md-8'><textarea id='offPayload' class='form-control' rows='3' placeholder='{"status":"off"}'></textarea></div>
  </div>

  <div class='row mb-2'>
    <div class='col-md-4'><label for='levelPayload'><b>Level Payload JSON</b></label></div>
    <div class='col-md-8'>
      <textarea id='levelPayload' class='form-control' rows='3' placeholder='{"brightness":"__LEVEL__"}'></textarea>
      <small class='form-text text-muted'>Use <b>__LEVEL__</b> token for the numeric dim level (0-100).</small>
    </div>
  </div>

  <div class='row mb-3'>
    <div class='col-md-4'><label for='deviceNotes'><b>Device Notes</b></label></div>
    <div class='col-md-8'><textarea id='deviceNotes' class='form-control' rows='3' placeholder='Optional notes: model quirks, payload info, etc.'></textarea></div>
  </div>

  <div class='row mb-4'>
    <div class='col-md-12'>
      <button id='saveBtn' class='buttons btn-success'>Save Settings</button>
      <button id='discoverBtn' class='buttons btn-outline-primary'>Discover Devices</button>
      <button id='testOnBtn' class='buttons btn-outline-secondary'>Test ON (Default Switch)</button>
      <button id='testOffBtn' class='buttons btn-outline-secondary'>Test OFF (Default Switch)</button>
      <input id='testLevelValue' type='number' min='0' max='100' value='40' style='width: 90px; margin-left: 8px;'>
      <button id='testLevelBtn' class='buttons btn-outline-secondary'>Test LEVEL</button>
    </div>
  </div>

  <h3>Discovered Devices</h3>
  <div id='devicesOutput' style='min-height: 180px; background: #111; color: #ddd; padding: 12px; border-radius: 6px;'>No device data yet.</div>

  <h3>Status</h3>
  <pre id='statusOutput' style='min-height: 80px; background: #111; color: #ddd; padding: 12px; border-radius: 6px;'>Ready.</pre>
</div>

<script>
(function () {
  const plugin = '<?php echo $pluginName; ?>';
  let discoveredDevices = [];
  const discoveredDevicesSettingKey = 'LEVITON_DISCOVERED_DEVICES';

  const fields = {
    LEVITON_EMAIL: 'levitonEmail',
    LEVITON_PASSWORD: 'levitonPassword',
    LEVITON_DEFAULT_SWITCH_ID: 'defaultSwitch',
    LEVITON_LEVEL_KEY: 'levelKey',
    LEVITON_ON_PAYLOAD: 'onPayload',
    LEVITON_OFF_PAYLOAD: 'offPayload',
    LEVITON_LEVEL_PAYLOAD: 'levelPayload',
    LEVITON_DEVICE_NOTES: 'deviceNotes'
  };

  const profiles = {
    default: {
      levelKey: 'brightness',
      onPayload: '{"status":"on"}',
      offPayload: '{"status":"off"}',
      levelPayload: '{"brightness":"__LEVEL__"}'
    },
    d26hd: {
      levelKey: 'brightness',
      onPayload: '{"power":"ON"}',
      offPayload: '{"power":"OFF"}',
      levelPayload: '{"power":"ON","brightness":"__LEVEL__"}'
    },
    dw15p: {
      levelKey: 'brightness',
      onPayload: '{"power":"ON"}',
      offPayload: '{"power":"OFF"}',
      levelPayload: '{"power":"ON"}'
    }
  };

  function showStatus(obj) {
    document.getElementById('statusOutput').textContent =
      typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2);
  }

  async function getSetting(key) {
    const resp = await fetch(`api/plugin/${plugin}/settings/${encodeURIComponent(key)}`);
    const json = await resp.json();
    return json[key] || '';
  }

  async function setSetting(key, value) {
    const resp = await fetch(`api/plugin/${plugin}/settings/${encodeURIComponent(key)}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'text/plain' },
      body: value
    });
    return await resp.json();
  }

  async function loadSettings() {
    for (const [key, id] of Object.entries(fields)) {
      try {
        const value = await getSetting(key);
        document.getElementById(id).value = value;
      } catch (err) {
        showStatus(`Failed to load ${key}: ${err}`);
      }
    }

    inferProfileSelection();
  }

  function inferProfileSelection() {
    const levelKey = document.getElementById('levelKey').value.trim();
    const onPayload = document.getElementById('onPayload').value.trim();
    const offPayload = document.getElementById('offPayload').value.trim();
    const levelPayload = document.getElementById('levelPayload').value.trim();
    const select = document.getElementById('deviceProfile');

    if (levelKey === profiles.d26hd.levelKey && onPayload === profiles.d26hd.onPayload && offPayload === profiles.d26hd.offPayload && levelPayload === profiles.d26hd.levelPayload) {
      select.value = 'd26hd';
      return;
    }

    if (levelKey === profiles.dw15p.levelKey && onPayload === profiles.dw15p.onPayload && offPayload === profiles.dw15p.offPayload && levelPayload === profiles.dw15p.levelPayload) {
      select.value = 'dw15p';
      return;
    }

    if (levelKey === profiles.default.levelKey && onPayload === profiles.default.onPayload && offPayload === profiles.default.offPayload && levelPayload === profiles.default.levelPayload) {
      select.value = 'default';
      return;
    }

    select.value = 'custom';
  }

  function applyProfile(profileName) {
    if (profileName === 'custom') {
      return;
    }
    const profile = profiles[profileName];
    if (!profile) {
      return;
    }

    document.getElementById('levelKey').value = profile.levelKey;
    document.getElementById('onPayload').value = profile.onPayload;
    document.getElementById('offPayload').value = profile.offPayload;
    document.getElementById('levelPayload').value = profile.levelPayload;
    document.getElementById('deviceProfile').value = profileName;
    showStatus({ ok: true, message: `Applied profile: ${profileName}` });
  }

  async function saveSettings() {
    const onPayload = document.getElementById('onPayload').value.trim();
    const offPayload = document.getElementById('offPayload').value.trim();
    const levelPayload = document.getElementById('levelPayload').value.trim();

    if (onPayload) {
      try { JSON.parse(onPayload); } catch (e) {
        showStatus('On Payload JSON is invalid: ' + e);
        return;
      }
    }

    if (offPayload) {
      try { JSON.parse(offPayload); } catch (e) {
        showStatus('Off Payload JSON is invalid: ' + e);
        return;
      }
    }

    if (levelPayload) {
      try { JSON.parse(levelPayload); } catch (e) {
        showStatus('Level Payload JSON is invalid: ' + e);
        return;
      }
    }

    const results = {};
    for (const [key, id] of Object.entries(fields)) {
      const value = document.getElementById(id).value;
      results[key] = await setSetting(key, value);
    }

    showStatus({ ok: true, message: 'Settings saved', results });
  }

  function getDeviceModel(device) {
    return device?.raw?.modelType || device?.raw?.model || device?.deviceType || 'unknown';
  }

  function getSortedDevices(devices) {
    return [...(devices || [])].sort((a, b) => {
      const aName = String(a?.name || '').toLowerCase();
      const bName = String(b?.name || '').toLowerCase();
      return aName.localeCompare(bName);
    });
  }

  function guessProfileFromDevice(device) {
    const modelStr = String(getDeviceModel(device)).toLowerCase().trim();
    if (modelStr.includes('dw15p') || modelStr.includes('outlet')) {
      return 'dw15p';
    }
    if (modelStr.includes('d26hd') || modelStr.includes('dimmer')) {
      return 'd26hd';
    }
    return 'default';
  }

  function selectDeviceFromDropdown(deviceId) {
    const selectedId = String(deviceId || '').trim();
    if (!selectedId) {
      return;
    }
    const device = discoveredDevices.find(d => String(d.id || '').trim() === selectedId);
    if (!device) {
      showStatus({ ok: false, message: `Device ID ${selectedId} not found in discovered list.` });
      return;
    }
    const profileName = guessProfileFromDevice(device);
    const deviceModel = getDeviceModel(device);
    document.getElementById('defaultSwitch').value = selectedId;
    document.getElementById('quickSelectDevice').value = selectedId;
    applyProfile(profileName);
    showStatus({ ok: true, message: `Selected: ${device.name} (${deviceModel})` });
  }

  window.selectDeviceFromDropdown = selectDeviceFromDropdown;

  function populateQuickSelectDropdown() {
    const select = document.getElementById('quickSelectDevice');
    select.innerHTML = '<option value="">-- Select discovered device --</option>';
    
    if (discoveredDevices.length === 0) return;

    const sortedDevices = getSortedDevices(discoveredDevices);
    sortedDevices.forEach(device => {
      const option = document.createElement('option');
      option.value = String(device.id || '');
      const deviceModel = getDeviceModel(device);
      option.textContent = `${device.name} (${deviceModel})`;
      select.appendChild(option);
    });
  }

  function renderDiscoveredDevices(devices) {
    if (!Array.isArray(devices) || devices.length === 0) {
      document.getElementById('devicesOutput').innerHTML = 'No devices discovered.';
      populateQuickSelectDropdown();
      return;
    }

    const sortedDevices = getSortedDevices(devices);

    let html = '<table style="width:100%; border-collapse:collapse; color:#ddd;">';
    html += '<tr style="border-bottom:1px solid #444;"><th style="text-align:left;padding:8px;">Name</th><th style="text-align:left;padding:8px;">ID</th><th style="text-align:left;padding:8px;">Model</th><th style="text-align:center;padding:8px;">Action</th></tr>';

    sortedDevices.forEach((device, index) => {
      const guessedProfile = guessProfileFromDevice(device);
      const profileLabel = guessedProfile === 'dw15p' ? 'DW15P' : guessedProfile === 'd26hd' ? 'D26HD' : 'Default';
      const deviceId = String(device.id || '?');
      const deviceName = device.name || `Device ${index + 1}`;
      const deviceModel = getDeviceModel(device);

      html += `<tr style="border-bottom:1px solid #333;">`;
      html += `<td style="padding:8px;">${deviceName}</td>`;
      html += `<td style="padding:8px;"><code>${deviceId}</code></td>`;
      html += `<td style="padding:8px;">${deviceModel}</td>`;
      html += `<td style="padding:8px; text-align:center;">`;
      html += `<button class="buttons btn-outline-primary" style="padding:4px 8px; font-size:12px;" onclick="selectDeviceFromDropdown('${deviceId}')">Select (${profileLabel})</button>`;
      html += `</td>`;
      html += `</tr>`;
    });

    html += '</table>';
    document.getElementById('devicesOutput').innerHTML = html;
    populateQuickSelectDropdown();
  }

  async function saveDiscoveredDevices(devices) {
    try {
      await setSetting(discoveredDevicesSettingKey, JSON.stringify(devices || []));
    } catch (err) {
      showStatus(`Warning: unable to cache discovered devices: ${err}`);
    }
  }

  async function loadDiscoveredDevicesFromCache() {
    try {
      const raw = await getSetting(discoveredDevicesSettingKey);
      if (!raw) {
        return;
      }
      const parsed = JSON.parse(raw);
      if (!Array.isArray(parsed)) {
        return;
      }
      discoveredDevices = parsed;
      renderDiscoveredDevices(discoveredDevices);
      showStatus({ ok: true, message: `Loaded ${discoveredDevices.length} cached device(s).` });
    } catch (err) {
      showStatus(`Warning: unable to load cached discovered devices: ${err}`);
    }
  }

  async function discoverDevices() {
    showStatus('Discovering devices...');
    const resp = await fetch(`api/plugin/${plugin}/devices`);
    const data = await resp.json();
    
    if (data.ok === false) {
      document.getElementById('devicesOutput').textContent = JSON.stringify(data, null, 2);
      showStatus(data);
      return;
    }

    const devices = data.devices || [];
    discoveredDevices = devices;
    renderDiscoveredDevices(discoveredDevices);
    await saveDiscoveredDevices(discoveredDevices);
    showStatus({ ok: true, count: devices.length, message: `Discovered ${devices.length} device(s)` });
  }

  async function runAction(action, value = null) {
    const switchId = document.getElementById('defaultSwitch').value.trim();
    const payload = {
      action: action,
      switchId: switchId
    };

    if (value !== null && value !== undefined && value !== '') {
      payload.value = String(value);
    }

    const resp = await fetch(`api/plugin/${plugin}/run`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await resp.json();
    showStatus(data);
  }

  document.getElementById('saveBtn').addEventListener('click', saveSettings);
  document.getElementById('discoverBtn').addEventListener('click', discoverDevices);
  document.getElementById('testOnBtn').addEventListener('click', () => runAction('on'));
  document.getElementById('testOffBtn').addEventListener('click', () => runAction('off'));
  document.getElementById('testLevelBtn').addEventListener('click', () => {
    const level = parseInt(document.getElementById('testLevelValue').value, 10);
    if (Number.isNaN(level) || level < 0 || level > 100) {
      showStatus('Test level must be an integer between 0 and 100.');
      return;
    }
    runAction('level', level);
  });
  document.getElementById('deviceProfile').addEventListener('change', (e) => applyProfile(e.target.value));
  
  const quickSelectEl = document.getElementById('quickSelectDevice');
  if (quickSelectEl) {
    quickSelectEl.addEventListener('change', (e) => {
      selectDeviceFromDropdown(e.target.value);
    });
  }

  loadSettings();
  loadDiscoveredDevicesFromCache();
})();
</script>
