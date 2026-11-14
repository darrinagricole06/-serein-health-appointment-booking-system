<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$token = isset($_POST['token']) ? trim((string)$_POST['token']) : '';
if ($token === '' && isset($_GET['token'])) {
    $token = trim((string)$_GET['token']);
}

$validToken = preg_match('/^[A-Za-z0-9]{10}$/', $token) === 1;

$appointment = null;

if ($validToken) {
    $stmt = $conn->prepare(
        'SELECT id, access_token, first_name, last_name, email, phone, service, doctor,
                appointment_date, appointment_time, notes, status, doctor_message,
                proposed_date, proposed_time, cancelled_by, cancelled_by_role,
                cancelled_at, cancellation_reason, created_at
         FROM appointments
         WHERE access_token = ?
         LIMIT 1'
    );

    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $appointment) {
    if (!in_array($appointment['status'], ['pending', 'confirmed', 'rescheduled'], true)) {
        redirect_with_message_to(
            'appointment.php?token=' . urlencode($token),
            'error',
            'This appointment can no longer be cancelled.'
        );
    }

    $reason = isset($_POST['reason']) ? trim((string)$_POST['reason']) : '';

    if ($reason === '') {
        redirect_with_message_to(
            'appointment.php?token=' . urlencode($token),
            'error',
            'Please provide a reason for cancelling.'
        );
    }

    $reasonValue = $reason;

    $cancelledBy = trim(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? ''));
    if ($cancelledBy === '') {
        $cancelledBy = $appointment['email'];
    }

    $update = $conn->prepare(
        "UPDATE appointments
         SET status = 'cancelled', cancelled_by = ?, cancelled_by_role = 'patient',
             cancelled_at = NOW(), cancellation_reason = ?
         WHERE access_token = ? AND status IN ('pending', 'confirmed', 'rescheduled')"
    );

    if (!$update) {
        redirect_with_message_to(
            'appointment.php?token=' . urlencode($token),
            'error',
            'Could not prepare the cancellation request.'
        );
    }

    $update->bind_param('sss', $cancelledBy, $reasonValue, $token);

    if ($update->execute() && $update->affected_rows > 0) {
        $update->close();
        redirect_with_message_to(
            'appointment.php?token=' . urlencode($token),
            'success',
            'Your appointment has been cancelled.'
        );
    }

    $error = $update->error;
    $update->close();

    redirect_with_message_to(
        'appointment.php?token=' . urlencode($token),
        'error',
        'Could not cancel the appointment: ' . $error
    );
}

$statusClasses = [
    'pending' => 'bg-warning text-dark',
    'confirmed' => 'bg-success',
    'rescheduled' => 'bg-info text-dark',
    'cancelled' => 'bg-danger',
    'completed' => 'bg-secondary',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Appointment details — Serein Health</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container py-4" style="max-width: 820px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0">Appointment details</h1>
                <p class="text-muted mb-0">Serein Health</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (is_logged_in()): ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars(dashboard_url(), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(dashboard_label(), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary btn-sm" href="index.php">View site</a>
            </div>
        </div>

        <?php render_flash(); ?>

        <?php if (!$validToken || !$appointment): ?>
            <div class="alert alert-danger">
                This appointment link is invalid or has expired. Please check the link in your email or contact Serein Health.
            </div>
            <a class="btn btn-brand" href="index.php">Back to the website</a>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h2 class="h5 mb-1">
                                <?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name'], ENT_QUOTES, 'UTF-8') ?>
                            </h2>
                            <p class="text-muted mb-0">
                                Appointment #<?= (int)$appointment['id'] ?>
                            </p>
                        </div>
                        <span class="badge <?= $statusClasses[$appointment['status']] ?? 'bg-secondary' ?> fs-6">
                            <?= htmlspecialchars(ucfirst($appointment['status']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-3">
                            <tbody>
                                <tr>
                                    <th class="table-light" style="width: 200px;">Service</th>
                                    <td><?= htmlspecialchars($appointment['service'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="table-light">Doctor</th>
                                    <td><?= htmlspecialchars($appointment['doctor'] ?: 'First available', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="table-light">Date</th>
                                    <td><?= htmlspecialchars($appointment['appointment_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="table-light">Time</th>
                                    <td><?= htmlspecialchars($appointment['appointment_time'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="table-light">Email</th>
                                    <td><?= htmlspecialchars($appointment['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="table-light">Phone</th>
                                    <td><?= htmlspecialchars($appointment['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php if (!empty($appointment['notes'])): ?>
                                    <tr>
                                        <th class="table-light">Your notes</th>
                                        <td><?= nl2br(htmlspecialchars($appointment['notes'], ENT_QUOTES, 'UTF-8')) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($appointment['doctor_message'])): ?>
                        <div class="alert alert-info">
                            <strong>Message from your doctor:</strong>
                            <?= nl2br(htmlspecialchars($appointment['doctor_message'], ENT_QUOTES, 'UTF-8')) ?>
                            <?php if ($appointment['status'] === 'rescheduled' && $appointment['proposed_date']): ?>
                                <hr>
                                <strong>New proposed appointment:</strong>
                                <?= htmlspecialchars($appointment['proposed_date'], ENT_QUOTES, 'UTF-8') ?>
                                at <?= htmlspecialchars($appointment['proposed_time'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($appointment['status'] === 'cancelled' && !empty($appointment['cancelled_by'])): ?>
                        <div class="alert alert-danger">
                            <strong>Cancelled by <?= htmlspecialchars(strtolower(cancellation_actor_label($appointment['cancelled_by_role'])), ENT_QUOTES, 'UTF-8') ?>:</strong>
                            <?= htmlspecialchars($appointment['cancelled_by'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($appointment['cancelled_at'])): ?>
                                <br><?= htmlspecialchars($appointment['cancelled_at'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                            <?php if (!empty($appointment['cancellation_reason'])): ?>
                                <br><strong>Reason:</strong> <?= htmlspecialchars($appointment['cancellation_reason'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array($appointment['status'], ['pending', 'confirmed', 'rescheduled'], true)): ?>
                        <div class="border-top pt-3">
                            <h3 class="h6">Need to cancel?</h3>
                            <p class="text-muted small">
                                Cancelling here updates your appointment immediately.
                            </p>
                            <form method="POST" action="appointment.php" class="row g-2"
                                  onsubmit="return confirm('Cancel this appointment? This cannot be undone.');">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="col-md-8">
                                    <input type="text" name="reason" class="form-control"
                                           placeholder="Reason for cancelling" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        Cancel appointment
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php elseif ($appointment['status'] === 'completed'): ?>
                        <div class="alert alert-secondary mb-0">
                            This appointment has been completed. Thank you for choosing Serein Health.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <p class="text-muted small mt-3 mb-0">
                Keep this link private — anyone with it can view and cancel this appointment.
            </p>
        <?php endif; ?>
    </div>
    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/flash.js?v=<?= filemtime(__DIR__ . '/assets/js/flash.js') ?>"></script>
</body>
</html>
