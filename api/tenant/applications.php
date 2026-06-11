<?php
require_once __DIR__ . '/../helpers.php';
$user   = requireAuth(['tenant']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare('
        SELECT rr.*, u.label AS unit_label, u.monthly_rent,
               p.name AS property_name, p.city, p.cover_image_url
        FROM rental_requests rr
        JOIN units u      ON u.id = rr.unit_id
        JOIN properties p ON p.id = u.property_id
        WHERE rr.applicant_email = ?
        ORDER BY rr.applied_at DESC
    ');
    $stmt->execute([$user['email']]);
    success($stmt->fetchAll());

} elseif ($method === 'POST') {
    $body   = getBody();
    $unitId = (int)($body['unit_id'] ?? 0);
    $moveIn = $body['requested_move_in'] ?? date('Y-m-d', strtotime('+1 month'));
    $months = max(1, (int)($body['lease_months'] ?? 12));
    $occ    = trim($body['occupation'] ?? '');
    $summ   = trim($body['profile_summary'] ?? '');

    if (!$unitId) error('Unit ID is required');

    // Check unit is still available
    $stmt = $db->prepare('SELECT id FROM units WHERE id=? AND is_occupied=0');
    $stmt->execute([$unitId]);
    if (!$stmt->fetch()) error('This unit is no longer available');

    // Prevent duplicate pending applications
    $stmt = $db->prepare('SELECT id FROM rental_requests WHERE unit_id=? AND applicant_email=? AND status IN("pending","in_review")');
    $stmt->execute([$unitId, $user['email']]);
    if ($stmt->fetch()) error('You already have a pending application for this unit');

    // Fetch phone from users table
    $uStmt = $db->prepare('SELECT phone FROM users WHERE id=?');
    $uStmt->execute([$user['id']]);
    $phone = $uStmt->fetchColumn() ?: '';

    $db->prepare('INSERT INTO rental_requests (unit_id,applicant_name,applicant_email,applicant_phone,occupation,profile_summary,requested_move_in,lease_months) VALUES (?,?,?,?,?,?,?,?)')->execute([$unitId,$user['name'],$user['email'],$phone,$occ,$summ,$moveIn,$months]);
    success(['id' => (int)$db->lastInsertId()]);

} else {
    error('Method not allowed', 405);
}
