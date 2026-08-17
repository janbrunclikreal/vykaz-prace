<?php

namespace App\Controller;

use App\Core\Session;
use App\Repository\UserRepository;
use App\Repository\ShiftRepository;
use App\Service\PdfService;

class PdfController extends BaseController
{
    private UserRepository $userRepository;
    private ShiftRepository $shiftRepository;
    private PdfService $pdfService;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->shiftRepository = new ShiftRepository();
        $this->pdfService = new PdfService();
    }

    public function generate(): void
    {
        $this->requireAuth();

        $employeeId = filter_input(INPUT_GET, 'employee_id', FILTER_VALIDATE_INT);
        $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);

        if (!$employeeId || !$month || !$year || $month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            die('Chybné nebo neúplné parametry pro generování PDF.');
        }

        // Obyčejný zaměstnanec smí generovat PDF pouze pro sebe
        if (!Session::isAdmin() && $employeeId !== Session::getUserId()) {
            die('Přístup odepřen.');
        }

        $employee = $this->userRepository->findById($employeeId);
        if (!$employee) {
            die('Zaměstnanec nebyl nalezen.');
        }

        $shifts = $this->shiftRepository->findByEmployeeAndMonth($employeeId, $month, $year);

        $this->pdfService->generate($employee, $month, $year, $shifts);
    }
}
