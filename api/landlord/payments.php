<?php
require_once __DIR__ . '/../helpers.php';
$user = requireAuth(['landlord']);
$db   = getDB();

$stmt = $db->prepare('
    SELECT pay.*, l.monthly_rent, l.start_date, l.end_date,
           u.label AS unit_label, p.name AS property_name,
           tu.full_name AS tenant_name, tu.email AS tenant_email, tu.phone AS tenant_phone
    FROM payments pay
    JOIN leases l   ON l.id = pay.lease_id
    JOIN units u    ON u.id = l.unit_id
    JOIN properties p ON p.id = u.property_id
    LEFT JOIN users tu ON tu.id = l.tenant_user_id
    WHERE p.owner_id = ?
    ORDER BY pay.due_date DESC
');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

// Summary stats
$total   = array_sum(array_column(array_filter($rows, fn($r) => $r['status']==='paid'), 'amount'));
$pending = count(array_filter($rows, fn($r) => $r['status']==='upcoming'));
$overdue = count(array_filter($rows, fn($r) => $r['status']==='overdue'));

success(['payments' => $rows, 'stats' => ['total_collected' => $total, 'pending_count' => $pending, 'overdue_count' => $overdue]]);
