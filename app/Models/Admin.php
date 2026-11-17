<?php

namespace App\Models;

use PDO;

class Admin extends Model
{
    protected static string $table = 'admins';

    public static function findByEmail(string $email): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => trim($email)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = static::db()->prepare("
            INSERT INTO admins (fullname, email, password, role)
            VALUES (:fullname, :email, :password, :role)
        ");
        $stmt->execute([
            ':fullname' => $data['fullname'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':role' => $data['role'] ?? 'Electoral Commissioner',
        ]);
        return (int)static::db()->lastInsertId();
    }
}
