<?php
require_once __DIR__ . '/config/db.php';
// Clear all session data and issue a fresh session id
$_SESSION = [];
session_regenerate_id(true);
flash('success', 'You have been logged out.');
redirect('login.php');
