<?php
/**
 * BUI Beta Set Alumni Election Voting System (VCAST)
 * Global Configuration & Database Connection Handler
 * For Hostinger Web Hosting & MySQL 8+
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Environment & Database Credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'vcast_db');
define('DB_USER', getenv('DB_USER') ?: 'vcast_user');
define('DB_PASS', getenv('DB_PASS') ?: 'SecretPassword123!');
define('DB_CHARSET', 'utf8mb4');

// Mailer Config (PHPMailer / Hostinger SMTP)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.hostinger.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 465);
define('SMTP_USER', getenv('SMTP_USER') ?: 'elections@vote.buibetaset.org');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'SmtpSecretPass2026!');
define('FROM_EMAIL', 'elections@vote.buibetaset.org');
define('FROM_NAME', 'BUI Beta Set Electoral Commission');

/**
 * Returns a PDO Database Connection Instance
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            // Safe fallback for demo / unconfigured database environments
            die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

/**
 * Helper to sanitize input strings
 */
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate 8-character unique alphanumeric voting token
 */
function generateVotingToken(): string {
    return strtoupper(substr(bin2hex(random_bytes(6)), 0, 8));
}

/**
 * Log System Audit Trail
 */
function logAudit(PDO $pdo, string $user, string $action): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (`user`, `action`, `ip_address`) VALUES (?, ?, ?)");
    $stmt->execute([$user, $action, $ip]);
}

/**
 * Send Voting Token via PHP mail() or SMTP
 */
function sendTokenEmail(string $toEmail, string $toName, string $token): bool {
    $subject = "Your Official Voting Token - BUI Beta Set Alumni Election";
    $message = "
    <html>
    <head>
        <title>BUI Beta Set Election Token</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #0f172a; color: #ffffff; padding: 20px; }
            .card { background-color: #1e293b; padding: 30px; border-radius: 12px; border: 1px solid #334155; max-width: 500px; margin: auto; text-align: center; }
            .token { font-size: 32px; font-weight: bold; letter-spacing: 4px; color: #fbbf24; background: #0f172a; padding: 15px; border-radius: 8px; margin: 20px 0; display: inline-block; border: 1px solid #d97706; }
            .footer { font-size: 12px; color: #94a3b8; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h2>BUI Beta Set Alumni Election</h2>
            <p>Hello <strong>" . htmlspecialchars($toName) . "</strong>,</p>
            <p>Your voter registration has been verified by the Electoral Commission. Use the single-use token below to cast your ballot:</p>
            <div class='token'>" . htmlspecialchars($token) . "</div>
            <p>Visit <a href='https://vote.buibetaset.org/vote.php' style='color:#38bdf8;'>vote.buibetaset.org/vote.php</a> to enter your ballot paper.</p>
            <div class='footer'>This token is confidential and single-use only. Do not share it.</div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";

    return @mail($toEmail, $subject, $message, $headers);
}
