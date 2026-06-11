<?php
require_once __DIR__ . '/../helpers.php';
$user   = requireAuth(['landlord']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

if ($method === 'GET') {
    $sql    = '
        SELECT mr.*, p.name AS property_name, u.label AS unit_label,
               ru.full_name AS reporter_display_name,
               tr.status AS tech_status, tr.scheduled_for, tr.cost_estimate,
               t.full_name AS tech_name
        FROM maintenance_requests mr
        JOIN properties p   ON p.id = mr.property_id
        LEFT JOIN units u   ON u.id = mr.unit_id
        LEFT JOIN users ru  ON ru.id = mr.reported_by
        LEFT JOIN technician_requests tr ON tr.maintenance_id = mr.id AND tr.status != "cancelled"
        LEFT JOIN technicians t ON t.id = tr.technician_id
        WHERE p.owner_id = ?
    ';
    $params = [$user['id']];
    if ($_GET['status'] ?? null) { $sql .= ' AND mr.status=?'; $params[] = $_GET['status']; }
    $sql .= ' ORDER BY mr.reported_on DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    success($stmt->fetchAll());

} elseif ($method === 'PATCH') {
    if (!$id) error('ID required');
    $body   = getBody();
    $status = $body['status'] ?? null;
    if (!in_array($status, ['open','in_progress','resolved','closed'])) error('Invalid status');

    $db->prepare('UPDATE maintenance_requests SET status=? WHERE id=?')->execute([$status, $id]);
    success(['id' => $id, 'status' => $status]);

} elseif ($method === 'POST') {
    // Landlord dispatching a technician to a maintenance job
    $body     = getBody();
    $mrId     = (int)($body['maintenance_id'] ?? 0);
    $techId   = (int)($body['technician_id']  ?? 0);
    $schedFor = $body['scheduled_for'] ?? null;
    $notes    = trim($body['notes'] ?? '');

    if (!$mrId || !$techId) error('maintenance_id and technician_id are required');

    // Verify maintenance belongs to landlord
    $stmt = $db->prepare('SELECT mr.id FROM maintenance_requests mr JOIN properties p ON p.id=mr.property_id WHERE mr.id=? AND p.owner_id=?');
    $stmt->execute([$mrId, $user['id']]);
    if (!$stmt->fetch()) error('Maintenance request not found', 404);

    $db->prepare('INSERT INTO technician_requests (maintenance_id,technician_id,scheduled_for,notes,status) VALUES (?,?,?,?,"dispatched")')->execute([$mrId,$techId,$schedFor,$notes]);
    $db->prepare('UPDATE maintenance_requests SET status="in_progress" WHERE id=?')->execute([$mrId]);

    success(['id' => (int)$db->lastInsertId()]);

} else {
    error('Method not allowed', 405);
}
