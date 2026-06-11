<?php
require_once __DIR__ . '/../helpers.php';
$user = requireAuth(['tenant']);
$db   = getDB();

$stmt = $db->prepare('
    SELECT l.*, u.label AS unit_label, u.area_sqft, u.bedrooms, u.bathrooms,
           p.name AS property_name, p.address_line, p.city, p.cover_image_url,
           lu.full_name AS landlord_name, lu.email AS landlord_email, lu.phone AS landlord_phone
    FROM leases l
    JOIN units u   ON u.id = l.unit_id
    JOIN properties p ON p.id = u.property_id
    JOIN users lu  ON lu.id = p.owner_id
    WHERE l.tenant_user_id = ? AND l.status = "active"
    ORDER BY l.created_at DESC LIMIT 1
');
$stmt->execute([$user['id']]);
$lease = $stmt->fetch();

if (!$lease) success(null, 'No active lease');

// All payments for this lease
$stmt = $db->prepare('SELECT * FROM payments WHERE lease_id=? ORDER BY due_date DESC');
$stmt->execute([$lease['id']]);
$lease['payments'] = $stmt->fetchAll();

// Next upcoming
$stmt = $db->prepare('SELECT * FROM payments WHERE lease_id=? AND status="upcoming" ORDER BY due_date ASC LIMIT 1');
$stmt->execute([$lease['id']]);
$lease['next_payment'] = $stmt->fetch() ?: null;

success($lease);
