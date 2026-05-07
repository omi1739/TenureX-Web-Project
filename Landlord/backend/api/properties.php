<?php
/**
 * Sample REST endpoint — Properties CRUD.
 *
 *   GET  /backend/api/properties.php           list owner's properties
 *   GET  /backend/api/properties.php?id=42     single property
 *   POST /backend/api/properties.php           create new property (JSON body)
 *
 * This file is a TEMPLATE — copy the pattern for tenants, leases, etc.
 */
declare(strict_types=1);

require __DIR__ . '/../db.php';

// In a real app this would come from the session
$ownerId = (int) ($_SESSION['user_id'] ?? 1);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $stmt = db()->prepare(
                'SELECT * FROM properties WHERE id = ? AND owner_id = ?'
            );
            $stmt->execute([(int) $_GET['id'], $ownerId]);
            $row = $stmt->fetch();
            if (!$row) json_response(['error' => 'Not found'], 404);
            json_response($row);
        }

        $stmt = db()->prepare(
            'SELECT id, name, address_line, city, total_units, monthly_value, status
             FROM properties
             WHERE owner_id = ?
             ORDER BY created_at DESC'
        );
        $stmt->execute([$ownerId]);
        json_response($stmt->fetchAll());
    }

    if ($method === 'POST') {
        $body = read_json_body();

        // Validate required fields server-side (never trust the browser)
        foreach (['name', 'address_line', 'city'] as $field) {
            if (empty($body[$field])) {
                json_response(['error' => "Missing field: $field"], 422);
            }
        }

        $stmt = db()->prepare(
            'INSERT INTO properties
                (owner_id, name, address_line, city, region, country, postcode,
                 cover_image_url, total_units, monthly_value)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $ownerId,
            $body['name'],
            $body['address_line'],
            $body['city'],
            $body['region']         ?? null,
            $body['country']        ?? null,
            $body['postcode']       ?? null,
            $body['cover_image_url']?? null,
            (int) ($body['total_units']   ?? 1),
            $body['monthly_value']  ?? null,
        ]);

        json_response(['id' => (int) db()->lastInsertId()], 201);
    }

    json_response(['error' => 'Method not allowed'], 405);

} catch (Throwable $e) {
    json_response(['error' => 'Server error', 'message' => $e->getMessage()], 500);
}
