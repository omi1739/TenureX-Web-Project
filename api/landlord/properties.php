<?php
require_once __DIR__ . '/../helpers.php';
$user   = requireAuth(['landlord']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM properties WHERE id=? AND owner_id=?');
        $stmt->execute([$id, $user['id']]);
        $prop = $stmt->fetch();
        if (!$prop) error('Property not found', 404);

        $stmt = $db->prepare('SELECT * FROM units WHERE property_id=?');
        $stmt->execute([$id]);
        $prop['units'] = $stmt->fetchAll();
        success($prop);
    } else {
        $stmt = $db->prepare('
            SELECT p.*, COUNT(u.id) AS unit_count, COALESCE(SUM(u.is_occupied),0) AS occupied_count
            FROM properties p LEFT JOIN units u ON u.property_id=p.id
            WHERE p.owner_id=?
            GROUP BY p.id ORDER BY p.created_at DESC
        ');
        $stmt->execute([$user['id']]);
        success($stmt->fetchAll());
    }

} elseif ($method === 'POST') {
    $body    = getBody();
    $name    = trim($body['name']         ?? '');
    $address = trim($body['address_line'] ?? '');
    $city    = trim($body['city']         ?? '');
    $monthly = (float)($body['monthly_value'] ?? 0);
    $units   = max(1, (int)($body['total_units'] ?? 1));
    $image   = trim($body['cover_image_url'] ?? '');

    if (!$name || !$address || !$city) error('Name, address and city are required');

    $db->prepare('INSERT INTO properties (owner_id,name,address_line,city,monthly_value,total_units,cover_image_url) VALUES (?,?,?,?,?,?,?)')->execute([$user['id'],$name,$address,$city,$monthly,$units,$image]);
    $propId = (int)$db->lastInsertId();

    // Auto-create first unit
    $db->prepare('INSERT INTO units (property_id,label,monthly_rent) VALUES (?,?,?)')->execute([$propId,'Unit 1',$monthly ?: 0]);

    success(['id' => $propId, 'name' => $name]);

} elseif (in_array($method, ['PUT','PATCH'])) {
    if (!$id) error('Property ID required');
    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM properties WHERE id=? AND owner_id=?');
    $stmt->execute([$id, $user['id']]);
    if (!$stmt->fetch()) error('Property not found', 404);

    $body   = getBody();
    $fields = ['name','address_line','city','region','postcode','monthly_value','total_units','status','cover_image_url'];
    $sets   = []; $params = [];
    foreach ($fields as $f) {
        if (array_key_exists($f, $body)) { $sets[] = "$f=?"; $params[] = $body[$f]; }
    }
    if ($sets) {
        $params[] = $id;
        $db->prepare('UPDATE properties SET ' . implode(',', $sets) . ' WHERE id=?')->execute($params);
    }
    success(['id' => $id]);

} elseif ($method === 'DELETE') {
    if (!$id) error('Property ID required');
    $db->prepare('DELETE FROM properties WHERE id=? AND owner_id=?')->execute([$id, $user['id']]);
    success(['id' => $id]);

} else {
    error('Method not allowed', 405);
}
