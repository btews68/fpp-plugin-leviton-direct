<?php

function getEndpointsfpppluginlevitondirect() {
    $result = array();

    $result[] = array(
        'method' => 'GET',
        'endpoint' => 'devices',
        'callback' => 'fpppluginlevitondirectDevices'
    );

    $result[] = array(
        'method' => 'POST',
        'endpoint' => 'run',
        'callback' => 'fpppluginlevitondirectRun'
    );

    return $result;
}

function fpppluginlevitondirectDevices() {
    global $settings;

    $plugin = 'fpp-plugin-leviton-direct';
    $script = $settings['pluginDirectory'] . '/' . $plugin . '/commands/leviton_control.py';
    $cmd = 'python3 ' . escapeshellarg($script) . ' --list 2>&1';

    $output = array();
    $rc = 0;
    exec($cmd, $output, $rc);
    $raw = implode("\n", $output);

    if ($rc != 0) {
        return json(array('ok' => false, 'error' => $raw, 'rc' => $rc));
    }

    $decoded = json_decode($raw, true);
    if ($decoded === null) {
        return json(array('ok' => false, 'error' => 'Unable to decode script output', 'raw' => $raw));
    }

    return json($decoded);
}

function fpppluginlevitondirectRun() {
    global $settings;

    $plugin = 'fpp-plugin-leviton-direct';
    $script = $settings['pluginDirectory'] . '/' . $plugin . '/commands/leviton_action.sh';

    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!is_array($data)) {
        return json(array('ok' => false, 'error' => 'JSON body required'));
    }

    $action = isset($data['action']) ? $data['action'] : '';
    $switchId = isset($data['switchId']) ? $data['switchId'] : '';
    $value = isset($data['value']) ? $data['value'] : '';

    if ($action == '') {
        return json(array('ok' => false, 'error' => 'action is required'));
    }

    $cmd = escapeshellarg($script) . ' ';
    if ($switchId != '') {
        $cmd .= escapeshellarg($switchId) . ' ' . escapeshellarg($action);
    } else {
        $cmd .= escapeshellarg($action);
    }

    if ($value !== '') {
        $cmd .= ' ' . escapeshellarg($value);
    }

    $cmd .= ' 2>&1';

    $output = array();
    $rc = 0;
    exec($cmd, $output, $rc);
    $raw = implode("\n", $output);

    $decoded = json_decode($raw, true);
    if ($decoded !== null) {
        return json($decoded);
    }

    if ($rc != 0) {
        return json(array('ok' => false, 'error' => $raw, 'rc' => $rc));
    }

    return json(array('ok' => true, 'raw' => $raw));
}

?>
