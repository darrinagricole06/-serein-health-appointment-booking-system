<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_doctor();

$user = current_user();
$doctorId = (int)($user['doctor_id'] ?? 0);
$doctor = null;
$appointments = [];

if ($doctorId > 0) {
    $doctorStmt = $conn->prepare('SELECT id, name, specialty, email, phone FROM doctors WHERE id = ? LIMIT 1');
    if ($doctorStmt) {
        $doctorStmt->bind_param('i', $doctorId);
        $doctorStmt->execute();
        $doctorResult = $doctorStmt->get_result();
        $doctor = $doctorResult ? $doctorResult->fetch_assoc() : null;
        $doctorStmt->close();
    }
}

if ($doctor) {
    $stmt = $conn->prepare(
        'SELECT id, first_name, last_name, email, phone, service, doctor, doctor_id,
                appointment_date, appointment_time, notes, status, doctor_message,
                proposed_date, proposed_time, created_at,
                cancelled_by, cancelled_by_role, cancelled_at, cancellation_reason
         FROM appointments
         WHERE doctor_id = ? OR doctor = ?
         ORDER BY appointment_date ASC, appointment_time ASC'
    );

    if ($stmt) {
        $stmt->bind_param('is', $doctorId, $doctor['name']);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }

        $stmt->close();
    }
}

$waitingAppointments = [];
$confirmedAppointments = [];

foreach ($appointments as $appointment) {
    if ($appointment['status'] === 'pending') {
        $waitingAppointments[] = $appointment;
    } elseif ($appointment['status'] === 'confirmed') {
        $confirmedAppointments[] = $appointment;
    }
}

$totalCount = count($appointments);
$waitingCount = count($waitingAppointments);
$confirmedCount = count($confirmedAppointments);

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
    <title>Doctor dashboard — Serein Health</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0"><?= $doctor ? htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') : 'Doctor dashboard' ?></h1>
                <p class="text-muted mb-0">
                    <?= $doctor ? htmlspecialchars($doctor['specialty'], ENT_QUOTES, 'UTF-8') : 'No doctor profile linked to this account.' ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="index.php">View site</a>
                <a class="btn btn-danger btn-sm" href="logout.php">Log out</a>
            </div>
        </div>

        <?php render_flash(); ?>

        <?php if (!$doctor): ?>
            <div class="alert alert-warning">
                Your account is not linked to a doctor record. Ask the administrator to fix your account.
            </div>
        <?php else: ?>
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <a href="#total-section" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Total appointments</div>
                                <div class="fs-3 fw-bold"><?= $totalCount ?></div>
                                <div class="small text-muted mt-1">Click to view all</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-4">
                    <a href="#waiting-section" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Waiting for approval</div>
                                <div class="fs-3 fw-bold text-warning"><?= $waitingCount ?></div>
                                <div class="small text-muted mt-1">Click to view pending</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-4">
                    <a href="#confirmed-section" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Confirmed</div>
                                <div class="fs-3 fw-bold text-success"><?= $confirmedCount ?></div>
                                <div class="small text-muted mt-1">Click to view confirmed</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Total appointments -->
            <div id="total-section" class="mb-4">
                <h2 class="h5 mb-3">
                    <i class="bi bi-list-check text-primary me-2"></i>Total appointments
                    <span class="badge bg-primary ms-2"><?= $totalCount ?></span>
                </h2>

                <?php if (empty($appointments)): ?>
                    <div class="alert alert-info">No appointments assigned yet.</div>
                <?php else: ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <?php include __DIR__ . '/includes/doctor_appointment_card.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Waiting for approval -->
            <div id="waiting-section" class="mb-4">
                <h2 class="h5 mb-3">
                    <i class="bi bi-hourglass-split text-warning me-2"></i>Waiting for approval
                    <span class="badge bg-warning text-dark ms-2"><?= $waitingCount ?></span>
                </h2>

                <?php if (empty($waitingAppointments)): ?>
                    <div class="alert alert-info">No appointments are waiting for confirmation.</div>
                <?php else: ?>
                    <?php foreach ($waitingAppointments as $appointment): ?>
                        <?php include __DIR__ . '/includes/doctor_appointment_card.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Confirmed -->
            <div id="confirmed-section" class="mb-4">
                <h2 class="h5 mb-3">
                    <i class="bi bi-check-circle text-success me-2"></i>Confirmed appointments
                    <span class="badge bg-success ms-2"><?= $confirmedCount ?></span>
                </h2>

                <?php if (empty($confirmedAppointments)): ?>
                    <div class="alert alert-info">No confirmed appointments yet.</div>
                <?php else: ?>
                    <?php foreach ($confirmedAppointments as $appointment): ?>
                        <?php include __DIR__ . '/includes/doctor_appointment_card.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/flash.js?v=<?= filemtime(__DIR__ . '/assets/js/flash.js') ?>"></script>
    <script>
        (function () {
            var sectionIds = ['total-section', 'waiting-section', 'confirmed-section'];
            var cards = document.querySelectorAll('a[href^="#"][href$="-section"]');

            function showSection(targetId) {
                sectionIds.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.style.display = (id === targetId) ? '' : 'none';
                    }
                });
            }

            cards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    var href = card.getAttribute('href') || '';
                    var targetId = href.replace('#', '');
                    if (sectionIds.indexOf(targetId) !== -1) {
                        e.preventDefault();
                        showSection(targetId);
                        var target = document.getElementById(targetId);
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                });
            });

            // Show only Total appointments by default.
            showSection('total-section');
        })();
    </script>
</body>
</html>
