<h2>Leviton Direct Plugin Help</h2>
<p>Use <b>Configuration</b> to set your credentials and device-level behavior.</p>
<ul>
  <li><b>Username / Email</b>: your myLeviton login</li>
  <li><b>Password</b>: your myLeviton password</li>
  <li><b>Default Switch ID</b>: optional switch used when command does not include a switch ID</li>
  <li><b>Device Profile</b>: quick payload presets (Default Leviton or D26HD)</li>
  <li><b>Level Key</b>: payload key used for dim/level updates (default: brightness)</li>
  <li><b>On/Off Payload JSON</b>: model-specific payload mapping</li>
  <li><b>Level Payload JSON</b>: optional JSON template for level action, supports <code>__LEVEL__</code> token</li>
  <li><b>Friendly Name</b>: map a readable name (like <code>Dining</code>) to a discovered device ID for scripts</li>
</ul>
<p>Use <b>Discover Devices</b> to pull current switch IDs from your account.</p>
<p>Save aliases in <b>Friendly Name</b>, then run commands with alias instead of ID, for example:</p>
<pre>bash /home/fpp/media/plugins/fpp-plugin-leviton-direct/commands/leviton_action.sh Dining on</pre>
<p>Playlist helper scripts are also available:</p>
<ul>
  <li><code>bash /home/fpp/media/plugins/fpp-plugin-leviton-direct/commands/leviton_on.sh [alias_or_id]</code></li>
  <li><code>bash /home/fpp/media/plugins/fpp-plugin-leviton-direct/commands/leviton_off.sh [alias_or_id]</code></li>
  <li><code>bash /home/fpp/media/plugins/fpp-plugin-leviton-direct/commands/leviton_dim.sh &lt;0-100&gt; [alias_or_id]</code></li>
</ul>
<p>Use <b>Test LEVEL</b> with a 0-100 value to verify dimming behavior directly from the UI.</p>
