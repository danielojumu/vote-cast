<?php

namespace App\Models;

use PDO;

class Voter extends Model
{
    protected static string $table = 'voters';

    public static function create(int $registrationId, string $email, string $token): int
    {
        $stmt = static::db()->prepare("
            INSERT INTO voters (registration_id, email, voting_token, token_sent, token_sent_at, has_voted)
            VALUES (:reg_id, :email, :token, 1, NOW(), 0)
        ");
        $stmt->execute([
            ':reg_id' => $registrationId,
            ':email' => strtolower(trim($email)),
            ':token' => strtoupper(trim($token)),
        ]);
        return (int)static::db()->lastInsertId();
    }

    public static function findByEmailAndToken(string $email, string $token): ?array
    {
        $stmt = static::db()->prepare("
            SELECT v.*, r.fullname 
            FROM voters v
            JOIN registrations r ON v.registration_id = r.id
            WHERE LOWER(v.email) = :email AND UPPER(v.voting_token) = :token
            LIMIT 1
        ");
        $stmt->execute([
            ':email' => strtolower(trim($email)),
            ':token' => strtoupper(trim($token))
        ]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function markVoted(int $voterId): bool
    {
        $stmt = static::db()->prepare("
            UPDATE voters 
            SET has_voted = 1, voted_at = NOW() 
            WHERE id = :id AND has_voted = 0
        ");
        return $stmt->execute([':id' => $voterId]);
    }

    public static function countVoted(): int
    {
        $stmt = static::db()->query("SELECT COUNT(*) FROM voters WHERE has_voted = 1");
        return (int)$stmt->fetchColumn();
    }

    public static function countRemaining(): int
    {
        $stmt = static::db()->query("SELECT COUNT(*) FROM voters WHERE has_voted = 0");
        return (int)$stmt->fetchColumn();
    }

    public static function getTurnoutPercentage(): float
    {
        $total = static::count();
        if ($total === 0) return 0.0;
        $voted = static::countVoted();
        return round(($voted / $total) * 100, 1);
    }
}
