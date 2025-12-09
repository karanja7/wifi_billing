<?php
require_once '../db_config.php';

session_start();

// Check if admin is logged in
function check_admin_auth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Logout function
function admin_logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get current admin info
function get_admin_info() {
    global $conn;
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT id, username, email FROM admins WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['admin_id']);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $admin;
}

// Session timeout (30 minutes)
if (isset($_SESSION['admin_login_time']) && time() - $_SESSION['admin_login_time'] > 1800) {
    admin_logout();
}
