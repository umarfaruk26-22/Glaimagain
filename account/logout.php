<?php
/**
 * GLAIMAGAIN - Account Logout Forwarder
 */
require_once __DIR__ . '/../config/config.php';
header('Location: ' . BASE_URL . 'logout.php');
exit;
