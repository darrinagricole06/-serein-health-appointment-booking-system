<?php
require_once __DIR__ . '/includes/auth.php';

logout_user();

redirect_with_message('success', 'You have been logged out successfully.');
?>