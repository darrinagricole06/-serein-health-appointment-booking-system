<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message_to('admin_doctors.php', 'error', 'Invalid request method.');
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
$returnTo = 'admin_doctors.php';

function upload_doctor_photo(array $file, int $doctorId): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The photo upload failed. Please try again.');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('The doctor photo must be 2 MB or smaller.');
    }

    $mime = '';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
    }

    if ($mime === '') {
        $imageInfo = @getimagesize($file['tmp_name']);
        $mime = $imageInfo['mime'] ?? '';
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('The photo must be a JPG, PNG or WebP image.');
    }

    $uploadDir = __DIR__ . '/assets/img/doctors/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Could not create the doctor photo folder.');
    }

    $filename = 'doctor-' . $doctorId . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save the uploaded doctor photo.');
    }

    return 'assets/img/doctors/uploads/' . $filename;
}

function delete_old_doctor_photo(?string $image): void
{
    if ($image === null || $image === '') {
        return;
    }

    $normalized = str_replace('\\', '/', $image);
    if (strpos($normalized, 'assets/img/doctors/uploads/') !== 0) {
        return;
    }

    $path = __DIR__ . '/' . $normalized;
    if (is_file($path)) {
        @unlink($path);
    }
}

if ($action === 'add') {
    $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $specialty = isset($_POST['specialty']) ? trim((string)$_POST['specialty']) : '';
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
    $languages = isset($_POST['languages']) ? trim((string)$_POST['languages']) : '';
    $bio = isset($_POST['bio']) ? trim((string)$_POST['bio']) : '';
    $username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $photoOption = isset($_POST['photo_option']) ? trim((string)$_POST['photo_option']) : 'later';
    $uploadedImage = null;

    if ($name === '' || $specialty === '' || $username === '') {
        redirect_with_message_to($returnTo, 'error', 'Name, specialty and username are required.');
    }

    if (!validate_email($email)) {
        redirect_with_message_to($returnTo, 'error', 'A valid doctor email address is required.');
    }

    if (strlen($password) < 6) {
        redirect_with_message_to($returnTo, 'error', 'The temporary password must be at least 6 characters.');
    }

    $conn->begin_transaction();

    try {
        $doctorStmt = $conn->prepare('INSERT INTO doctors (name, specialty, email, phone, languages, bio, image) VALUES (?, ?, ?, ?, ?, ?, NULL)');
        $doctorStmt->bind_param('ssssss', $name, $specialty, $email, $phone, $languages, $bio);
        $doctorStmt->execute();
        $doctorId = (int)$conn->insert_id;
        $doctorStmt->close();

        if ($photoOption === 'now') {
            $uploadedImage = upload_doctor_photo($_FILES['photo'] ?? [], $doctorId);
            if ($uploadedImage !== null) {
                $imageStmt = $conn->prepare('UPDATE doctors SET image = ? WHERE id = ?');
                $imageStmt->bind_param('si', $uploadedImage, $doctorId);
                $imageStmt->execute();
                $imageStmt->close();
            }
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userStmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, doctor_id) VALUES (?, ?, ?, 'doctor', ?)");
        $userStmt->bind_param('sssi', $username, $email, $hash, $doctorId);
        $userStmt->execute();
        $userStmt->close();

        $conn->commit();
        redirect_with_message_to($returnTo, 'success', 'Doctor account created and added to the website.');
    } catch (Throwable $exception) {
        $conn->rollback();
        delete_old_doctor_photo($uploadedImage);
        $errorMessage = $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Could not create the doctor account. The username or email may already exist.';
        redirect_with_message_to($returnTo, 'error', $errorMessage);
    }
}

if ($action === 'update_photo') {
    $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;

    if ($doctorId <= 0) {
        redirect_with_message_to($returnTo, 'error', 'Invalid doctor selected.');
    }

    $lookup = $conn->prepare('SELECT image FROM doctors WHERE id = ? LIMIT 1');
    if (!$lookup) {
        redirect_with_message_to($returnTo, 'error', 'Could not load the doctor record.');
    }

    $lookup->bind_param('i', $doctorId);
    $lookup->execute();
    $lookupResult = $lookup->get_result();
    $doctorRow = $lookupResult ? $lookupResult->fetch_assoc() : null;
    $lookup->close();

    if (!$doctorRow) {
        redirect_with_message_to($returnTo, 'error', 'Doctor not found.');
    }

    $newImage = null;

    try {
        $newImage = upload_doctor_photo($_FILES['photo'] ?? [], $doctorId);
        if ($newImage === null) {
            redirect_with_message_to($returnTo, 'error', 'Choose a photo to upload first.');
        }

        $update = $conn->prepare('UPDATE doctors SET image = ? WHERE id = ?');
        $update->bind_param('si', $newImage, $doctorId);
        $update->execute();
        $update->close();

        delete_old_doctor_photo($doctorRow['image'] ?? null);
        redirect_with_message_to($returnTo, 'success', 'Doctor photo updated successfully.');
    } catch (Throwable $exception) {
        if ($newImage !== null) {
            delete_old_doctor_photo($newImage);
        }
        $errorMessage = $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Could not update the doctor photo.';
        redirect_with_message_to($returnTo, 'error', $errorMessage);
    }
}

if ($action === 'reset_password') {
    $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
    $newPassword = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';

    if ($doctorId <= 0) {
        redirect_with_message_to($returnTo, 'error', 'Invalid doctor selected.');
    }

    if (strlen($newPassword) < 6) {
        redirect_with_message_to($returnTo, 'error', 'The new password must be at least 6 characters.');
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE doctor_id = ? AND role = 'doctor'");

    if (!$stmt) {
        redirect_with_message_to($returnTo, 'error', 'Could not prepare the password reset.');
    }

    $stmt->bind_param('si', $hash, $doctorId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        redirect_with_message_to($returnTo, 'success', 'Doctor password reset successfully.');
    }

    $stmt->close();
    redirect_with_message_to($returnTo, 'error', 'No login account was found for that doctor.');
}

if ($action === 'delete') {
    $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;

    if ($doctorId <= 0) {
        redirect_with_message_to($returnTo, 'error', 'Invalid doctor selected.');
    }

    $lookup = $conn->prepare('SELECT image FROM doctors WHERE id = ? LIMIT 1');
    if (!$lookup) {
        redirect_with_message_to($returnTo, 'error', 'Could not load the doctor record.');
    }

    $lookup->bind_param('i', $doctorId);
    $lookup->execute();
    $lookupResult = $lookup->get_result();
    $doctorRow = $lookupResult ? $lookupResult->fetch_assoc() : null;
    $lookup->close();

    if (!$doctorRow) {
        redirect_with_message_to($returnTo, 'error', 'Doctor not found.');
    }

    $stmt = $conn->prepare('DELETE FROM doctors WHERE id = ?');

    if (!$stmt) {
        redirect_with_message_to($returnTo, 'error', 'Could not prepare the delete request.');
    }

    $stmt->bind_param('i', $doctorId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        delete_old_doctor_photo($doctorRow['image'] ?? null);
        redirect_with_message_to($returnTo, 'success', 'Doctor and login account deleted.');
    }

    $stmt->close();
    redirect_with_message_to($returnTo, 'error', 'Doctor not found.');
}

redirect_with_message_to($returnTo, 'error', 'Unknown doctor management action.');
?>