<?php
require_once __DIR__ . '/../helpers.php';
$user   = requireAuth();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare('
        SELECT mt.id, mt.subject, mt.last_message_at,
               (SELECT m.body FROM messages m WHERE m.thread_id=mt.id ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
               (SELECT COUNT(*) FROM messages m WHERE m.thread_id=mt.id AND m.sender_id!=? AND m.read_at IS NULL) AS unread_count,
               GROUP_CONCAT(DISTINCT ou.full_name ORDER BY ou.id SEPARATOR ", ") AS participants
        FROM message_threads mt
        JOIN message_thread_participants mtp  ON mtp.thread_id=mt.id AND mtp.user_id=?
        JOIN message_thread_participants mtp2 ON mtp2.thread_id=mt.id
        JOIN users ou ON ou.id=mtp2.user_id AND ou.id != ?
        GROUP BY mt.id
        ORDER BY mt.last_message_at DESC
    ');
    $stmt->execute([$user['id'], $user['id'], $user['id']]);
    success($stmt->fetchAll());

} elseif ($method === 'POST') {
    $body        = getBody();
    $recipientId = (int)($body['recipient_id'] ?? 0);
    $subject     = trim($body['subject'] ?? 'Conversation');
    $message     = trim($body['message'] ?? '');

    if (!$recipientId || !$message) error('recipient_id and message are required');
    if ($recipientId === $user['id']) error('Cannot message yourself');

    // Reuse existing thread between same pair
    $stmt = $db->prepare('
        SELECT mt.id FROM message_threads mt
        JOIN message_thread_participants a ON a.thread_id=mt.id AND a.user_id=?
        JOIN message_thread_participants b ON b.thread_id=mt.id AND b.user_id=?
        LIMIT 1
    ');
    $stmt->execute([$user['id'], $recipientId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $threadId = $existing['id'];
    } else {
        $db->prepare('INSERT INTO message_threads (subject,last_message_at) VALUES (?,NOW())')->execute([$subject]);
        $threadId = (int)$db->lastInsertId();
        $db->prepare('INSERT INTO message_thread_participants (thread_id,user_id) VALUES (?,?),(?,?)')->execute([$threadId,$user['id'],$threadId,$recipientId]);
    }

    $db->prepare('INSERT INTO messages (thread_id,sender_id,body) VALUES (?,?,?)')->execute([$threadId,$user['id'],$message]);
    $db->prepare('UPDATE message_threads SET last_message_at=NOW() WHERE id=?')->execute([$threadId]);

    success(['thread_id' => $threadId]);

} else {
    error('Method not allowed', 405);
}
