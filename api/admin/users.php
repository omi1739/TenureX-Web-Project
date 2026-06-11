<?php
require_once __DIR__ . '/../helpers.php';
$admin  = requireAuth(['admin']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

if ($method === 'GET') {
    $role   = $_GET['role']   ?? null;
    $status = $_GET['status'] ?? 'pending';

    $sql    = 'SELECT id, role, full_name, email, phone, approval_status, is_active, created_at FROM users WHERE role != "admin"';
    $params = [];

    if ($role) { $sql .= ' AND role = ?';            $params[] = $role;   }
    if ($status !== 'all') { $sql .= ' AND approval_status = ?'; $params[] = $status; }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    success($stmt->fetchAll());

} elseif ($method === 'PATCH') {
    if (!$id) error('User ID required');
    $body   = getBody();
    $action = $body['action'] ?? '';

    if (!in_array($action, ['approve', 'reject'])) error('Action must be approve or reject');

    $status = $action === 'approve' ? 'approved' : 'rejected';
    $db->prepare('UPDATE users SET approval_status = ? WHERE id = ?')->execute([$status, $id]);
    success(['id' => $id, 'approval_status' => $status]);

} else {
    error('Method not allowed', 405);
}
