<?php

namespace App\Models;

use App\Config\Database;
use PDO;

abstract class Model
{
    protected static string $table = '';

    protected static function db(): PDO
    {
        return Database::getConnection();
    }

    public static function all(): array
    {
        $table = static::$table;
        $stmt = static::db()->query("SELECT * FROM {$table} ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $table = static::$table;
        $stmt = static::db()->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function count(): int
    {
        $table = static::$table;
        $stmt = static::db()->query("SELECT COUNT(*) FROM {$table}");
        return (int)$stmt->fetchColumn();
    }

    public static function delete(int $id): bool
    {
        $table = static::$table;
        $stmt = static::db()->prepare("DELETE FROM {$table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
