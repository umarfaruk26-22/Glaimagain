<?php
/**
 * GLAIMAGAIN - Customer Sign Out
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
setFlashMessage('info', 'You have been signed out of your GLAIMAGAIN account.');
header('Location: ' . BASE_URL . 'login.php');
exit;
