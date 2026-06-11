<?php
require_once __DIR__ . '/../helpers.php';
$user   = requireAuth(['tenant']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare('
        SELECT pay.*, u.label AS unit_label, p.name AS property_name
        FROM payments pay
        JOIN leases l   ON l.id = pay.lease_id
        JOIN units u    ON u.id = l.unit_id
        JOIN properties p ON p.id = u.property_id
        WHERE l.tenant_user_id = ?
        ORDER BY pay.due_date DESC
    ');
    $stmt->execute([$user['id']]);
    success($stmt->fetchAll());

} elseif ($method === 'POST') {
    $body      = getBody();
    $paymentId = (int)($body['payment_id'] ?? 0);
    $methodPay = $body['method'] ?? 'card';

    if (!$paymentId) error('payment_id is required');

    // Verify belongs to this tenant and is upcoming
    $stmt = $db->prepare('
        SELECT pay.* FROM payments pay
        JOIN leases l ON l.id = pay.lease_id
        WHERE pay.id=? AND l.tenant_user_id=? AND pay.status="upcoming"
    ');
    $stmt->execute([$paymentId, $user['id']]);
    if (!$stmt->fetch()) error('Payment not found or already paid');

    $ref = 'TXN-' . strtoupper(bin2hex(random_bytes(4)));
    $db->prepare('UPDATE payments SET status="paid", paid_at=NOW(), method=?, reference=? WHERE id=?')->execute([$methodPay,$ref,$paymentId]);

    // Create next month's upcoming payment
    $paid = $db->prepare('SELECT lease_id, amount, due_date FROM payments WHERE id=?');
    $paid->execute([$paymentId]);
    $p = $paid->fetch();
    $nextDue = date('Y-m-01', strtotime('+1 month', strtotime($p['due_date'])));
    $db->prepare('INSERT INTO payments (lease_id,amount,due_date,status) VALUES (?,?,?,"upcoming")')->execute([$p['lease_id'],$p['amount'],$nextDue]);

    success(['reference' => $ref, 'status' => 'paid']);

} else {
    error('Method not allowed', 405);
}
