<?php
require_once __DIR__ . '/../helpers.php';
// Public — no auth required
$db = getDB();

$city    = trim($_GET['city']    ?? '');
$minRent = (float)($_GET['min_rent'] ?? 0);
$maxRent = (float)($_GET['max_rent'] ?? 999999);
$type    = trim($_GET['type'] ?? '');

$sql    = '
    SELECT p.id AS property_id, p.name AS property_name,
           p.address_line, p.city, p.cover_image_url,
           u.id AS unit_id, u.label AS unit_label, u.unit_type,
           u.bedrooms, u.bathrooms, u.area_sqft, u.monthly_rent
    FROM properties p
    JOIN units u ON u.property_id = p.id
    WHERE p.status = "active"
      AND u.is_occupied = 0
      AND u.monthly_rent BETWEEN ? AND ?
';
$params = [$minRent, $maxRent];

if ($city)  { $sql .= ' AND p.city LIKE ?';     $params[] = "%$city%"; }
if ($type)  { $sql .= ' AND u.unit_type = ?';   $params[] = $type;     }

$sql .= ' ORDER BY p.created_at DESC LIMIT 50';

$stmt = $db->prepare($sql);
$stmt->execute($params);
success($stmt->fetchAll());
