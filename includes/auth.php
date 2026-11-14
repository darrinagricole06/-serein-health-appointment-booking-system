<?php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function current_user(): ?array
{
    return is_logged_in() ? $_SESSION['user'] : null;
}

function is_admin(): bool
{
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

function is_doctor(): bool
{
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'doctor');
}

function dashboard_url(): string
{
    if (is_admin()) {
        return 'admin_bookings.php';
    }

    if (is_doctor()) {
        return 'doctor_dashboard.php';
    }

    return 'my_bookings.php';
}

function dashboard_label(): string
{
    if (is_admin()) {
        return 'Admin panel';
    }

    if (is_doctor()) {
        return 'Doctor dashboard';
    }

    return 'My appointments';
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'doctor_id' => isset($user['doctor_id']) ? (int)$user['doctor_id'] : null,
    ];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        $message = urlencode('Please log in to access that page.');
        header('Location: login.php?status=error&message=' . $message);
        exit;
    }
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        redirect_with_message('error', 'You do not have permission to access that page.');
    }
}

function require_doctor(): void
{
    require_login();

    if (!is_doctor()) {
        redirect_with_message('error', 'You do not have permission to access that page.');
    }
}
?>