<?php

namespace App\Models;

use PDO;

class Position extends Model
{
    protected static string $table = 'positions';

    public static function getActiveOrdered(): array
    {
        $stmt = static::db()->query("
            SELECT * FROM positions 
            WHERE active = 1 
            ORDER BY display_order ASC, name ASC
        ");
        return $stmt->fetchAll();
    }
}
