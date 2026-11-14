<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$appointments = [];
$result = $conn->query(
    'SELECT id, first_name, last_name, email, phone, service, doctor, doctor_id,
            appointment_date, appointment_time, notes, status, doctor_message,
            proposed_date, proposed_time, created_at,
            cancelled_by, cancelled_by_role, cancelled_at, cancellation_reason
     FROM appointments
     ORDER BY created_at DESC'
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $result->free();
}

$subscribers = [];
$res2 = $conn->query('SELECT id, email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC');
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $subscribers[] = $row;
    }
    $res2->free();
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
    <title>Serein Health — Bookings (Admin)</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0">Appointments</h1>
                <p class="text-muted mb-0">Manage every booking and doctor response.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">
                    Signed in as <strong><?= htmlspecialchars(current_user()['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                    (<?= htmlspecialchars(current_user()['role'], ENT_QUOTES, 'UTF-8') ?>)
                </span>
                <a class="btn btn-outline-primary btn-sm" href="admin_doctors.php">Manage doctors</a>
                <a class="btn btn-outline-secondary btn-sm" href="index.php">View site</a>
                <a class="btn btn-danger btn-sm" href="logout.php">Log out</a>
            </div>
        </div>

        <?php render_flash(); ?>

        <?php if (empty($appointments)): ?>
            <div class="alert alert-info">No appointments booked yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Doctor</th>
                            <th>Appointment</th>
                            <th>Status</th>
                            <th>Doctor message</th>
                            <th>Booked</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td><?= (int)$row['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <div class="small text-muted">
                                        <a href="mailto:<?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></a><br>
                                        <?= htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['service'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['doctor'] ?: 'First available', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small">
                                    <?= htmlspecialchars($row['appointment_date'], ENT_QUOTES, 'UTF-8') ?><br>
                                    <?= htmlspecialchars($row['appointment_time'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClasses[$row['status']] ?? 'bg-secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($row['status']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($row['status'] === 'rescheduled' && $row['proposed_date']): ?>
                                        <div class="small text-muted mt-1">
                                            Proposed: <?= htmlspecialchars($row['proposed_date'], ENT_QUOTES, 'UTF-8') ?>
                                            <?= htmlspecialchars($row['proposed_time'], ENT_QUOTES, 'UTF-8') ?>
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
                                <td class="small"><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (is_patient_cancelled($row)): ?>
                                        <p class="small text-muted mb-2">Cancelled by patient. You can only send a thank-you message.</p>
                                        <form method="POST" action="process_appointment_action.php" class="mt-2" style="min-width: 220px;">
                                            <input type="hidden" name="appointment_id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="action" value="feedback">
                                            <div class="mb-2">
                                                <input type="text" name="message" class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars($row['doctor_message'] ?: 'Thank you for informing us.', ENT_QUOTES, 'UTF-8') ?>"
                                                       placeholder="Thank you for informing us.">
                                            </div>
                                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Send message</button>
                                        </form>
                                    <?php else: ?>
                                        <details>
                                            <summary class="small text-primary" style="cursor:pointer;">Update</summary>
                                            <form method="POST" action="process_appointment_action.php" class="mt-2" style="min-width: 260px;">
                                                <input type="hidden" name="appointment_id" value="<?= (int)$row['id'] ?>">
                                                <div class="mb-2">
                                                    <select name="action" class="form-select form-select-sm" required>
                                                        <option value="confirm">Confirm</option>
                                                        <option value="reschedule">Offer new time</option>
                                                        <option value="cancel">Cancel</option>
                                                        <option value="complete">Completed</option>
                                                        <option value="feedback">Save feedback</option>
                                                        <option value="delete">Delete appointment</option>
                                                    </select>
                                                </div>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-6">
                                                        <input type="date" name="proposed_date" class="form-control form-control-sm">
                                                    </div>
                                                    <div class="col-6">
                                                        <input type="time" name="proposed_time" class="form-control form-control-sm">
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <input type="text" name="message" class="form-control form-control-sm"
                                                           value="<?= htmlspecialchars($row['doctor_message'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                           placeholder="Message to patient">
                                                </div>
                                                <button type="submit" class="btn btn-brand btn-sm w-100">Update</button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="h5 mt-5 mb-3">Newsletter subscribers</h2>
        <?php if (empty($subscribers)): ?>
            <p class="text-muted">No subscribers yet.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($subscribers as $row): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></span>
                        <small class="text-muted"><?= htmlspecialchars($row['subscribed_at'], ENT_QUOTES, 'UTF-8') ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/flash.js?v=<?= filemtime(__DIR__ . '/assets/js/flash.js') ?>"></script>
</body>
</html>
