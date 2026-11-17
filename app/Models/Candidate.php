<?php

namespace App\Models;

use PDO;

class Candidate extends Model
{
    protected static string $table = 'candidates';

    public static function getByPosition(int $positionId): array
    {
        $stmt = static::db()->prepare("
            SELECT * FROM candidates 
            WHERE position_id = :pos_id AND active = 1 
            ORDER BY display_order ASC, fullname ASC
        ");
        $stmt->execute([':pos_id' => $positionId]);
        return $stmt->fetchAll();
    }

    public static function getAllWithPosition(): array
    {
        $stmt = static::db()->query("
            SELECT c.*, p.name as position_name 
            FROM candidates c
            JOIN positions p ON c.position_id = p.id
            ORDER BY p.display_order ASC, c.display_order ASC
        ");
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = static::db()->prepare("
            INSERT INTO candidates (position_id, fullname, manifesto, profile, university_photo, recent_photo, display_order, active)
            VALUES (:pos_id, :fullname, :manifesto, :profile, :uni_photo, :rec_photo, :disp_order, :active)
        ");
        $stmt->execute([
            ':pos_id' => $data['position_id'],
            ':fullname' => $data['fullname'],
            ':manifesto' => $data['manifesto'],
            ':profile' => $data['profile'] ?? '',
            ':uni_photo' => $data['university_photo'] ?? null,
            ':rec_photo' => $data['recent_photo'] ?? null,
            ':disp_order' => $data['display_order'] ?? 1,
            ':active' => $data['active'] ?? 1,
        ]);
        return (int)static::db()->lastInsertId();
    }

    public static function updateCandidate(int $id, array $data): bool
    {
        $stmt = static::db()->prepare("
            UPDATE candidates 
            SET position_id = :pos_id,
                fullname = :fullname,
                manifesto = :manifesto,
                profile = :profile,
                university_photo = COALESCE(:uni_photo, university_photo),
                recent_photo = COALESCE(:rec_photo, recent_photo),
                display_order = :disp_order,
                active = :active
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':pos_id' => $data['position_id'],
            ':fullname' => $data['fullname'],
            ':manifesto' => $data['manifesto'],
            ':profile' => $data['profile'] ?? '',
            ':uni_photo' => $data['university_photo'] ?? null,
            ':rec_photo' => $data['recent_photo'] ?? null,
            ':disp_order' => $data['display_order'] ?? 1,
            ':active' => $data['active'] ?? 1,
        ]);
    }
}
