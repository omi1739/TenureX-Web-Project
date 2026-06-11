<?php
require_once __DIR__ . '/../helpers.php';
$user     = requireAuth();
$db       = getDB();
$method   = $_SERVER['REQUEST_METHOD'];
$threadId = (int)($_GET['thread_id'] ?? 0);

if (!$threadId) error('thread_id is required');

// Verify participation
$stmt = $db->prepare('SELECT 1 FROM message_thread_participants WHERE thread_id=? AND user_id=?');
$stmt->execute([$threadId, $user['id']]);
if (!$stmt->fetch()) error('You are not a participant in this thread', 403);

if ($method === 'GET') {
    // Mark incoming as read first
    $db->prepare('UPDATE messages SET read_at=NOW() WHERE thread_id=? AND sender_id!=? AND read_at IS NULL')->execute([$threadId,$user['id']]);

    $stmt = $db->prepare('
        SELECT m.*, u.full_name AS sender_name, u.role AS sender_role
        FROM messages m JOIN users u ON u.id=m.sender_id
        WHERE m.thread_id=?
        ORDER BY m.sent_at ASC
    ');
    $stmt->execute([$threadId]);
    success($stmt->fetchAll());

} elseif ($method === 'POST') {
    $body    = getBody();
    $message = trim($body['message'] ?? '');
    if (!$message) error('Message cannot be empty');

    $db->prepare('INSERT INTO messages (thread_id,sender_id,body) VALUES (?,?,?)')->execute([$threadId,$user['id'],$message]);
    $msgId = (int)$db->lastInsertId();
    $db->prepare('UPDATE message_threads SET last_message_at=NOW() WHERE id=?')->execute([$threadId]);

    success(['id' => $msgId, 'sender_name' => $user['name'], 'sent_at' => date('Y-m-d H:i:s')]);

} else {
    error('Method not allowed', 405);
}
