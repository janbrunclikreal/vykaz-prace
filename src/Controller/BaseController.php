<?php

namespace App\Controller;

use App\Core\CSRF;
use App\Core\Session;

abstract class BaseController
{
    public function __construct()
    {
        Session::start();
    }

    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    protected function error(string $message, int $statusCode = 400): void
    {
        $this->json(['error' => $message], $statusCode);
    }

    protected function getJsonBody(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return is_array($data) ? $data : [];
    }

    protected function validateCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!CSRF::validateToken($token)) {
            $this->error("Neplatný CSRF token", 403);
        }
    }

    protected function requireAuth(): void
    {
        if (!Session::isLoggedIn()) {
            $this->error("Neautorizovaný přístup", 401);
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!Session::isAdmin()) {
            $this->error("Přístup odepřen: Vyžadována role administrátora", 403);
        }
    }
}
