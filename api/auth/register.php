<?php
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed', 405);

$body     = getBody();
$name     = trim($body['full_name'] ?? '');
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';
$role     = $body['role'] ?? 'tenant';
$phone    = trim($body['phone'] ?? '');

if (!$name || !$email || !$password) error('Name, email and password are required');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error('Invalid email format');
if (strlen($password) < 6) error('Password must be at least 6 characters');
if (!in_array($role, ['landlord', 'tenant', 'technician'])) error('Invalid role');

$db = getDB();
$stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) error('This email is already registered');

// Tenants are auto-approved; landlords and technicians need admin approval
$approvalStatus = $role === 'tenant' ? 'approved' : 'pending';
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $db->prepare('INSERT INTO users (role, full_name, email, phone, password_hash, approval_status) VALUES (?,?,?,?,?,?)');
$stmt->execute([$role, $name, $email, $phone, $hash, $approvalStatus]);
$newId = (int)$db->lastInsertId();

if ($role === 'technician') {
    $db->prepare('INSERT INTO technicians (user_id, full_name, email, phone) VALUES (?,?,?,?)')->execute([$newId, $name, $email, $phone]);
}

$message = $approvalStatus === 'pending'
    ? 'Account created. Awaiting admin approval before you can log in.'
    : 'Account created successfully. You can now log in.';

success(['id' => $newId, 'role' => $role, 'approval_status' => $approvalStatus], $message);
