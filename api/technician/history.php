<?php
require_once __DIR__ . '/../helpers.php';
$user = requireAuth(['technician']);
$db   = getDB();

$stmt = $db->prepare('SELECT id FROM technicians WHERE user_id=? LIMIT 1');
$stmt->execute([$user['id']]);
$tech = $stmt->fetch();
if (!$tech) error('Technician profile not found', 404);

$stmt = $db->prepare('
    SELECT tr.*, mr.title, mr.category,
           p.name AS property_name, u.label AS unit_label
    FROM technician_requests tr
    JOIN maintenance_requests mr ON mr.id = tr.maintenance_id
    JOIN properties p  ON p.id = mr.property_id
    LEFT JOIN units u  ON u.id = mr.unit_id
    WHERE tr.technician_id=? AND tr.status="completed"
    ORDER BY tr.created_at DESC
');
$stmt->execute([$tech['id']]);
$jobs = $stmt->fetchAll();

success([
    'jobs'  => $jobs,
    'stats' => [
        'total_jobs'    => count($jobs),
        'total_revenue' => array_sum(array_column($jobs, 'cost_estimate')),
    ],
]);
