<?php

namespace App\Helpers;

class Security
{
    /**
     * Generate a cryptographically secure voting token (e.g., A8G2K9LQ)
     */
    public static function generateVotingToken(int $length = 8): string
    {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Exclude visually ambiguous characters like O, 0, I, 1
        $maxIndex = strlen($characters) - 1;
        $token = '';
        
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[random_int(0, $maxIndex)];
        }
        
        return $token;
    }

    /**
     * Escape output HTML to prevent XSS attacks
     */
    public static function escape(?string $data): string
    {
        if ($data === null) {
            return '';
        }
        return htmlspecialchars(trim($data), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitize general user input
     */
    public static function sanitize(string $data): string
    {
        return trim(strip_tags($data));
    }

    /**
     * Generate CSRF Token and store in session
     */
    public static function generateCSRFToken(): string
    {
        Session::init();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF Token
     */
    public static function verifyCSRFToken(?string $token): bool
    {
        Session::init();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get Client IP Address
     */
    public static function getClientIP(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP) ?: '127.0.0.1';
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return filter_var(trim($ips[0]), FILTER_VALIDATE_IP) ?: '127.0.0.1';
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
