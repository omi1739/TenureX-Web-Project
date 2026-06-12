<?php
require_once __DIR__ . '/../helpers.php';
$user   = requireAuth(['landlord']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

if ($method === 'GET') {
    $status = $_GET['status'] ?? null;
    $sql    = '
        SELECT rr.*, u.label AS unit_label, u.monthly_rent, p.name AS property_name
        FROM rental_requests rr
        JOIN units u      ON u.id = rr.unit_id
        JOIN properties p ON p.id = u.property_id
        WHERE p.owner_id = ?
    ';
    $params = [$user['id']];
    if ($id)     { $sql .= ' AND rr.id=?';     $params[] = $id; }
    if ($status) { $sql .= ' AND rr.status=?'; $params[] = $status; }
    $sql .= ' ORDER BY rr.applied_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    success($stmt->fetchAll());

} elseif ($method === 'PATCH') {
    if (!$id) error('Request ID required');
    $body   = getBody();
    $action = $body['action'] ?? '';

    $map = ['accept' => 'accepted', 'reject' => 'rejected', 'in_review' => 'in_review'];
    if (!isset($map[$action])) error('Invalid action');

    // Verify ownership
    $stmt = $db->prepare('
        SELECT rr.* FROM rental_requests rr
        JOIN units u ON u.id=rr.unit_id
        JOIN properties p ON p.id=u.property_id
        WHERE rr.id=? AND p.owner_id=?
    ');
    $stmt->execute([$id, $user['id']]);
    $req = $stmt->fetch();
    if (!$req) error('Request not found', 404);

    $newStatus = $map[$action];
    $db->prepare('UPDATE rental_requests SET status=?, decided_at=NOW() WHERE id=?')->execute([$newStatus, $id]);

    if ($action === 'accept') {
        // Find or create tenant user
        $stmt = $db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$req['applicant_email']]);
        $tRow = $stmt->fetch();

        if (!$tRow) {
            $tmpHash = password_hash('tenant123', PASSWORD_BCRYPT);
            $db->prepare('INSERT INTO users (role,full_name,email,phone,password_hash,approval_status) VALUES ("tenant",?,?,?,?,"approved")')->execute([$req['applicant_name'],$req['applicant_email'],$req['applicant_phone']??'',$tmpHash]);
            $tenantId = (int)$db->lastInsertId();
        } else {
            $tenantId = (int)$tRow['id'];
        }

        // Get monthly rent
        $rent      = (float)$db->prepare('SELECT monthly_rent FROM units WHERE id=?')->execute([$req['unit_id']]) ? 0 : 0;
        $rentStmt  = $db->prepare('SELECT monthly_rent FROM units WHERE id=?');
        $rentStmt->execute([$req['unit_id']]);
        $rent      = (float)($rentStmt->fetchColumn() ?: 0);

        $start = $req['requested_move_in'] ?? date('Y-m-d');
        $end   = date('Y-m-d', strtotime('+' . ($req['lease_months'] ?? 12) . ' months', strtotime($start)));

        $db->prepare('INSERT INTO leases (unit_id,tenant_user_id,rental_request_id,start_date,end_date,monthly_rent) VALUES (?,?,?,?,?,?)')->execute([$req['unit_id'],$tenantId,$id,$start,$end,$rent]);
        $leaseId = (int)$db->lastInsertId();

        // Create upcoming payment for next month
        $nextDue = date('Y-m-01', strtotime('+1 month'));
        $db->prepare('INSERT INTO payments (lease_id,amount,due_date,status) VALUES (?,?,?,"upcoming")')->execute([$leaseId,$rent,$nextDue]);

        // Mark unit occupied
        $db->prepare('UPDATE units SET is_occupied=1 WHERE id=?')->execute([$req['unit_id']]);
    }

    success(['id' => $id, 'status' => $newStatus]);

} else {
    error('Method not allowed', 405);
}
