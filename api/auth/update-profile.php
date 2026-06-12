<?php
require_once __DIR__ . '/../helpers.php';
$user = requireAuth();
$db   = getDB();
$body = getBody();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);

$name  = trim($body['name']  ?? '');
$email = trim($body['email'] ?? '');

if (!$name || !$email) error('Name and email are required');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error('Invalid email address');

// Check email not taken by another user
$stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
$stmt->execute([$email, $user['id']]);
if ($stmt->fetch()) error('That email is already in use by another account');

// Handle optional password change
$newPass     = $body['new_password']     ?? '';
$currentPass = $body['current_password'] ?? '';
$confirmPass = $body['confirm_password'] ?? '';

if ($newPass !== '') {
    if (strlen($newPass) < 6) error('New password must be at least 6 characters');
    if ($newPass !== $confirmPass) error('New passwords do not match');

    // Verify current password
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($currentPass, $row['password_hash'])) {
        error('Current password is incorrect');
    }

    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
    $db->prepare('UPDATE users SET full_name = ?, email = ?, password_hash = ? WHERE id = ?')
       ->execute([$name, $email, $newHash, $user['id']]);
} else {
    $db->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?')
       ->execute([$name, $email, $user['id']]);
}

// Refresh session
$_SESSION['user']['name']  = $name;
$_SESSION['user']['email'] = $email;

success(['name' => $name, 'email' => $email]);
