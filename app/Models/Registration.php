<?php

namespace App\Models;

use PDO;

class Registration extends Model
{
    protected static string $table = 'registrations';

    public static function create(array $data): int
    {
        $stmt = static::db()->prepare("
            INSERT INTO registrations (fullname, phone, email, graduation_year, id_type, id_value)
            VALUES (:fullname, :phone, :email, :graduation_year, :id_type, :id_value)
        ");
        $stmt->execute([
            ':fullname' => $data['fullname'],
            ':phone' => $data['phone'],
            ':email' => strtolower(trim($data['email'])),
            ':graduation_year' => (int)$data['graduation_year'],
            ':id_type' => $data['id_type'],
            ':id_value' => $data['id_value'],
        ]);
        return (int)static::db()->lastInsertId();
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM registrations WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $res = $stmt->fetch();
        return $res ?: null;
    }
}
