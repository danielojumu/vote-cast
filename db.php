<?php
/**
 * PDO Database Helper Connection
 */
require_once __DIR__ . '/config.php';

try {
    $db = getDB();
} catch (Exception $e) {
    $db = null;
}
