<?php
require_once '../../db_config.php';

// Get the callback from Safaricom
$callback_data = file_get_contents('php://input');
$json_data = json_decode($callback_data, true);

// Log all callbacks
$log_file = '../../logs/mpesa_callbacks.log';
@mkdir('../../logs', 0755, true);
file_put_contents($log_file, date('Y-m-d H:i:s') . ' - ' . $callback_data . "\n", FILE_APPEND);

// Process the callback
if (isset($json_data['Body']['stkCallback'])) {
    $callback = $json_data['Body']['stkCallback'];
    $result_code = $callback['ResultCode'] ?? null;
    $checkout_id = $callback['CheckoutRequestID'] ?? null;
    $merchant_request_id = $callback['MerchantRequestID'] ?? null;
    $result_desc = $callback['ResultDesc'] ?? '';
    
    if (!$checkout_id) {
        http_response_code(400);
        echo json_encode(['error' => 'No checkout request ID']);
        exit;
    }
    
    // Find the payment
    $payment_stmt = $conn->prepare("SELECT * FROM payments WHERE mpesa_checkout_request_id = ?");
    $payment_stmt->bind_param('s', $checkout_id);
    $payment_stmt->execute();
    $payment = $payment_stmt->get_result()->fetch_assoc();
    $payment_stmt->close();
    
    if (!$payment) {
        http_response_code(400);
        echo json_encode(['error' => 'Payment not found']);
        exit;
    }
    
    // Update payment status based on result code
    if ($result_code == 0) {
        // Success
        $status = 'success';
        $paid_at = date('Y-m-d H:i:s');
        
        $update_stmt = $conn->prepare(
            "UPDATE payments SET status = ?, paid_at = ?, mpesa_response = ? WHERE id = ?"
        );
        $response_json = json_encode($callback);
        $update_stmt->bind_param('sssi', $status, $paid_at, $response_json, $payment['id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Create WiFi session
        $user_id = $payment['user_id'];
        $plan_id = $payment['plan_id'];
        $start_time = date('Y-m-d H:i:s');
        
        // Get plan duration
        $plan_stmt = $conn->prepare("SELECT duration_minutes FROM plans WHERE id = ?");
        $plan_stmt->bind_param('i', $plan_id);
        $plan_stmt->execute();
        $plan = $plan_stmt->get_result()->fetch_assoc();
        $plan_stmt->close();
        
        $end_time = date('Y-m-d H:i:s', strtotime('+' . $plan['duration_minutes'] . ' minutes'));
        
        // For now, we'll use a placeholder MAC (would come from device tracking)
        $mac_address = 'FF:FF:FF:FF:FF:FF'; // Placeholder
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        // Create session
        $session_stmt = $conn->prepare(
            "INSERT INTO sessions (user_id, payment_id, mac_address, ip_address, plan_id, start_time, end_time, status, router_status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 'pending', NOW())"
        );
        $session_stmt->bind_param('iisssss', $user_id, $payment['id'], $mac_address, $ip_address, $plan_id, $start_time, $end_time);
        $session_stmt->execute();
        $session_id = $session_stmt->insert_id;
        $session_stmt->close();
        
        // TODO: Call router API to authorize device
        // authorize_device_on_router($mac_address, $user_id, $plan['duration_minutes']);
        
        log_callback($user_id, 'payment_success', 'success', json_encode([
            'payment_id' => $payment['id'],
            'session_id' => $session_id,
            'amount' => $payment['amount'],
            'duration' => $plan['duration_minutes'] . ' minutes'
        ]), $conn);
        
    } else {
        // Failed or cancelled
        $status = $result_code == 1 ? 'cancelled' : 'failed';
        
        $update_stmt = $conn->prepare(
            "UPDATE payments SET status = ?, mpesa_response = ? WHERE id = ?"
        );
        $response_json = json_encode($callback);
        $update_stmt->bind_param('ssi', $status, $response_json, $payment['id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        log_callback($payment['user_id'], 'payment_' . $status, 'warning', json_encode([
            'result_code' => $result_code,
            'result_desc' => $result_desc,
            'amount' => $payment['amount']
        ]), $conn);
    }
}

// Respond to Safaricom
http_response_code(200);
echo json_encode(['success' => true]);

function log_callback($user_id, $action, $status, $details, $conn) {
    $log_stmt = $conn->prepare(
        "INSERT INTO access_logs (user_id, action, status, details, created_at) 
         VALUES (?, ?, ?, ?, NOW())"
    );
    $log_stmt->bind_param('isss', $user_id, $action, $status, $details);
    $log_stmt->execute();
    $log_stmt->close();
}
?>
