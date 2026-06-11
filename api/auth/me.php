<?php
require_once __DIR__ . '/../helpers.php';

$user = currentUser();
if (!$user) error('Not authenticated', 401);
success($user);
