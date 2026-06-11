<?php
require_once __DIR__ . '/../helpers.php';
$user   = requireAuth(['technician']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

// Get technician profile linked to this user
$stmt = $db->prepare('SELECT id FROM technicians WHERE user_id=? LIMIT 1');
$stmt->execute([$user['id']]);
$tech = $stmt->fetch();
if (!$tech) error('Technician profile not found', 404);
$techId = $tech['id'];

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare('
            SELECT tr.*, mr.title, mr.description, mr.category, mr.priority,
                   mr.status AS mr_status, mr.reporter_name, mr.reporter_email,
                   p.name AS property_name, p.address_line, p.city,
                   u.label AS unit_label,
                   lu.full_name AS landlord_name, lu.phone AS landlord_phone
            FROM technician_requests tr
            JOIN maintenance_requests mr ON mr.id = tr.maintenance_id
            JOIN properties p   ON p.id = mr.property_id
            LEFT JOIN units u   ON u.id = mr.unit_id
            JOIN users lu       ON lu.id = p.owner_id
            WHERE tr.id=? AND tr.technician_id=?
        ');
        $stmt->execute([$id, $techId]);
        success($stmt->fetch());
    } else {
        $status = $_GET['status'] ?? null;
        $sql    = '
            SELECT tr.*, mr.title, mr.category, mr.priority,
                   p.name AS property_name, u.label AS unit_label
            FROM technician_requests tr
            JOIN maintenance_requests mr ON mr.id = tr.maintenance_id
            JOIN properties p ON p.id = mr.property_id
            LEFT JOIN units u ON u.id = mr.unit_id
            WHERE tr.technician_id=?
        ';
        $params = [$techId];
        if ($status) { $sql .= ' AND tr.status=?'; $params[] = $status; }
        $sql .= ' ORDER BY tr.created_at DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        success($stmt->fetchAll());
    }

} elseif ($method === 'PATCH') {
    if (!$id) error('Task ID required');
    $body   = getBody();
    $sets   = []; $params = [];
    if (isset($body['status'])) {
        if (!in_array($body['status'], ['dispatched','completed','cancelled'])) error('Invalid status');
        $sets[] = 'status=?'; $params[] = $body['status'];
    }
    if (isset($body['notes']))        { $sets[] = 'notes=?';         $params[] = $body['notes']; }
    if (isset($body['cost_estimate'])){ $sets[] = 'cost_estimate=?'; $params[] = (float)$body['cost_estimate']; }

    if (!$sets) error('Nothing to update');
    $params[] = $id; $params[] = $techId;
    $db->prepare('UPDATE technician_requests SET '.implode(',',$sets).' WHERE id=? AND technician_id=?')->execute($params);

    if (($body['status'] ?? '') === 'completed') {
        $db->prepare('UPDATE maintenance_requests mr JOIN technician_requests tr ON tr.maintenance_id=mr.id SET mr.status="resolved", mr.resolved_on=NOW() WHERE tr.id=?')->execute([$id]);
    }
    success(['id' => $id]);

} else {
    error('Method not allowed', 405);
}
