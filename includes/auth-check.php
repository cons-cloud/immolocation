<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prefix check
$auth_prefix = '';
if (file_exists('includes/config.php')) {
    $auth_prefix = '';
} elseif (file_exists('../includes/config.php')) {
    $auth_prefix = '../';
} elseif (file_exists('../../includes/config.php')) {
    $auth_prefix = '../../';
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $auth_prefix . "page/connexion.php");
    exit();
}

if (isset($required_role) && $_SESSION['type_compte'] !== $required_role) {
    // If accessing admin area as client, redirect to client space, etc.
    $redirect = match($_SESSION['type_compte']) {
        'admin'        => $auth_prefix . 'page/dash/admin.php',
        'proprietaire' => $auth_prefix . 'page/dash/proprio.php',
        default        => $auth_prefix . 'page/dash/client.php',
    };
    header("Location: $redirect");
    exit();
}
?>
