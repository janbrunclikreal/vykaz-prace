<?php

namespace App\Controller;

use App\Core\CSRF;
use App\Core\Session;
use App\Repository\UserRepository;

class AuthController extends BaseController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    public function login(): void
    {
        $data = $this->getJsonBody();
        $email = trim($data['email'] ?? '');
        $password = $data['heslo'] ?? '';

        if (empty($email) || empty($password)) {
            $this->error("Vyplňte email a heslo");
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user['heslo'])) {
            $this->error("Nesprávné přihlašovací údaje");
        }

        Session::login($user);

        $this->json([
            'message' => 'Přihlášení úspěšné',
            'user' => [
                'id' => $user['id'],
                'jmeno' => $user['jmeno'],
                'prijmeni' => $user['prijmeni'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'csrf_token' => CSRF::generateToken()
        ]);
    }

    public function logout(): void
    {
        $this->requireAuth();
        // logout nevyžaduje striktní CSRF v tomto případě, ale můžeme ho zkontrolovat
        Session::destroy();
        $this->json(['message' => 'Odhlášení úspěšné']);
    }

    public function me(): void
    {
        if (Session::isLoggedIn()) {
            $user = $this->userRepository->findById(Session::getUserId());
            if ($user) {
                $this->json([
                    'logged_in' => true,
                    'user' => [
                        'id' => $user['id'],
                        'jmeno' => $user['jmeno'],
                        'prijmeni' => $user['prijmeni'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ],
                    'csrf_token' => CSRF::generateToken()
                ]);
                return;
            }
        }
        $this->json([
            'logged_in' => false,
            'csrf_token' => CSRF::generateToken() // Generujeme i pro nepřihlášené pro login formulář
        ]);
    }
}
