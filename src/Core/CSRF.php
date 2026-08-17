<?php

namespace App\Core;

class CSRF
{
    public static function generateToken(): string
    {
        Session::start();
        $token = Session::get('csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set('csrf_token', $token);
        }
        return $token;
    }

    public static function validateToken(?string $token): bool
    {
        if (!$token) {
            return false;
        }
        Session::start();
        $savedToken = Session::get('csrf_token');
        return $savedToken && hash_equals($savedToken, $token);
    }
}
