<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$user = current_user();
$appointments = [];

$stmt = $conn->prepare(
    'SELECT id, service, doctor, appointment_date, appointment_time, notes,
            status, doctor_message, proposed_date, proposed_time, created_at,
            cancelled_by, cancelled_by_role, cancelled_at, cancellation_reason
     FROM appointments
     WHERE email = ?
     ORDER BY appointment_date DESC, appointment_time DESC'
);

if ($stmt) {
    $stmt->bind_param('s', $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    $stmt->close();
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
    <title>My appointments — Serein Health</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
</head>
<body class="p-4">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="mb-0">My appointments</h1>
                <p class="text-muted mb-0">Signed in as <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="d-flex gap-2">
                <?php if (is_admin()): ?>
                    <a class="btn btn-outline-primary btn-sm" href="admin_bookings.php">Admin panel</a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary btn-sm" href="index.php">View site</a>
                <a class="btn btn-danger btn-sm" href="logout.php">Log out</a>
            </div>
        </div>

        <?php render_flash(); ?>

        <?php if (empty($appointments)): ?>
            <div class="alert alert-info">
                You have no appointments booked with <strong><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></strong>.
                <a href="index.php#book" class="alert-link">Book an appointment</a>.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Doctor message</th>
                            <th>Notes</th>
                            <th>Booked at</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td><?= (int)$row['id'] ?></td>
                                <td><?= htmlspecialchars($row['service'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['doctor'] ?: 'First available', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['appointment_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['appointment_time'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge <?= $statusClasses[$row['status']] ?? 'bg-secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($row['status']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($row['status'] === 'rescheduled' && $row['proposed_date']): ?>
                                        <div class="small text-muted mt-1">
                                            New: <?= htmlspecialchars($row['proposed_date'], ENT_QUOTES, 'UTF-8') ?>
                                            at <?= htmlspecialchars($row['proposed_time'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($row['status'] === 'cancelled' && !empty($row['cancelled_by'])): ?>
                                        <div class="small text-danger mt-1">
                                            Cancelled by <?= htmlspecialchars(strtolower(cancellation_actor_label($row['cancelled_by_role'])), ENT_QUOTES, 'UTF-8') ?>:
                                            <strong><?= htmlspecialchars($row['cancelled_by'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if (!empty($row['cancelled_at'])): ?>
                                                <br><?= htmlspecialchars($row['cancelled_at'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                            <?php if (!empty($row['cancellation_reason'])): ?>
                                                <br>Reason: <?= htmlspecialchars($row['cancellation_reason'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= htmlspecialchars($row['doctor_message'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['notes'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (in_array($row['status'], ['pending', 'confirmed', 'rescheduled'], true)): ?>
                                        <details>
                                            <summary class="small text-danger" style="cursor:pointer;">Cancel</summary>
                                            <form method="POST" action="process_patient_cancel.php" class="mt-2" style="min-width: 180px;"
                                                  onsubmit="return confirm('Cancel this appointment? This cannot be undone.');">
                                                <input type="hidden" name="appointment_id" value="<?= (int)$row['id'] ?>">
                                                <input type="text" name="reason" class="form-control form-control-sm mb-2"
                                                       placeholder="Reason for cancelling" required>
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Confirm cancellation</button>
                                            </form>
                                        </details>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="assets/js/flash.js?v=<?= filemtime(__DIR__ . '/assets/js/flash.js') ?>"></script>
</body>
</html>
