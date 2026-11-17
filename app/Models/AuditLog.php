<?php

namespace App\Models;

use App\Helpers\Security;
use PDO;

class AuditLog extends Model
{
    protected static string $table = 'audit_logs';

    public static function log(string $action, string $user = 'System'): bool
    {
        $ip = Security::getClientIP();
        $stmt = static::db()->prepare("
            INSERT INTO audit_logs (user, action, ip_address)
            VALUES (:user, :action, :ip)
        ");
        return $stmt->execute([
            ':user' => $user,
            ':action' => $action,
            ':ip' => $ip,
        ]);
    }

    public static function getRecent(int $limit = 50): array
    {
        $stmt = static::db()->prepare("
            SELECT * FROM audit_logs 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
