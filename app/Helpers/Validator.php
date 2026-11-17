<?php

namespace App\Helpers;

use App\Config\Database;
use PDO;

class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $customName = null): self
    {
        $name = $customName ?? ucfirst($field);
        if (empty($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "{$name} is required.";
        }
        return $this;
    }

    public function email(string $field): self
    {
        if (!empty($this->data[$field])) {
            $email = filter_var($this->data[$field], FILTER_VALIDATE_EMAIL);
            if (!$email) {
                $this->errors[$field] = "Please enter a valid email address.";
            }
        }
        return $this;
    }

    public function phone(string $field): self
    {
        if (!empty($this->data[$field])) {
            $phone = preg_replace('/[^0-9+]/', '', $this->data[$field]);
            if (strlen($phone) < 8 || strlen($phone) > 20) {
                $this->errors[$field] = "Please enter a valid phone number (8-20 digits).";
            }
        }
        return $this;
    }

    public function unique(string $field, string $table, string $column): self
    {
        if (!empty($this->data[$field]) && !isset($this->errors[$field])) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = :val");
            $stmt->execute([':val' => trim($this->data[$field])]);
            
            if ($stmt->fetchColumn() > 0) {
                $this->errors[$field] = "This " . str_replace('_', ' ', $field) . " is already registered in the system.";
            }
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
