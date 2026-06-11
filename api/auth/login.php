<?php
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);

$body     = getBody();
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!$email || !$password) error('Email and password are required');

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    error('Invalid email or password', 401);
}

if ($user['approval_status'] === 'pending') {
    error('Your account is pending admin approval. Please wait.', 403);
}
if ($user['approval_status'] === 'rejected') {
    error('Your account has been rejected. Please contact support.', 403);
}

$_SESSION['user'] = [
    'id'    => (int)$user['id'],
    'role'  => $user['role'],
    'name'  => $user['full_name'],
    'email' => $user['email'],
];

success($_SESSION['user'], 'Login successful');
