<?php

namespace App\Repository;

use App\Core\Database;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM janbrunclik_vykaz_prace_uzivatele WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM janbrunclik_vykaz_prace_uzivatele WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT id, jmeno, prijmeni, email, telefon, role, created_at FROM janbrunclik_vykaz_prace_uzivatele ORDER BY prijmeni, jmeno");
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO janbrunclik_vykaz_prace_uzivatele (jmeno, prijmeni, email, telefon, heslo, role)
            VALUES (:jmeno, :prijmeni, :email, :telefon, :heslo, :role)
        ");
        $stmt->execute([
            'jmeno' => $data['jmeno'],
            'prijmeni' => $data['prijmeni'],
            'email' => $data['email'],
            'telefon' => $data['telefon'] ?? null,
            'heslo' => $data['heslo'],
            'role' => $data['role'] ?? 'employee'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [
            'jmeno = :jmeno',
            'prijmeni = :prijmeni',
            'email = :email',
            'telefon = :telefon',
            'role = :role'
        ];
        
        $params = [
            'id' => $id,
            'jmeno' => $data['jmeno'],
            'prijmeni' => $data['prijmeni'],
            'email' => $data['email'],
            'telefon' => $data['telefon'] ?? null,
            'role' => $data['role'] ?? 'employee'
        ];

        if (!empty($data['heslo'])) {
            $fields[] = 'heslo = :heslo';
            $params['heslo'] = $data['heslo'];
        }

        $sql = "UPDATE janbrunclik_vykaz_prace_uzivatele SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM janbrunclik_vykaz_prace_uzivatele WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getShiftCount(int $id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM janbrunclik_vykaz_prace_pracovni_vykaz WHERE id_zamestnance = :id");
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn();
    }
}
