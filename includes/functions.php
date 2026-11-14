<?php
require_once __DIR__ . '/config.php';

function sanitize_input($value) {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function redirect_with_message_to($url, $status, $message) {
    $separator = (strpos($url, '?') === false) ? '?' : '&';
    header('Location: ' . $url . $separator . 'status=' . urlencode($status) . '&message=' . urlencode($message));
    exit;
}

function redirect_with_message($status, $message) {
    redirect_with_message_to('index.php', $status, $message);
}

function generate_access_token(int $length = 10): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $maxIndex = strlen($alphabet) - 1;
    $token = '';

    for ($i = 0; $i < $length; $i++) {
        $token .= $alphabet[random_int(0, $maxIndex)];
    }

    return $token;
}

function app_url(string $path = ''): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0])) === 'https');

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $basePath = str_replace(' ', '%20', $basePath);

    $path = ltrim($path, '/');

    return $scheme . '://' . $host . $basePath . ($path === '' ? '' : '/' . $path);
}

function cancellation_actor_label(?string $role): string
{
    return match ($role) {
        'patient' => 'Patient',
        'doctor' => 'Doctor',
        'admin' => 'Administrator',
        default => 'Someone',
    };
}

function service_key_for_specialty(string $specialty): string
{
    $value = strtolower(trim($specialty));

    if ($value === '') {
        return '';
    }

    if (str_contains($value, 'general') || str_contains($value, 'family medicine') || str_contains($value, 'internal medicine')) {
        return 'general';
    }

    if (str_contains($value, 'dent') || str_contains($value, 'orthodont') || str_contains($value, 'teeth') || str_contains($value, 'tooth')) {
        return 'dental';
    }

    if (str_contains($value, 'physio') || str_contains($value, 'rehab') || str_contains($value, 'physical therapy')) {
        return 'physio';
    }

    if (str_contains($value, 'derm') || str_contains($value, 'skin') || str_contains($value, 'aesthet') || str_contains($value, 'cosmetic')) {
        return 'skin';
    }

    if (str_contains($value, 'mental') || str_contains($value, 'psych') || str_contains($value, 'wellness') || str_contains($value, 'counsel')) {
        return 'mental';
    }

    if (str_contains($value, 'nutrition') || str_contains($value, 'diet')) {
        return 'nutrition';
    }

    return '';
}

function is_patient_cancelled(array $appointment): bool
{
    return ($appointment['status'] ?? '') === 'cancelled'
        && ($appointment['cancelled_by_role'] ?? '') === 'patient';
}

function is_real_email($email) {
    if (!validate_email($email)) {
        return false;
    }

    $domain = strtolower(substr(strrchr($email, '@'), 1));
    $fakeDomains = [
        'example.com', 'example.org', 'example.net', 'test.com', 'localhost',
        'invalid.com', 'mailinator.com', 'guerrillamail.com', 'tempmail.com',
        'yopmail.com', 'fake.com', 'noemail.com',
    ];

    if (in_array($domain, $fakeDomains, true)) {
        return false;
    }

    return true;
}

function send_smtp_email($to, $subject, $body, array $config) {
    $host = $config['host'] ?? '';
    $port = (int)($config['port'] ?? 587);
    $username = $config['username'] ?? '';
    $password = $config['password'] ?? '';
    $encryption = $config['encryption'] ?? 'tls';
    $fromEmail = $config['from_email'] ?? 'noreply@serein.sc';
    $fromName = $config['from_name'] ?? 'Serein Health';

    if ($host === '' || $username === '' || $password === '') {
        return false;
    }

    $prefix = '';
    if ($encryption === 'ssl') {
        $prefix = 'ssl://';
    }

    $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 30);
    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, 30);

    $read = function () use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };

    $write = function ($command) use ($socket) {
        fputs($socket, $command . "\r\n");
    };

    $greeting = $read();
    if (strpos($greeting, '220') !== 0) {
        fclose($socket);
        return false;
    }

    $write('EHLO localhost');
    $read();

    if ($encryption === 'tls') {
        $write('STARTTLS');
        $response = $read();
        if (strpos($response, '220') !== 0) {
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        $write('EHLO localhost');
        $read();
    }

    $write('AUTH LOGIN');
    $response = $read();
    if (strpos($response, '334') !== 0) {
        fclose($socket);
        return false;
    }

    $write(base64_encode($username));
    $response = $read();
    if (strpos($response, '334') !== 0) {
        fclose($socket);
        return false;
    }

    $write(base64_encode($password));
    $response = $read();
    if (strpos($response, '235') !== 0) {
        fclose($socket);
        return false;
    }

    $write('MAIL FROM: <' . $fromEmail . '>');
    $response = $read();
    if (strpos($response, '250') !== 0) {
        fclose($socket);
        return false;
    }

    $write('RCPT TO: <' . $to . '>');
    $response = $read();
    if (strpos($response, '250') !== 0) {
        fclose($socket);
        return false;
    }

    $write('DATA');
    $response = $read();
    if (strpos($response, '354') !== 0) {
        fclose($socket);
        return false;
    }

    $headers = "From: $fromName <$fromEmail>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "\r\n";

    $write($headers . $body . "\r\n.");
    $response = $read();
    if (strpos($response, '250') !== 0) {
        fclose($socket);
        return false;
    }

    $write('QUIT');
    fclose($socket);

    return true;
}

function send_appointment_email($to, $subject, $body) {
    if (!is_real_email($to)) {
        return false;
    }

    $config = [];
    $configFile = __DIR__ . '/mail_config.php';
    if (is_file($configFile)) {
        $config = require $configFile;
    }

    if (is_array($config) && !empty($config['host']) && !empty($config['username']) && !empty($config['password'])) {
        $sent = send_smtp_email($to, $subject, $body, $config);
    } else {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Serein Health <noreply@serein.sc>\r\n";
        $headers .= "Reply-To: noreply@serein.sc\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        $sent = @mail($to, $subject, $body, $headers);
    }

    // Keep a local copy so the feature can be verified even when the
    // server has no outgoing mail transport configured.
    $logDir = __DIR__ . '/../data';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/emails.log';
    $logEntry = '[' . date('Y-m-d H:i:s') . "] To: $to | Subject: $subject | Sent: " . ($sent ? 'yes' : 'no') . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    return (bool)$sent;
}

function render_flash(): void {
    if (isset($_GET['status'], $_GET['message'])) {
        $alertClass = ($_GET['status'] === 'success') ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">'
            . htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8')
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}
