<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_name('tenurex_sess');
    session_start();
}

function json_out(mixed $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function success(mixed $data = null, string $message = 'OK'): never {
    json_out(['success' => true, 'data' => $data, 'message' => $message]);
}

function error(string $message, int $status = 400): never {
    json_out(['success' => false, 'error' => $message], $status);
}

function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function requireAuth(array $roles = []): array {
    if (empty($_SESSION['user'])) {
        error('Not authenticated', 401);
    }
    $user = $_SESSION['user'];
    if (!empty($roles) && !in_array($user['role'], $roles)) {
        error('Forbidden — role not allowed', 403);
    }
    return $user;
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function h(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}
