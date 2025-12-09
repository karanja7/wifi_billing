<?php
/**
 * Simple Rate Limiting for Redemption Attempts
 * Prevents brute force attacks on voucher redemption
 */
class RateLimiter {
    private $conn;
    private $table = 'rate_limits';
    private $max_attempts = 10;
    private $time_window = 3600; // 1 hour in seconds
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->create_table();
    }
    
    /**
     * Create rate limit tracking table if it doesn't exist
     */
    private function create_table() {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            endpoint VARCHAR(100) NOT NULL,
            attempts INT DEFAULT 1,
            first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ip_endpoint (ip_address, endpoint),
            INDEX idx_last_attempt (last_attempt)
        )";
        $this->conn->query($sql);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }
    
    /**
     * Check if client has exceeded rate limit
     */
    public function is_limited($endpoint = 'redeem') {
        $ip = $this->get_client_ip();
        
        // Clean up old entries
        $this->cleanup_old_entries();
        
        // Check if IP has recent attempts
        $stmt = $this->conn->prepare(
            "SELECT attempts FROM rate_limits 
             WHERE ip_address = ? AND endpoint = ? 
             AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $stmt->bind_param('ssi', $ip, $endpoint, $this->time_window);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            return $result['attempts'] >= $this->max_attempts;
        }
        
        return false;
    }
    
    /**
     * Record an attempt
     */
    public function record_attempt($endpoint = 'redeem') {
        $ip = $this->get_client_ip();
        
        $stmt = $this->conn->prepare(
            "INSERT INTO rate_limits (ip_address, endpoint, attempts) 
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE 
             attempts = IF(last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND), attempts + 1, 1),
             last_attempt = NOW()"
        );
        $stmt->bind_param('ssi', $ip, $endpoint, $this->time_window);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Reset attempts for an IP
     */
    public function reset($endpoint = 'redeem') {
        $ip = $this->get_client_ip();
        
        $stmt = $this->conn->prepare(
            "DELETE FROM rate_limits WHERE ip_address = ? AND endpoint = ?"
        );
        $stmt->bind_param('ss', $ip, $endpoint);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get remaining attempts
     */
    public function get_remaining_attempts($endpoint = 'redeem') {
        $ip = $this->get_client_ip();
        
        $stmt = $this->conn->prepare(
            "SELECT attempts FROM rate_limits 
             WHERE ip_address = ? AND endpoint = ? 
             AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $stmt->bind_param('ssi', $ip, $endpoint, $this->time_window);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            return max(0, $this->max_attempts - $result['attempts']);
        }
        
        return $this->max_attempts;
    }
    
    /**
     * Clean up old entries
     */
    private function cleanup_old_entries() {
        $this->conn->query(
            "DELETE FROM rate_limits 
             WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
    }
    
    /**
     * Get time until reset (in seconds)
     */
    public function get_reset_time($endpoint = 'redeem') {
        $ip = $this->get_client_ip();
        
        $stmt = $this->conn->prepare(
            "SELECT CEIL(TIMESTAMPDIFF(SECOND, last_attempt, DATE_ADD(last_attempt, INTERVAL ? SECOND))) as reset_seconds
             FROM rate_limits 
             WHERE ip_address = ? AND endpoint = ?"
        );
        $stmt->bind_param('iss', $this->time_window, $ip, $endpoint);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result ? max(1, $result['reset_seconds']) : 0;
    }
}
