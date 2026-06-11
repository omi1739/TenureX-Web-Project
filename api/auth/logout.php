<?php
require_once __DIR__ . '/../helpers.php';

$_SESSION = [];
session_destroy();
success(null, 'Logged out successfully');
