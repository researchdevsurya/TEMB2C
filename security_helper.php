<?php
require_once 'db.php';

class SecurityHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Check if IP is allowed to perform action
     * Returns true if allowed, false if blocked
     */
    public function checkRateLimit(string $ip, string $action, int $maxAttempts = 5, int $blockDurationMinutes = 15): bool {
        // cleanup old blocks
        // $this->pdo->exec("DELETE FROM rate_limits WHERE blocked_until < NOW()");

        $stmt = $this->pdo->prepare("SELECT * FROM rate_limits WHERE ip_address = ? AND action_type = ?");
        $stmt->execute([$ip, $action]);
        $record = $stmt->fetch();

        if ($record) {
            // Check if currently blocked
            if ($record['blocked_until'] && new DateTime($record['blocked_until']) > new DateTime()) {
                return false;
            }

            // Check attempts
            if ($record['attempt_count'] >= $maxAttempts) {
                // Block user
                $blockTime = (new DateTime())->modify("+$blockDurationMinutes minutes")->format('Y-m-d H:i:s');
                $update = $this->pdo->prepare("UPDATE rate_limits SET blocked_until = ? WHERE id = ?");
                $update->execute([$blockTime, $record['id']]);
                return false;
            }
        }

        return true;
    }

    /**
     * Log a failed attempt
     */
    public function logFailure(string $ip, string $action) {
        $stmt = $this->pdo->prepare("SELECT id, attempt_count FROM rate_limits WHERE ip_address = ? AND action_type = ?");
        $stmt->execute([$ip, $action]);
        $record = $stmt->fetch();

        if ($record) {
            $stmt = $this->pdo->prepare("UPDATE rate_limits SET attempt_count = attempt_count + 1, last_attempt = NOW() WHERE id = ?");
            $stmt->execute([$record['id']]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO rate_limits (ip_address, action_type, attempt_count, last_attempt) VALUES (?, ?, 1, NOW())");
            $stmt->execute([$ip, $action]);
        }
    }

    /**
     * Clear attempts on success
     */
    public function clearAttempts(string $ip, string $action) {
        $stmt = $this->pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND action_type = ?");
        $stmt->execute([$ip, $action]);
    }
    
    /**
     * Get Client IP
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
        return $_SERVER['REMOTE_ADDR'];
    }
}
?>
