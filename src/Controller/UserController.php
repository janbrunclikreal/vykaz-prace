<?php

namespace App\Controller;

use App\Core\Session;
use App\Repository\UserRepository;

class UserController extends BaseController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    public function list(): void
    {
        // Seznam uživatelů pro filtr potřebuje i employee, ale bez citlivých údajů
        $this->requireAuth();
        
        $users = $this->userRepository->findAll();
        
        // Pokud nejsme admin, vrátíme jen základní data (id, jméno, příjmení) pro filtr
        if (!Session::isAdmin()) {
            $users = array_map(function ($u) {
                return [
                    'id' => $u['id'],
                    'jmeno' => $u['jmeno'],
                    'prijmeni' => $u['prijmeni']
                ];
            }, $users);
        }

        $this->json(['users' => $users]);
    }

    public function show(array $params): void
    {
        $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            $this->error("Uživatel nebyl nalezen", 404);
        }

        unset($user['heslo']); // Bezpečné odstranění hashe hesla
        $this->json(['user' => $user]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $data = $this->getJsonBody();
        $jmeno = trim($data['jmeno'] ?? '');
        $prijmeni = trim($data['prijmeni'] ?? '');
        $email = trim($data['email'] ?? '');
        $telefon = trim($data['telefon'] ?? '');
        $heslo = $data['heslo'] ?? '';
        $role = trim($data['role'] ?? 'employee');

        if (empty($jmeno) || empty($prijmeni) || empty($email) || empty($heslo)) {
            $this->error("Jméno, příjmení, email a heslo jsou povinné údaje");
        }

        // Ověření unikátnosti e-mailu
        if ($this->userRepository->findByEmail($email)) {
            $this->error("Uživatel s tímto e-mailem již existuje");
        }

        $hashedPassword = password_hash($heslo, PASSWORD_DEFAULT);

        $id = $this->userRepository->create([
            'jmeno' => $jmeno,
            'prijmeni' => $prijmeni,
            'email' => $email,
            'telefon' => $telefon,
            'heslo' => $hashedPassword,
            'role' => $role
        ]);

        $this->json([
            'message' => 'Uživatel úspěšně vytvořen',
            'id' => $id
        ]);
    }

    public function update(array $params): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $id = (int)($params['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            $this->error("Uživatel nebyl nalezen", 404);
        }

        $data = $this->getJsonBody();
        $jmeno = trim($data['jmeno'] ?? $user['jmeno']);
        $prijmeni = trim($data['prijmeni'] ?? $user['prijmeni']);
        $email = trim($data['email'] ?? $user['email']);
        $telefon = trim($data['telefon'] ?? $user['telefon']);
        $heslo = $data['heslo'] ?? '';
        $role = trim($data['role'] ?? $user['role']);

        if (empty($jmeno) || empty($prijmeni) || empty($email)) {
            $this->error("Jméno, příjmení a email jsou povinné údaje");
        }

        // Ověření unikátnosti e-mailu, pokud se mění
        if ($email !== $user['email']) {
            if ($this->userRepository->findByEmail($email)) {
                $this->error("Uživatel s tímto e-mailem již existuje");
            }
        }

        $updateData = [
            'jmeno' => $jmeno,
            'prijmeni' => $prijmeni,
            'email' => $email,
            'telefon' => $telefon,
            'role' => $role
        ];

        if (!empty($heslo)) {
            $updateData['heslo'] = password_hash($heslo, PASSWORD_DEFAULT);
        }

        $this->userRepository->update($id, $updateData);
        $this->json(['message' => 'Uživatel úspěšně aktualizován']);
    }

    public function delete(array $params): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $id = (int)($params['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            $this->error("Uživatel nebyl nalezen", 404);
        }

        // Zamezení smazání sebe sama
        if ($id === Session::getUserId()) {
            $this->error("Nemůžete smazat svůj vlastní účet");
        }

        // Kontrola, zda uživatel nemá směny
        if ($this->userRepository->getShiftCount($id) > 0) {
            $this->error("Nelze smazat uživatele, který má přiřazené směny. Nejprve smažte jeho směny.");
        }

        $this->userRepository->delete($id);
        $this->json(['message' => 'Uživatel úspěšně smazán']);
    }
}
