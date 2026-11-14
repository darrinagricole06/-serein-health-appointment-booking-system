<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('error', 'Invalid request method.');
}

$email = isset($_POST['newsletterEmail']) ? sanitize_input($_POST['newsletterEmail']) : '';

if (!validate_email($email)) {
    redirect_with_message('error', 'Please enter a valid email address.');
}

$stmt = $conn->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?)');

if (!$stmt) {
    redirect_with_message('error', 'Could not prepare the subscription statement.');
}

$stmt->bind_param('s', $email);

if ($stmt->execute()) {
    $stmt->close();
    redirect_with_message('success', 'Thanks for subscribing! Health notes will arrive monthly at ' . $email . '.');
} else {
    // Treat duplicate emails as a success so the user is not blocked.
    if ($conn->errno === 1062) {
        $stmt->close();
        redirect_with_message('success', 'You are already subscribed. Health notes will arrive monthly at ' . $email . '.');
    }
    $error = $stmt->error;
    $stmt->close();
    redirect_with_message('error', 'Could not save your subscription: ' . $error);
}
?>