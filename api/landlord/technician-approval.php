<?php
require_once __DIR__ . '/../helpers.php';
$user   = requireAuth(['landlord']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT * FROM landlord_tech_requests WHERE landlord_id=? ORDER BY submitted_at DESC');
    $stmt->execute([$user['id']]);
    success($stmt->fetchAll());

} elseif ($method === 'POST') {
    $body = getBody();
    $name = trim($body['full_name']   ?? '');
    $phone= trim($body['phone']       ?? '');
    $spec = trim($body['specialization'] ?? '');
    $nid  = trim($body['nid']         ?? '');
    $exp  = max(0, (int)($body['years_experience'] ?? 0));
    $note = trim($body['note']        ?? '');

    if (!$name || !$phone || !$spec || !$nid) error('Name, phone, specialization and NID are required');

    $db->prepare('INSERT INTO landlord_tech_requests (landlord_id,full_name,phone,specialization,nid,years_experience,note) VALUES (?,?,?,?,?,?,?)')->execute([$user['id'],$name,$phone,$spec,$nid,$exp,$note]);
    success(['id' => (int)$db->lastInsertId()]);

} else {
    error('Method not allowed', 405);
}
