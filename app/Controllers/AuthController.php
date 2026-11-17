<?php

namespace App\Controllers;

use App\Helpers\Session;
use App\Helpers\Security;
use App\Helpers\Response;
use App\Models\Admin;
use App\Models\AuditLog;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        Session::init();
        if (Session::has('admin_id')) {
            Response::redirect('/admin/dashboard');
        }

        $this->render('admin/login', [
            'csrf_token' => Security::generateCSRFToken(),
            'error' => Session::flash('error')
        ]);
    }

    public function login(): void
    {
        $email = strtolower(trim(Security::sanitize($_POST['email'] ?? '')));
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            Response::json([
                'success' => false,
                'message' => 'Please enter both your email address and password.'
            ], 400);
        }

        $admin = Admin::findByEmail($email);

        if (!$admin || !password_verify($password, $admin['password'])) {
            AuditLog::log("Failed login attempt for email: {$email}", "Security System");
            Response::json([
                'success' => false,
                'message' => 'Invalid admin credentials.'
            ], 401);
        }

        // Regenerate session ID for security
        Session::regenerate();
        Session::set('admin_id', $admin['id']);
        Session::set('admin_name', $admin['fullname']);
        Session::set('admin_email', $admin['email']);
        Session::set('admin_role', $admin['role']);
        Session::set('last_activity', time());

        if ($remember) {
            // Set remember token cookie if requested (30 days)
            setcookie('admin_remember', base64_encode($admin['email']), time() + (86400 * 30), "/", "", true, true);
        }

        AuditLog::log("Admin logged in: {$admin['fullname']} ({$admin['email']})", "Admin Portal");

        Response::json([
            'success' => true,
            'message' => 'Login successful. Redirecting to dashboard...',
            'redirect' => '/admin/dashboard'
        ]);
    }

    public function logout(): void
    {
        Session::init();
        $adminName = Session::get('admin_name', 'Admin');
        AuditLog::log("Admin logged out: {$adminName}", "Admin Portal");
        
        Session::destroy();
        if (isset($_COOKIE['admin_remember'])) {
            setcookie('admin_remember', '', time() - 3600, "/");
        }

        Response::redirect('/admin/login');
    }
}
