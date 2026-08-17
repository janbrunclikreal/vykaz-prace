<?php

namespace App\Repository;

use App\Core\Database;
use PDO;

class ShiftRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM janbrunclik_vykaz_prace_pracovni_vykaz WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $shift = $stmt->fetch();
        return $shift ?: null;
    }

    public function findByEmployeeAndMonth(int $employeeId, int $month, int $year): array
    {
        $stmt = $this->db->prepare("
            SELECT pv.*, u.jmeno, u.prijmeni 
            FROM janbrunclik_vykaz_prace_pracovni_vykaz pv 
            LEFT JOIN janbrunclik_vykaz_prace_uzivatele u ON pv.id_zamestnance = u.id 
            WHERE pv.id_zamestnance = :employee_id 
              AND MONTH(pv.datum) = :month 
              AND YEAR(pv.datum) = :year 
            ORDER BY pv.datum ASC
        ");
        $stmt->execute([
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year
        ]);
        return $stmt->fetchAll();
    }

    public function findAllWithFilters(?int $employeeId = null, ?int $month = null, ?int $year = null): array
    {
        $sql = "
            SELECT pv.*, u.jmeno, u.prijmeni 
            FROM janbrunclik_vykaz_prace_pracovni_vykaz pv 
            LEFT JOIN janbrunclik_vykaz_prace_uzivatele u ON pv.id_zamestnance = u.id 
            WHERE 1=1
        ";
        $params = [];

        if ($employeeId !== null && $employeeId > 0) {
            $sql .= " AND pv.id_zamestnance = :employee_id";
            $params['employee_id'] = $employeeId;
        }

        if ($month !== null && $month > 0) {
            $sql .= " AND MONTH(pv.datum) = :month";
            $params['month'] = $month;
        }

        if ($year !== null && $year > 0) {
            $sql .= " AND YEAR(pv.datum) = :year";
            $params['year'] = $year;
        }

        $sql .= " ORDER BY pv.datum DESC, pv.cas_zacatku DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO janbrunclik_vykaz_prace_pracovni_vykaz (id_zamestnance, datum, cas_zacatku, cas_konce, poznamka, noni)
            VALUES (:employee_id, :datum, :cas_zacatku, :cas_konce, :poznamka, :noni)
        ");
        $stmt->execute([
            'employee_id' => $data['id_zamestnance'],
            'datum' => $data['datum'],
            'cas_zacatku' => $data['cas_zacatku'],
            'cas_konce' => $data['cas_konce'],
            'poznamka' => $data['poznamka'] ?? null,
            'noni' => $data['noni'] ? 1 : 0
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE janbrunclik_vykaz_prace_pracovni_vykaz 
            SET id_zamestnance = :employee_id,
                datum = :datum,
                cas_zacatku = :cas_zacatku,
                cas_konce = :cas_konce,
                poznamka = :poznamka,
                noni = :noni 
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'employee_id' => $data['id_zamestnance'],
            'datum' => $data['datum'],
            'cas_zacatku' => $data['cas_zacatku'],
            'cas_konce' => $data['cas_konce'],
            'poznamka' => $data['poznamka'] ?? null,
            'noni' => $data['noni'] ? 1 : 0
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM janbrunclik_vykaz_prace_pracovni_vykaz WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function deleteByEmployeeAndDate(int $employeeId, string $datum): bool
    {
        $stmt = $this->db->prepare("DELETE FROM janbrunclik_vykaz_prace_pracovni_vykaz WHERE id_zamestnance = :employee_id AND datum = :datum");
        return $stmt->execute([
            'employee_id' => $employeeId,
            'datum' => $datum
        ]);
    }

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollBack(): void
    {
        $this->db->rollBack();
    }
}
