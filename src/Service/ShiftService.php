<?php

namespace App\Service;

use App\Repository\ShiftRepository;

class ShiftService
{
    private ShiftRepository $shiftRepository;

    public function __construct()
    {
        $this->shiftRepository = new ShiftRepository();
    }

    /**
     * Vypočítá odpracované hodiny (odečítá 0.5 hodiny na pauzu)
     * Správně řeší noční směny přes půlnoc.
     */
    public function calculateHours(string $startTime, string $endTime): float
    {
        if (empty($startTime) || empty($endTime)) {
            return 0.0;
        }

        $start = strtotime($startTime);
        $end = strtotime($endTime);

        if ($start === false || $end === false) {
            return 0.0;
        }

        $diff = $end - $start;
        if ($diff < 0) {
            // Přechod přes půlnoc - přičteme 24 hodin (86400 sekund)
            $diff += 86400;
        }

        $hours = $diff / 3600;
        // Odečtení 0.5 hodiny na pauzu
        $hours -= 0.5;

        return max(0.0, $hours);
    }

    /**
     * Hromadné uložení směn v transakci
     * $shifts je pole: [ ['datum' => '2026-08-17', 'cas_zacatku' => '07:00', 'cas_konce' => '19:00', 'poznamka' => '...', 'noni' => true], ... ]
     */
    public function saveBulk(int $employeeId, array $shifts): bool
    {
        $this->shiftRepository->beginTransaction();

        try {
            foreach ($shifts as $shiftData) {
                $datum = $shiftData['datum'] ?? null;
                if (!$datum) {
                    continue;
                }

                // Smazat případnou existující směnu pro dané datum a zaměstnance
                $this->shiftRepository->deleteByEmployeeAndDate($employeeId, $datum);

                $startTime = $shiftData['cas_zacatku'] ?? '';
                $endTime = $shiftData['cas_konce'] ?? '';

                // Pokud jsou časy vyplněny, vytvoříme nový záznam
                if (!empty($startTime) && !empty($endTime)) {
                    $this->shiftRepository->create([
                        'id_zamestnance' => $employeeId,
                        'datum' => $datum,
                        'cas_zacatku' => $startTime,
                        'cas_konce' => $endTime,
                        'poznamka' => $shiftData['poznamka'] ?? '',
                        'noni' => !empty($shiftData['noni']) ? 1 : 0
                    ]);
                }
            }

            $this->shiftRepository->commit();
            return true;
        } catch (\Exception $e) {
            $this->shiftRepository->rollBack();
            throw $e;
        }
    }
}
