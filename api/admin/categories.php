<?php
require_once __DIR__ . '/../helpers.php';
$admin  = requireAuth(['admin']);
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

if ($method === 'GET') {
    $stmt = $db->query('SELECT * FROM service_categories ORDER BY created_at DESC');
    success($stmt->fetchAll());

} elseif ($method === 'POST') {
    $body = getBody();
    $name = trim($body['name'] ?? '');
    $desc = trim($body['description'] ?? '');
    if (!$name) error('Category name is required');

    $db->prepare('INSERT INTO service_categories (name, description, created_by) VALUES (?,?,?)')->execute([$name, $desc, $admin['id']]);
    success(['id' => (int)$db->lastInsertId(), 'name' => $name]);

} elseif ($method === 'PUT') {
    if (!$id) error('Category ID required');
    $body   = getBody();
    $name   = trim($body['name'] ?? '');
    $desc   = trim($body['description'] ?? '');
    $active = isset($body['is_active']) ? (int)$body['is_active'] : 1;
    if (!$name) error('Category name is required');

    $db->prepare('UPDATE service_categories SET name=?, description=?, is_active=? WHERE id=?')->execute([$name, $desc, $active, $id]);
    success(['id' => $id]);

} elseif ($method === 'DELETE') {
    if (!$id) error('Category ID required');
    $db->prepare('DELETE FROM service_categories WHERE id=?')->execute([$id]);
    success(['id' => $id]);

} else {
    error('Method not allowed', 405);
}
