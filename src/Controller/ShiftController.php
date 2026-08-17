<?php

namespace App\Controller;

use App\Core\Session;
use App\Repository\ShiftRepository;
use App\Service\ShiftService;

class ShiftController extends BaseController
{
    private ShiftRepository $shiftRepository;
    private ShiftService $shiftService;

    public function __construct()
    {
        parent::__construct();
        $this->shiftRepository = new ShiftRepository();
        $this->shiftService = new ShiftService();
    }

    public function list(): void
    {
        $this->requireAuth();

        $employeeId = filter_input(INPUT_GET, 'employee_id', FILTER_VALIDATE_INT);
        $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT) ?: (int)date('n');
        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int)date('Y');

        $currentUserId = Session::getUserId();

        // Zaměstnanec smí vidět pouze své vlastní směny
        if (!Session::isAdmin()) {
            $employeeId = $currentUserId;
        } elseif ($employeeId === null || $employeeId <= 0) {
            $employeeId = $currentUserId;
        }

        $shifts = $this->shiftRepository->findByEmployeeAndMonth($employeeId, $month, $year);

        // Obohatíme data o spočítané hodiny pro klientský JS
        foreach ($shifts as &$shift) {
            $shift['celkem_hodin'] = $this->shiftService->calculateHours($shift['cas_zacatku'], $shift['cas_konce']);
        }

        $this->json([
            'shifts' => $shifts,
            'filters' => [
                'employee_id' => $employeeId,
                'month' => $month,
                'year' => $year
            ]
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $data = $this->getJsonBody();
        $employeeId = (int)($data['id_zamestnance'] ?? 0);
        $currentUserId = Session::getUserId();

        // Obyčejný zaměstnanec smí vytvářet směny jen pro sebe
        if (!Session::isAdmin()) {
            $employeeId = $currentUserId;
        }

        $datum = trim($data['datum'] ?? '');
        $startTime = trim($data['cas_zacatku'] ?? '');
        $endTime = trim($data['cas_konce'] ?? '');
        $poznamka = trim($data['poznamka'] ?? '');
        $noni = !empty($data['noni']);

        if (empty($employeeId) || empty($datum) || empty($startTime) || empty($endTime)) {
            $this->error("Všechna povinná pole musí být vyplněna (Zaměstnanec, Datum, Začátek, Konec)");
        }

        $id = $this->shiftRepository->create([
            'id_zamestnance' => $employeeId,
            'datum' => $datum,
            'cas_zacatku' => $startTime,
            'cas_konce' => $endTime,
            'poznamka' => $poznamka,
            'noni' => $noni
        ]);

        $this->json([
            'message' => 'Směna úspěšně přidána',
            'id' => $id
        ]);
    }

    public function update(array $params): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $id = (int)($params['id'] ?? 0);
        $shift = $this->shiftRepository->findById($id);

        if (!$shift) {
            $this->error("Směna nebyla nalezena", 404);
        }

        // Obyčejný zaměstnanec smí upravovat jen své směny
        if (!Session::isAdmin() && $shift['id_zamestnance'] !== Session::getUserId()) {
            $this->error("Přístup odepřen", 403);
        }

        $data = $this->getJsonBody();
        $employeeId = (int)($data['id_zamestnance'] ?? $shift['id_zamestnance']);

        if (!Session::isAdmin()) {
            $employeeId = Session::getUserId();
        }

        $datum = trim($data['datum'] ?? $shift['datum']);
        $startTime = trim($data['cas_zacatku'] ?? $shift['cas_zacatku']);
        $endTime = trim($data['cas_konce'] ?? $shift['cas_konce']);
        $poznamka = trim($data['poznamka'] ?? $shift['poznamka']);
        $noni = isset($data['noni']) ? !empty($data['noni']) : (bool)$shift['noni'];

        if (empty($employeeId) || empty($datum) || empty($startTime) || empty($endTime)) {
            $this->error("Povinná pole nesmí být prázdná");
        }

        $this->shiftRepository->update($id, [
            'id_zamestnance' => $employeeId,
            'datum' => $datum,
            'cas_zacatku' => $startTime,
            'cas_konce' => $endTime,
            'poznamka' => $poznamka,
            'noni' => $noni
        ]);

        $this->json(['message' => 'Směna úspěšně aktualizována']);
    }

    public function delete(array $params): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $id = (int)($params['id'] ?? 0);
        $shift = $this->shiftRepository->findById($id);

        if (!$shift) {
            $this->error("Směna nebyla nalezena", 404);
        }

        if (!Session::isAdmin() && $shift['id_zamestnance'] !== Session::getUserId()) {
            $this->error("Přístup odepřen", 403);
        }

        $this->shiftRepository->delete($id);
        $this->json(['message' => 'Směna úspěšně smazána']);
    }

    public function bulkSave(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $data = $this->getJsonBody();
        $employeeId = (int)($data['employee_id'] ?? 0);
        $shifts = $data['shifts'] ?? [];

        if (empty($employeeId)) {
            $this->error("ID zaměstnance je povinné");
        }

        if (!Session::isAdmin() && $employeeId !== Session::getUserId()) {
            $this->error("Můžete upravovat pouze své vlastní směny", 403);
        }

        if (!is_array($shifts)) {
            $this->error("Neplatná data směn");
        }

        $this->shiftService->saveBulk($employeeId, $shifts);
        $this->json(['message' => 'Hromadné uložení směn proběhlo úspěšně']);
    }
}
