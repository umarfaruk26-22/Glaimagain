<?php
/**
 * GLAIMAGAIN - Admin Portal Sign Out
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

logoutAdmin();
setFlashMessage('info', 'You have been safely signed out of the GLAIMAGAIN Command Suite.');
header('Location: ' . BASE_URL . 'admin/login.php');
exit;
