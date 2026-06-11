<?php
require_once __DIR__ . '/../helpers.php';
$admin  = requireAuth(['admin']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

if ($method === 'GET') {
    $status = $_GET['status'] ?? null;
    $sql    = 'SELECT ltr.*, u.full_name AS landlord_name, u.email AS landlord_email FROM landlord_tech_requests ltr JOIN users u ON u.id=ltr.landlord_id';
    $params = [];
    if ($status) { $sql .= ' WHERE ltr.status=?'; $params[] = $status; }
    $sql .= ' ORDER BY ltr.submitted_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    success($stmt->fetchAll());

} elseif ($method === 'PATCH') {
    if (!$id) error('Request ID required');
    $body      = getBody();
    $action    = $body['action'] ?? '';
    $adminNote = trim($body['admin_note'] ?? '');

    if (!in_array($action, ['approve', 'reject'])) error('Invalid action');
    $status = $action === 'approve' ? 'approved' : 'rejected';

    $db->prepare('UPDATE landlord_tech_requests SET status=?, admin_note=?, decided_at=NOW() WHERE id=?')->execute([$status, $adminNote, $id]);
    success(['id' => $id, 'status' => $status]);

} else {
    error('Method not allowed', 405);
}
