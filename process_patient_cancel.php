<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message_to('my_bookings.php', 'error', 'Invalid request method.');
}

$user = current_user();
$appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$reason = isset($_POST['reason']) ? trim((string)$_POST['reason']) : '';

if ($appointmentId <= 0) {
    redirect_with_message_to('my_bookings.php', 'error', 'Invalid appointment.');
}

$stmt = $conn->prepare(
    'SELECT id, first_name, last_name, email, service, doctor, appointment_date,
            appointment_time, status, cancelled_by
     FROM appointments
     WHERE id = ?
     LIMIT 1'
);

if (!$stmt) {
    redirect_with_message_to('my_bookings.php', 'error', 'Could not load the appointment.');
}

$stmt->bind_param('i', $appointmentId);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$appointment) {
    redirect_with_message_to('my_bookings.php', 'error', 'Appointment not found.');
}

$ownsAppointment = strcasecmp((string)$appointment['email'], (string)$user['email']) === 0;

if (!$ownsAppointment) {
    redirect_with_message_to('my_bookings.php', 'error', 'You can only cancel your own appointments.');
}

if (!in_array($appointment['status'], ['pending', 'confirmed', 'rescheduled'], true)) {
    redirect_with_message_to('my_bookings.php', 'error', 'This appointment can no longer be cancelled.');
}

if ($reason === '') {
    redirect_with_message_to('my_bookings.php', 'error', 'Please provide a reason for cancelling.');
}

$cancelledBy = trim(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? ''));
if ($cancelledBy === '') {
    $cancelledBy = $user['username'];
}

$cancelledByRole = in_array($user['role'], ['admin', 'doctor'], true) ? $user['role'] : 'patient';

$update = $conn->prepare(
    "UPDATE appointments
     SET status = 'cancelled', cancelled_by = ?, cancelled_by_role = ?, cancelled_at = NOW(),
         cancellation_reason = ?
     WHERE id = ? AND status IN ('pending', 'confirmed', 'rescheduled')"
);

if (!$update) {
    redirect_with_message_to('my_bookings.php', 'error', 'Could not prepare the cancellation request.');
}

$update->bind_param('sssi', $cancelledBy, $cancelledByRole, $reason, $appointmentId);

if ($update->execute() && $update->affected_rows > 0) {
    $update->close();
    redirect_with_message_to('my_bookings.php', 'success', 'Your appointment has been cancelled.');
}

$error = $update->error;
$update->close();

redirect_with_message_to('my_bookings.php', 'error', 'Could not cancel the appointment: ' . $error);
