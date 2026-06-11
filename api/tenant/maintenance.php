<?php
require_once __DIR__ . '/../helpers.php';
$user   = requireAuth(['tenant']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare('
            SELECT mr.*, p.name AS property_name, u.label AS unit_label,
                   tr.status AS tech_status, tr.scheduled_for, t.full_name AS tech_name
            FROM maintenance_requests mr
            JOIN properties p ON p.id = mr.property_id
            LEFT JOIN units u ON u.id = mr.unit_id
            LEFT JOIN technician_requests tr ON tr.maintenance_id = mr.id AND tr.status != "cancelled"
            LEFT JOIN technicians t ON t.id = tr.technician_id
            WHERE mr.id=? AND mr.reported_by=?
        ');
        $stmt->execute([$id, $user['id']]);
        success($stmt->fetch());
    } else {
        $stmt = $db->prepare('
            SELECT mr.*, p.name AS property_name, u.label AS unit_label
            FROM maintenance_requests mr
            JOIN properties p ON p.id = mr.property_id
            LEFT JOIN units u ON u.id = mr.unit_id
            WHERE mr.reported_by=?
            ORDER BY mr.reported_on DESC
        ');
        $stmt->execute([$user['id']]);
        success($stmt->fetchAll());
    }

} elseif ($method === 'POST') {
    $body     = getBody();
    $title    = trim($body['title']       ?? '');
    $desc     = trim($body['description'] ?? '');
    $category = $body['category'] ?? 'other';
    $priority = $body['priority'] ?? 'medium';

    if (!$title) error('Title is required');

    // Find tenant's active lease to get property/unit
    $stmt = $db->prepare('SELECT l.unit_id, u.property_id FROM leases l JOIN units u ON u.id=l.unit_id WHERE l.tenant_user_id=? AND l.status="active" LIMIT 1');
    $stmt->execute([$user['id']]);
    $lease = $stmt->fetch();
    if (!$lease) error('No active lease found. You must have an active lease to submit maintenance requests.');

    $db->prepare('INSERT INTO maintenance_requests (property_id,unit_id,reported_by,reporter_name,reporter_email,title,description,category,priority) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$lease['property_id'],$lease['unit_id'],$user['id'],$user['name'],$user['email'],$title,$desc,$category,$priority]);
    success(['id' => (int)$db->lastInsertId()]);

} else {
    error('Method not allowed', 405);
}
