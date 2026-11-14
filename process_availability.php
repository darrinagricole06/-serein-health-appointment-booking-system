<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('error', 'Invalid request method.');
}

$service = isset($_POST['quickService']) ? sanitize_input($_POST['quickService']) : '';
$date   = isset($_POST['quickDate'])     ? sanitize_input($_POST['quickDate'])     : '';
$time   = isset($_POST['quickTime'])     ? sanitize_input($_POST['quickTime'])     : '';

if ($service === '' || $date === '' || $time === '') {
    redirect_with_message('error', 'Please choose a service, date and time.');
}

redirect_with_message('success', 'Availability checked for ' . $service . ' on ' . $date . ' at ' . $time . '.');
