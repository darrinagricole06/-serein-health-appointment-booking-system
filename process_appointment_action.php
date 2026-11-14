<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

if (!is_admin() && !is_doctor()) {
    redirect_with_message('error', 'You do not have permission to update appointments.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('error', 'Invalid request method.');
}

$returnTo = is_admin() ? 'admin_bookings.php' : 'doctor_dashboard.php';

$appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
$message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';
$proposedDate = isset($_POST['proposed_date']) ? trim((string)$_POST['proposed_date']) : '';
$proposedTime = isset($_POST['proposed_time']) ? trim((string)$_POST['proposed_time']) : '';

$allowedActions = ['confirm', 'reschedule', 'cancel', 'complete', 'feedback', 'delete'];

if ($appointmentId <= 0 || !in_array($action, $allowedActions, true)) {
    redirect_with_message_to($returnTo, 'error', 'Invalid appointment update request.');
}

if ($action === 'reschedule' && ($proposedDate === '' || $proposedTime === '')) {
    redirect_with_message_to($returnTo, 'error', 'Choose a proposed date and time for the new appointment.');
}

$stmt = $conn->prepare(
    'SELECT id, access_token, first_name, last_name, email, phone, doctor_id, doctor, service,
            appointment_date, appointment_time, status, cancelled_by_role
     FROM appointments
     WHERE id = ?
     LIMIT 1'
);
if (!$stmt) {
    redirect_with_message_to($returnTo, 'error', 'Could not load the appointment.');
}

$stmt->bind_param('i', $appointmentId);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$appointment) {
    redirect_with_message_to($returnTo, 'error', 'Appointment not found.');
}

$doctorName = '';

if (is_doctor()) {
    $doctorId = (int)($_SESSION['user']['doctor_id'] ?? 0);

    if ($doctorId > 0) {
        $doctorStmt = $conn->prepare('SELECT name FROM doctors WHERE id = ? LIMIT 1');
        if ($doctorStmt) {
            $doctorStmt->bind_param('i', $doctorId);
            $doctorStmt->execute();
            $doctorResult = $doctorStmt->get_result();
            $doctorRow = $doctorResult ? $doctorResult->fetch_assoc() : null;
            $doctorName = $doctorRow['name'] ?? '';
            $doctorStmt->close();
        }
    }

    $ownsAppointment = ((int)$appointment['doctor_id'] === $doctorId && $doctorId > 0)
        || ($doctorName !== '' && $appointment['doctor'] === $doctorName);

    if (!$ownsAppointment) {
        redirect_with_message_to($returnTo, 'error', 'You can only update appointments assigned to you.');
    }
}

if (is_patient_cancelled($appointment) && $action !== 'feedback') {
    redirect_with_message_to($returnTo, 'error', 'This appointment was cancelled by the patient and cannot be updated.');
}

if ($action === 'delete') {
    if (!is_admin()) {
        redirect_with_message_to($returnTo, 'error', 'Only an administrator can delete appointments.');
    }

    $delete = $conn->prepare('DELETE FROM appointments WHERE id = ?');
    if (!$delete) {
        redirect_with_message_to($returnTo, 'error', 'Could not prepare the delete request.');
    }

    $delete->bind_param('i', $appointmentId);
    $delete->execute();

    if ($delete->affected_rows > 0) {
        $delete->close();
        redirect_with_message_to($returnTo, 'success', 'Appointment deleted.');
    }

    $delete->close();
    redirect_with_message_to($returnTo, 'error', 'Appointment not found.');
}

if ($action === 'feedback') {
    if (is_patient_cancelled($appointment) && $message === '') {
        $message = 'Thank you for informing us.';
    }

    $update = $conn->prepare('UPDATE appointments SET doctor_message = ? WHERE id = ?');
    if ($update) {
        $update->bind_param('si', $message, $appointmentId);
    }
} elseif ($action === 'reschedule') {
    if ($message === '') {
        $message = 'We have to give you another appointment. Sorry for the inconvenience this may cause.';
    }

    $update = $conn->prepare(
        "UPDATE appointments
         SET status = 'rescheduled', doctor_message = ?, proposed_date = ?, proposed_time = ?
         WHERE id = ?"
    );

    if ($update) {
        $update->bind_param('sssi', $message, $proposedDate, $proposedTime, $appointmentId);
    }
} else {
    $statusMap = [
        'confirm' => 'confirmed',
        'cancel' => 'cancelled',
        'complete' => 'completed',
    ];
    $newStatus = $statusMap[$action];

    if ($newStatus === 'cancelled' && $message === '') {
        $message = 'We are sorry, but we need to cancel this appointment. Please contact us to rebook.';
    }

    if ($newStatus === 'cancelled') {
        $actor = current_user();
        $cancelledByRole = is_admin() ? 'admin' : 'doctor';
        $cancelledBy = is_doctor()
            ? ($doctorName !== '' ? $doctorName : ($actor['username'] ?? 'Doctor'))
            : ($actor['username'] ?? 'Administrator');

        $update = $conn->prepare(
            "UPDATE appointments
             SET status = 'cancelled', doctor_message = ?, cancelled_by = ?,
                 cancelled_by_role = ?, cancelled_at = NOW()
             WHERE id = ?"
        );

        if ($update) {
            $update->bind_param('sssi', $message, $cancelledBy, $cancelledByRole, $appointmentId);
        }
    } else {
        $update = $conn->prepare('UPDATE appointments SET status = ?, doctor_message = ? WHERE id = ?');
        if ($update) {
            $update->bind_param('ssi', $newStatus, $message, $appointmentId);
        }
    }
}

if (!$update) {
    redirect_with_message_to($returnTo, 'error', 'Could not prepare the appointment update.');
}

if ($update->execute()) {
    $update->close();

    // Send an email notification for confirmation, rescheduling or cancellation.
    if (in_array($action, ['confirm', 'reschedule', 'cancel'], true)) {
        $patientEmail = $appointment['email'] ?? '';
        $patientName = trim(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? ''));
        $doctorName = $appointment['doctor'] ?? 'your doctor';
        $service = $appointment['service'] ?? '';
        $date = $appointment['appointment_date'] ?? '';
        $time = $appointment['appointment_time'] ?? '';

        $accessToken = trim((string)($appointment['access_token'] ?? ''));
        if ($accessToken === '') {
            $accessToken = generate_access_token(10);
            $tokenUpdate = $conn->prepare('UPDATE appointments SET access_token = ? WHERE id = ?');
            if ($tokenUpdate) {
                $tokenUpdate->bind_param('si', $accessToken, $appointmentId);
                $tokenUpdate->execute();
                $tokenUpdate->close();
            }
        }

        $appointmentLink = app_url('appointment.php?token=' . urlencode($accessToken));
        $linkBlock = '<p style="margin:24px 0;">'
            . '<a href="' . htmlspecialchars($appointmentLink, ENT_QUOTES, 'UTF-8') . '" '
            . 'style="display:inline-block;background:#128fae;color:#ffffff;padding:12px 22px;'
            . 'border-radius:8px;text-decoration:none;font-weight:600;">'
            . 'View or cancel your appointment</a></p>'
            . '<p style="font-size:12px;color:#6b7280;word-break:break-all;">'
            . 'Or copy this link: ' . htmlspecialchars($appointmentLink, ENT_QUOTES, 'UTF-8') . '</p>';

        if ($action === 'confirm') {
            if ($message === '') {
                $message = 'Your appointment has been confirmed. We look forward to seeing you.';
            }
            $subject = 'Your appointment has been confirmed - Serein Health';
            $body = '<html><body style="font-family:Arial,sans-serif;color:#1f2937;">'
                . '<h2 style="color:#128fae;">Appointment confirmed</h2>'
                . '<p>Dear ' . htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Your appointment with <strong>' . htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8') . '</strong> has been confirmed.</p>'
                . '<ul>'
                . '<li><strong>Service:</strong> ' . htmlspecialchars($service, ENT_QUOTES, 'UTF-8') . '</li>'
                . '<li><strong>Date:</strong> ' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . '</li>'
                . '<li><strong>Time:</strong> ' . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . '</li>'
                . '</ul>'
                . '<p><strong>Message from your doctor:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
                . $linkBlock
                . '<p>Thank you for choosing Serein Health.</p>'
                . '</body></html>';
        } elseif ($action === 'reschedule') {
            $proposedDate = $proposedDate ?: $date;
            $proposedTime = $proposedTime ?: $time;
            $subject = 'Your appointment has been rescheduled - Serein Health';
            $body = '<html><body style="font-family:Arial,sans-serif;color:#1f2937;">'
                . '<h2 style="color:#128fae;">Appointment rescheduled</h2>'
                . '<p>Dear ' . htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>We are sorry, but we need to move your appointment with <strong>' . htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
                . '<p><strong>Original appointment:</strong> ' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . ' at ' . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><strong>New proposed appointment:</strong> ' . htmlspecialchars($proposedDate, ENT_QUOTES, 'UTF-8') . ' at ' . htmlspecialchars($proposedTime, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><strong>Message from your doctor:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Please contact us if the new time does not work for you.</p>'
                . $linkBlock
                . '<p>Thank you for choosing Serein Health.</p>'
                . '</body></html>';
        } elseif ($action === 'cancel') {
            if ($message === '') {
                $message = 'We are sorry, but we need to cancel this appointment. Please contact us to rebook.';
            }
            $subject = 'Your appointment has been cancelled - Serein Health';
            $body = '<html><body style="font-family:Arial,sans-serif;color:#1f2937;">'
                . '<h2 style="color:#c0392b;">Appointment cancelled</h2>'
                . '<p>Dear ' . htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Your appointment with <strong>' . htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8') . '</strong> on ' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . ' at ' . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . ' has been cancelled.</p>'
                . '<p><strong>Reason:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Please contact us to rebook at your convenience.</p>'
                . $linkBlock
                . '<p>Thank you for choosing Serein Health.</p>'
                . '</body></html>';
        }

        send_appointment_email($patientEmail, $subject, $body);
    }

    redirect_with_message_to($returnTo, 'success', 'Appointment updated successfully.');
}

$error = $update->error;
$update->close();
redirect_with_message_to($returnTo, 'error', 'Could not update the appointment: ' . $error);
?>