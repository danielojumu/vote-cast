<?php

namespace App\Middleware;

use App\Helpers\Security;
use App\Helpers\Response;

class CSRFMiddleware
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!Security::verifyCSRFToken($token)) {
                Response::json([
                    'success' => false,
                    'message' => 'CSRF Token validation failed. Please refresh the page and try again.'
                ], 403);
            }
        }
    }
}
