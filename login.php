<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_url());
    exit;
}

$error = '';
$username = '';

if (isset($_GET['status'], $_GET['message']) && $_GET['status'] === 'error') {
    $error = sanitize_input($_GET['message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_login'])) {
    $stmt = $conn->prepare("SELECT id, username, email, password_hash, role, doctor_id FROM users WHERE username = 'patient' AND role = 'user' LIMIT 1");

    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    } else {
        $user = null;
    }

    if (!$user) {
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES ('patient', 'patient@example.com', ?, 'user')");
        if ($insert) {
            $insert->bind_param('s', $hash);
            $insert->execute();
            $insert->close();
        }

        $stmt = $conn->prepare("SELECT id, username, email, password_hash, role, doctor_id FROM users WHERE username = 'patient' LIMIT 1");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        }
    }

    if ($user) {
        login_user($user);
        header('Location: index.php');
        exit;
    }

    $error = 'Unable to continue as a patient right now.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both your username/email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, username, email, password_hash, role, doctor_id FROM users WHERE username = ? OR email = ? LIMIT 1');

        if ($stmt) {
            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;

            if ($user && password_verify($password, $user['password_hash'])) {
                login_user($user);

                $redirect = $_SESSION['redirect_after_login'] ?? '';
                unset($_SESSION['redirect_after_login']);

                if ($redirect === '' || strpos($redirect, 'login.php') !== false) {
                    if ($user['role'] === 'admin') {
                        $redirect = 'admin_bookings.php';
                    } elseif ($user['role'] === 'doctor') {
                        $redirect = 'doctor_dashboard.php';
                    } else {
                        $redirect = 'my_bookings.php';
                    }
                }

                $stmt->close();
                header('Location: ' . $redirect);
                exit;
            }

            $stmt->close();
        }

        $error = 'Invalid username/email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — Serein Health</title>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background:
                radial-gradient(circle at top left, rgba(18, 143, 174, .12), transparent 45%),
                var(--cream);
            padding: 2rem 0;
        }
        .auth-card {
            width: 100%;
            max-width: 460px;
            border: 0;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            background: #fff;
        }
        .auth-logo {
            display: inline-flex;
            align-items: center;
            gap: .7rem;
            text-decoration: none;
            color: var(--ink);
        }
        .auth-logo img { width: 46px; height: 46px; }
        .auth-logo strong {
            display: block;
            font-family: var(--font-serif);
            font-size: 1.3rem;
            line-height: 1;
        }
        .auth-logo span {
            font-size: .72rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .auth-card .form-label { font-weight: 600; font-size: .85rem; }
        .auth-card .form-control {
            border-radius: var(--radius-sm);
            padding: .72rem .9rem;
        }
        .auth-card .form-control:focus {
            border-color: var(--ocean-500);
            box-shadow: 0 0 0 .2rem rgba(18, 143, 174, .15);
        }
    </style>
</head>
<body>
    <main class="auth-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 d-flex justify-content-center">
                    <div class="card auth-card">
                        <div class="card-body p-4 p-md-5">
                            <a class="auth-logo mb-4" href="index.php">
                                <img src="assets/img/logo.svg" alt="" width="46" height="46">
                                <span>
                                    <strong>Serein</strong>
                                    <span>Victoria · Seychelles</span>
                                </span>
                            </a>

                            <h1 class="h3 mb-1">Welcome back</h1>
                            <p class="text-muted-soft mb-4">Log in to manage appointments.</p>

                            <?php if ($error !== ''): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= $error ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="login.php" novalidate>
                                <div class="mb-3">
                                    <label class="form-label" for="username">Username or email</label>
                                    <input type="text" class="form-control" id="username" name="username"
                                           value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                                           autocomplete="username" required autofocus>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password"
                                           autocomplete="current-password" required>
                                </div>
                                <button type="submit" class="btn btn-brand w-100 btn-lg">Log in</button>
                            </form>

                            <div class="text-center mt-4">
                                <p class="small text-muted mb-2">Patient? No password needed.</p>
                                <form method="POST" action="login.php">
                                    <input type="hidden" name="patient_login" value="1">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        Continue as patient
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/password-toggle.js?v=<?= filemtime(__DIR__ . '/assets/js/password-toggle.js') ?>"></script>
</body>
</html>
