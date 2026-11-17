<?php

namespace App\Middleware;

use App\Helpers\Session;
use App\Helpers\Response;

class AuthMiddleware
{
    public static function handle(): void
    {
        Session::init();
        
        // Session timeout check (30 minutes of inactivity)
        $timeout = 1800; // 30 minutes
        if (Session::has('last_activity') && (time() - Session::get('last_activity') > $timeout)) {
            Session::destroy();
            Session::init();
            Session::flash('error', 'Session expired due to inactivity. Please log in again.');
            Response::redirect('/admin/login');
        }
        
        Session::set('last_activity', time());

        if (!Session::has('admin_id')) {
            Session::flash('error', 'Please log in to access the administrator panel.');
            Response::redirect('/admin/login');
        }
    }
}
