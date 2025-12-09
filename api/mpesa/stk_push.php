<?php
require_once '../../db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$phone = $_POST['phone'] ?? '';
$plan_id = $_POST['plan_id'] ?? '';
$mac_address = $_POST['mac_address'] ?? '';
$redirect_url = $_POST['redirect_url'] ?? '';

// Validate inputs
if (empty($phone) || !preg_match('/^[0-9]{9}$/', $phone) || empty($plan_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid phone or plan']);
    exit;
}

// Add country code to phone
$phone_with_country = '254' . $phone;

// Get plan details
$plan_stmt = $conn->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
$plan_stmt->bind_param('i', $plan_id);
$plan_stmt->execute();
$plan = $plan_stmt->get_result()->fetch_assoc();
$plan_stmt->close();

if (!$plan) {
    http_response_code(400);
    echo json_encode(['error' => 'Plan not found']);
    exit;
}

// Get M-PESA config
$config_stmt = $conn->prepare("SELECT * FROM mpesa_config WHERE is_active = 1 LIMIT 1");
$config_stmt->execute();
$config = $config_stmt->get_result()->fetch_assoc();
$config_stmt->close();

if (!$config) {
    http_response_code(500);
    echo json_encode(['error' => 'M-PESA not configured']);
    exit;
}

// Create or get user
$user_stmt = $conn->prepare("SELECT id FROM users WHERE phone_number = ?");
$user_stmt->bind_param('s', $phone_with_country);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_id = $user['id'];
} else {
    // Create new user
    $insert_stmt = $conn->prepare("INSERT INTO users (phone_number, created_at) VALUES (?, NOW())");
    $insert_stmt->bind_param('s', $phone_with_country);
    $insert_stmt->execute();
    $user_id = $insert_stmt->insert_id;
    $insert_stmt->close();
}
$user_stmt->close();

// Create payment record
$amount = intval($plan['price']);
$account_ref = 'PLAN' . $plan_id;
$description = 'WiFi ' . $plan['name'];

$payment_stmt = $conn->prepare(
    "INSERT INTO payments (user_id, phone_number, plan_id, amount, status, created_at) 
     VALUES (?, ?, ?, ?, 'pending', NOW())"
);
$payment_stmt->bind_param('isii', $user_id, $phone_with_country, $plan_id, $amount);
$payment_stmt->execute();
$payment_id = $payment_stmt->insert_id;
$payment_stmt->close();

// Prepare M-PESA STK Push
$timestamp = date('YmdHis');
$password = base64_encode($config['business_shortcode'] . $config['passkey'] . $timestamp);

// Determine environment
$endpoint = $config['test_mode'] 
    ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
    : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

$auth_endpoint = $config['test_mode']
    ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
    : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

// Get access token
$curl_auth = curl_init();
curl_setopt_array($curl_auth, [
    CURLOPT_URL => $auth_endpoint,
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => $config['consumer_key'] . ':' . $config['consumer_secret'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
]);

$response_auth = curl_exec($curl_auth);
$curl_auth_errno = curl_errno($curl_auth);
$curl_auth_error = curl_error($curl_auth);
curl_close($curl_auth);

if ($curl_auth_errno) {
    log_access($user_id, $mac_address, 'stkpush_auth_error', 'error', $curl_auth_error, $conn);
    http_response_code(500);
    echo json_encode(['error' => 'Authentication failed']);
    exit;
}

$auth_data = json_decode($response_auth);
$access_token = $auth_data->access_token ?? null;

if (!$access_token) {
    log_access($user_id, $mac_address, 'stkpush_no_token', 'error', 'No access token', $conn);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get access token']);
    exit;
}

// Prepare STK Push request
$mpesa_request = [
    'BusinessShortCode' => $config['business_shortcode'],
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone_with_country,
    'PartyB' => $config['business_shortcode'],
    'PhoneNumber' => $phone_with_country,
    'CallBackURL' => $config['callback_url'],
    'AccountReference' => $account_ref,
    'TransactionDesc' => $description,
];

// Send STK Push
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $endpoint,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
    ],
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($mpesa_request),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($curl);
$curl_errno = curl_errno($curl);
$curl_error = curl_error($curl);
curl_close($curl);

if ($curl_errno) {
    log_access($user_id, $mac_address, 'stkpush_request_error', 'error', $curl_error, $conn);
    http_response_code(500);
    echo json_encode(['error' => 'STK Push failed']);
    exit;
}

$result = json_decode($response);

// Store M-PESA response
if ($result && isset($result->CheckoutRequestID)) {
    $checkout_id = $result->CheckoutRequestID;
    $merchant_id = $result->MerchantRequestID ?? null;
    
    $update_stmt = $conn->prepare(
        "UPDATE payments SET mpesa_checkout_request_id = ?, mpesa_merchant_request_id = ?, mpesa_response = ? WHERE id = ?"
    );
    $json_response = json_encode($result);
    $update_stmt->bind_param('sssi', $checkout_id, $merchant_id, $json_response, $payment_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    log_access($user_id, $mac_address, 'stkpush_success', 'success', json_encode([
        'payment_id' => $payment_id,
        'checkout_id' => $checkout_id,
        'amount' => $amount
    ]), $conn);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'STK Push sent. Check your phone for M-PESA prompt.',
        'payment_id' => $payment_id,
        'checkout_request_id' => $checkout_id,
    ]);
} else {
    log_access($user_id, $mac_address, 'stkpush_failed', 'error', json_encode($result), $conn);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $result->errorMessage ?? 'STK Push failed',
        'details' => $result
    ]);
}

function log_access($user_id, $mac_address, $action, $status, $details, $conn) {
    $log_stmt = $conn->prepare(
        "INSERT INTO access_logs (user_id, mac_address, ip_address, action, status, details, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, NOW())"
    );
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param('isssss', $user_id, $mac_address, $ip, $action, $status, $details);
    $log_stmt->execute();
    $log_stmt->close();
}
?>
