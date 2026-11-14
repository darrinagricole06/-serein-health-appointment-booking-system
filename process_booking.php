<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('error', 'Invalid request method.');
}

$firstName = isset($_POST['firstName']) ? sanitize_input($_POST['firstName']) : '';
$lastName  = isset($_POST['lastName'])  ? sanitize_input($_POST['lastName'])  : '';
$email     = isset($_POST['email'])     ? sanitize_input($_POST['email'])     : '';
$phone     = isset($_POST['phone'])     ? sanitize_input($_POST['phone'])     : '';
$service   = isset($_POST['service'])   ? sanitize_input($_POST['service'])   : '';
$doctor    = isset($_POST['doctor'])    ? sanitize_input($_POST['doctor'])    : '';
$date      = isset($_POST['date'])      ? sanitize_input($_POST['date'])      : '';
$time      = isset($_POST['time'])      ? sanitize_input($_POST['time'])      : '';
$notes     = isset($_POST['notes'])     ? sanitize_input($_POST['notes'])     : '';

$errors = [];
if ($firstName === '') $errors[] = 'First name is required.';
if ($lastName === '')  $errors[] = 'Last name is required.';
if (!validate_email($email)) $errors[] = 'A valid email address is required.';
if ($phone === '') $errors[] = 'Phone number is required.';
if ($service === '') $errors[] = 'Please choose a service.';
if ($date === '') $errors[] = 'Please choose a date.';
if ($time === '') $errors[] = 'Please choose a time.';

if (!empty($errors)) {
    redirect_with_message('error', implode(' ', $errors));
}

$doctorId = null;
if ($doctor !== '') {
    $doctorLookup = $conn->prepare('SELECT id, specialty FROM doctors WHERE name = ? LIMIT 1');
    if ($doctorLookup) {
        $doctorLookup->bind_param('s', $doctor);
        $doctorLookup->execute();
        $doctorResult = $doctorLookup->get_result();
        $doctorRow = $doctorResult ? $doctorResult->fetch_assoc() : null;
        if ($doctorRow) {
            $doctorId = (int)$doctorRow['id'];

            if ($service !== '' && service_key_for_specialty($doctorRow['specialty']) !== $service) {
                $doctorLookup->close();
                redirect_with_message('error', 'That doctor does not provide the selected service. Please choose a matching doctor or service.');
            }
        }
        $doctorLookup->close();
    }
}

$accessToken = generate_access_token(10);

$stmt = $conn->prepare(
    'INSERT INTO appointments (first_name, last_name, email, phone, service, doctor, doctor_id, appointment_date, appointment_time, notes, access_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

if (!$stmt) {
    redirect_with_message('error', 'Could not prepare the booking statement.');
}

$stmt->bind_param('ssssssissss', $firstName, $lastName, $email, $phone, $service, $doctor, $doctorId, $date, $time, $notes, $accessToken);

if ($stmt->execute()) {
    $_SESSION['last_booking'] = [
        'id' => (int)$stmt->insert_id,
        'token' => $accessToken,
        'email' => $email,
    ];
    $stmt->close();
    redirect_with_message('success', 'Your appointment has been booked. Your access token is shown below — save it to view or cancel your appointment.');
} else {
    $error = $stmt->error;
    $stmt->close();
    redirect_with_message('error', 'Could not save your booking: ' . $error);
}
?>