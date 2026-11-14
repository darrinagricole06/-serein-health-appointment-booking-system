<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$doctors = [];
$sql = "SELECT d.id, d.name, d.specialty, d.email, d.phone, d.image, d.created_at, d.languages, d.bio,
               u.id AS user_id, u.username, u.email AS login_email
        FROM doctors d
        LEFT JOIN users u ON u.doctor_id = d.id AND u.role = 'doctor'
        ORDER BY d.name ASC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage doctors — Serein Health</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h3 mb-0">Manage doctors</h1>
                <p class="text-muted mb-0">Create accounts, reset passwords and remove doctor access.</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="admin_bookings.php">Appointments</a>
                <a class="btn btn-outline-secondary btn-sm" href="index.php">View site</a>
                <a class="btn btn-danger btn-sm" href="logout.php">Log out</a>
            </div>
        </div>

        <?php render_flash(); ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Add a new doctor</h2>
                <form method="POST" action="process_admin_doctor.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="name">Full name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Dr. Jane Doe" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="specialty">Specialty</label>
                            <input type="text" class="form-control" id="specialty" name="specialty" placeholder="General medicine" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="doctor_email">Doctor email</label>
                            <input type="email" class="form-control" id="doctor_email" name="email" placeholder="jane.doe@serein.sc" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="+248 438 8000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="languages">Languages</label>
                            <input type="text" class="form-control" id="languages" name="languages" placeholder="English, French, Creole">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="bio">Short bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="2" placeholder="A little about this doctor"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="username">Login username</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="jane" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="password">Temporary password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="At least 6 characters" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-block">Doctor photo</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="photo_option" id="photo_now" value="now" checked>
                                <label class="form-check-label" for="photo_now">Upload a picture now</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="photo_option" id="photo_later" value="later">
                                <label class="form-check-label" for="photo_later">I'll update later</label>
                            </div>
                            <input type="file" class="form-control form-control-sm" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, PNG or WebP, max 2 MB.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-brand mt-3">
                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Add doctor account
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Doctor accounts</h2>

                <?php if (empty($doctors)): ?>
                    <p class="text-muted mb-0">No doctors found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Doctor</th>
                                    <th>Contact</th>
                                    <th>Login account</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doctor): ?>
                                    <?php
                                    $doctorImage = (!empty($doctor['image']) && is_file(__DIR__ . '/' . $doctor['image']))
                                        ? $doctor['image']
                                        : 'assets/img/doctors/doctor-placeholder.svg';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= htmlspecialchars($doctorImage, ENT_QUOTES, 'UTF-8') ?>"
                                                     alt="" width="44" height="55" class="rounded"
                                                     style="object-fit: cover; flex: 0 0 auto;">
                                                <div>
                                                    <strong><?= htmlspecialchars($doctor['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <div class="small text-muted">
                                                        <?= htmlspecialchars($doctor['specialty'], ENT_QUOTES, 'UTF-8') ?> · ID <?= (int)$doctor['id'] ?>
                                                    </div>
                                                    <?php if (!empty($doctor['languages'])): ?>
                                                        <div class="small text-muted">
                                                            <i class="bi bi-translate"></i>
                                                            <?= htmlspecialchars($doctor['languages'], ENT_QUOTES, 'UTF-8') ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($doctor['bio'])): ?>
                                                        <div class="small text-muted">
                                                            <?= htmlspecialchars(mb_strimwidth($doctor['bio'], 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small text-break">
                                            <?= htmlspecialchars($doctor['email'], ENT_QUOTES, 'UTF-8') ?><br>
                                            <?= htmlspecialchars($doctor['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <?php if ($doctor['username']): ?>
                                                <span class="badge bg-success">Active</span>
                                                <div class="small mt-1"><?= htmlspecialchars($doctor['username'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No account</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="min-width: 210px;">
                                            <details class="mb-2">
                                                <summary class="small text-primary" style="cursor: pointer;">Update photo</summary>
                                                <form method="POST" action="process_admin_doctor.php" enctype="multipart/form-data" class="mt-2">
                                                    <input type="hidden" name="action" value="update_photo">
                                                    <input type="hidden" name="doctor_id" value="<?= (int)$doctor['id'] ?>">
                                                    <input type="file" name="photo" class="form-control form-control-sm mb-1"
                                                           accept="image/jpeg,image/png,image/webp" required>
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Save photo</button>
                                                </form>
                                            </details>
                                            <?php if ($doctor['user_id']): ?>
                                                <form method="POST" action="process_admin_doctor.php" class="d-flex gap-2 mb-2">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="doctor_id" value="<?= (int)$doctor['id'] ?>">
                                                    <input type="password" name="new_password" class="form-control form-control-sm"
                                                           minlength="6" placeholder="New password" required>
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">Reset</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" action="process_admin_doctor.php"
                                                  onsubmit="return confirm('Delete this doctor and their login account?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="doctor_id" value="<?= (int)$doctor['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Delete doctor</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/password-toggle.js?v=<?= filemtime(__DIR__ . '/assets/js/password-toggle.js') ?>"></script>
    <script src="assets/js/flash.js?v=<?= filemtime(__DIR__ . '/assets/js/flash.js') ?>"></script>
    <script>
        (function () {
            const photoInput = document.getElementById('photo');
            const updateLater = document.getElementById('photo_later');
            const uploadNow = document.getElementById('photo_now');

            if (!photoInput || !updateLater || !uploadNow) {
                return;
            }

            function syncPhotoInput() {
                photoInput.disabled = updateLater.checked;
            }

            uploadNow.addEventListener('change', syncPhotoInput);
            updateLater.addEventListener('change', syncPhotoInput);
            syncPhotoInput();
        })();
    </script>
</body>
</html>
