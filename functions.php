<?php
require_once __DIR__ . '/db_config.php';
session_start();

/**
 * Check if logged in
 */
function is_logged_in(){
    return isset($_SESSION['user_id']);
}

/**
 * Attempt login (username or phone)
 */
function attempt_login($identifier, $password){
    global $conn;
    $sql = "SELECT * FROM users WHERE username=? OR phone=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if($res && password_verify($password, $res['password'])){
        $_SESSION['user_id'] = $res['id'];
        $_SESSION['username'] = $res['username'];
        return true;
    }
    return false;
}

/**
 * Check for an active subscription for a user
 */
function get_active_subscription($user_id){
    global $conn;
    $sql = "SELECT s.*, p.name AS plan_name, p.duration_minutes, p.price
            FROM subscriptions s
            JOIN plans p ON s.plan_id = p.id
            WHERE s.user_id = ? AND s.status='active' AND s.end_time > NOW()
            ORDER BY s.end_time DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ?: null;
}

/**
 * Create subscription record
 */
function create_subscription($user_id, $plan_id){
    global $conn;
    // get plan duration
    $sql = "SELECT duration_minutes FROM plans WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $plan_id);
    $stmt->execute();
    $plan = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if(!$plan) return false;

    $start = date('Y-m-d H:i:s');
    $end_ts = strtotime("+{$plan['duration_minutes']} minutes", strtotime($start));
    $end = date('Y-m-d H:i:s', $end_ts);

    $ins = $conn->prepare("INSERT INTO subscriptions (user_id, plan_id, start_time, end_time) VALUES (?, ?, ?, ?)");
    $ins->bind_param('iiss', $user_id, $plan_id, $start, $end);
    $ok = $ins->execute();
    $ins->close();

    return $ok;
}
