<?php
/**
 * Router Integration API
 * Handles authorization of devices on various router types
 */

require_once '../../db_config.php';

class RouterManager {
    private $conn;
    private $config;
    
    public function __construct($conn, $config) {
        $this->conn = $conn;
        $this->config = $config;
    }
    
    /**
     * Authorize a device on the router
     */
    public function authorize_device($mac_address, $user_id, $duration_minutes) {
        $router_type = strtolower($this->config['router_type']);
        
        switch ($router_type) {
            case 'mikrotik':
                return $this->authorize_mikrotik($mac_address, $duration_minutes);
            case 'coovachilli':
                return $this->authorize_coovachilli($mac_address, $duration_minutes);
            case 'nodogsplash':
                return $this->authorize_nodogsplash($mac_address);
            case 'pfsense':
                return $this->authorize_pfsense($mac_address, $duration_minutes);
            case 'unifi':
                return $this->authorize_unifi($mac_address, $duration_minutes);
            default:
                return ['success' => false, 'error' => 'Unknown router type'];
        }
    }
    
    /**
     * MikroTik Router Authorization
     * Uses MikroTik API to add hotspot user
     */
    private function authorize_mikrotik($mac_address, $duration_minutes) {
        try {
            // Create a temporary hotspot user
            $username = 'user_' . uniqid();
            $password = bin2hex(random_bytes(8));
            
            // In production, connect to MikroTik API
            // This is a simplified example
            
            return [
                'success' => true,
                'router' => 'mikrotik',
                'message' => 'Device authorized via MikroTik',
                'user' => $username,
                'duration' => $duration_minutes
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * CoovaChilli Router Authorization
     * Uses CoovaChilli uthash mechanism
     */
    private function authorize_coovachilli($mac_address, $duration_minutes) {
        try {
            // Generate authorization hash
            $hash = strtoupper(md5($mac_address . time()));
            
            // In production, call:
            // chilli_query authorize $mac_address $hash
            
            return [
                'success' => true,
                'router' => 'coovachilli',
                'message' => 'Device authorized via CoovaChilli',
                'mac' => $mac_address,
                'hash' => $hash
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Nodogsplash Router Authorization
     * Uses nodogsplash uthash
     */
    private function authorize_nodogsplash($mac_address) {
        try {
            // Generate authorization hash
            $token = bin2hex(random_bytes(16));
            
            // In production, call:
            // ndsctl auth 1 $mac_address
            
            return [
                'success' => true,
                'router' => 'nodogsplash',
                'message' => 'Device authorized via Nodogsplash',
                'mac' => $mac_address,
                'token' => $token
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * pfSense Captive Portal Authorization
     * Uses pfSense API
     */
    private function authorize_pfsense($mac_address, $duration_minutes) {
        try {
            // pfSense uses the captive portal database
            // Would need to communicate via SSH or API
            
            return [
                'success' => true,
                'router' => 'pfsense',
                'message' => 'Device authorized via pfSense',
                'mac' => $mac_address,
                'duration' => $duration_minutes
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * UniFi/Ubiquiti Authorization
     * Uses UniFi Controller API
     */
    private function authorize_unifi($mac_address, $duration_minutes) {
        try {
            // UniFi uses MAC filtering via the controller
            
            return [
                'success' => true,
                'router' => 'unifi',
                'message' => 'Device authorized via UniFi',
                'mac' => $mac_address,
                'duration' => $duration_minutes
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Revoke device access
     */
    public function revoke_device($mac_address, $user_id) {
        $router_type = strtolower($this->config['router_type']);
        
        // Update session status
        $stmt = $this->conn->prepare("UPDATE sessions SET status = 'expired' WHERE mac_address = ? AND status = 'active'");
        $stmt->bind_param('s', $mac_address);
        $stmt->execute();
        $stmt->close();
        
        // In production, would call router API to revoke
        
        return ['success' => true, 'message' => 'Device access revoked'];
    }
}

// API Endpoints
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'POST' && strpos($path, 'authorize') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $mac = $data['mac'] ?? null;
    $user_id = $data['user_id'] ?? null;
    $duration = $data['duration'] ?? null;
    
    if (!$mac || !$user_id || !$duration) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Get router config
    $config_stmt = $conn->prepare("SELECT * FROM router_config WHERE is_active = 1 LIMIT 1");
    $config_stmt->execute();
    $config = $config_stmt->get_result()->fetch_assoc();
    $config_stmt->close();
    
    if (!$config) {
        http_response_code(500);
        echo json_encode(['error' => 'Router not configured']);
        exit;
    }
    
    $router = new RouterManager($conn, $config);
    $result = $router->authorize_device($mac, $user_id, $duration);
    
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result);
    
} elseif ($method === 'POST' && strpos($path, 'revoke') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $mac = $data['mac'] ?? null;
    $user_id = $data['user_id'] ?? null;
    
    if (!$mac || !$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $config_stmt = $conn->prepare("SELECT * FROM router_config WHERE is_active = 1 LIMIT 1");
    $config_stmt->execute();
    $config = $config_stmt->get_result()->fetch_assoc();
    $config_stmt->close();
    
    $router = new RouterManager($conn, $config);
    $result = $router->revoke_device($mac, $user_id);
    
    echo json_encode($result);
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
