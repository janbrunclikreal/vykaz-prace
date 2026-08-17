<?php

namespace App\Service;

use TCPDF;

class PdfService
{
    private ShiftService $shiftService;

    public function __construct()
    {
        $this->shiftService = new ShiftService();
    }

    public function generate(array $employee, int $month, int $year, array $shifts): void
    {
        // Vytvoření pole pro všechny dny v měsíci
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $allDays = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $allDays[$date] = null;
        }

        foreach ($shifts as $shift) {
            $allDays[$shift['datum']] = $shift;
        }

        $tableData = [];
        $totalHours = 0.0;
        $totalWeekendHours = 0.0;

        foreach ($allDays as $date => $shift) {
            $dayNum = date('d', strtotime($date));
            $dayOfWeek = date('N', strtotime($date));
            $isWeekend = ($dayOfWeek == 6 || $dayOfWeek == 7);

            if ($shift === null) {
                $tableData[] = [
                    'den' => $dayNum,
                    'zacatek' => '',
                    'konec' => '',
                    'celkem' => '',
                    'poznamka' => '',
                    'noni' => '',
                    'vikend_hodiny' => ''
                ];
            } else {
                $hours = $this->shiftService->calculateHours($shift['cas_zacatku'], $shift['cas_konce']);
                $hoursFormat = number_format($hours, 2, ',', '');

                $tableData[] = [
                    'den' => $dayNum,
                    'zacatek' => date('H:i', strtotime($shift['cas_zacatku'])),
                    'konec' => date('H:i', strtotime($shift['cas_konce'])),
                    'celkem' => $hoursFormat,
                    'poznamka' => $shift['poznamka'] ?? '',
                    'noni' => $shift['noni'] == 1 ? 'Ano' : '',
                    'vikend_hodiny' => $isWeekend ? $hoursFormat : ''
                ];

                $totalHours += $hours;
                if ($isWeekend) {
                    $totalWeekendHours += $hours;
                }
            }
        }

        $totalHoursFormat = number_format($totalHours, 2, ',', '');
        $totalWeekendHoursFormat = number_format($totalWeekendHours, 2, ',', '');

        $monthsCs = [
            1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
            5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
            9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
        ];
        $monthName = $monthsCs[$month] ?? '';

        // Vytvoření PDF pomocí TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('Výkaz práce v4.0');
        $pdf->SetAuthor('Jan Brunclík');
        $pdf->SetTitle('Výkaz práce - ' . $employee['prijmeni'] . ', ' . $employee['jmeno'] . ' - ' . $monthName . ' ' . $year);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->SetFont('freeserif', '', 10);

        // Nadpis
        $pdf->SetFont('freeserif', 'B', 14);
        $pdf->Cell(0, 10, 'Výkaz práce', 0, 1, 'C');

        $pdf->SetFont('freeserif', '', 10);
        $pdf->Cell(0, 7, 'Objekt - Trojická 20, Praha', 0, 1, 'C');

        // Detaily
        $pdf->Cell(95, 7, 'Příjmení, jméno : ' . $employee['prijmeni'] . ', ' . $employee['jmeno'], 0, 0, 'L');
        $pdf->Cell(95, 7, 'Tel. Číslo : ' . ($employee['telefon'] ?? ''), 0, 1, 'R');

        $pdf->Cell(95, 7, 'Měsíc: ' . $monthName, 0, 0, 'L');
        $pdf->Cell(95, 7, 'Rok : ' . $year, 0, 1, 'R');

        $pdf->Ln(3);

        // Hlavička tabulky
        $pdf->SetFont('freeserif', 'B', 8);
        $pdf->Cell(15, 6, 'Den', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Začátek', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Konec', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Celkem', 1, 0, 'C');
        $pdf->Cell(45, 6, 'Poznámka', 1, 0, 'C');
        $pdf->Cell(15, 6, 'Noční', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Soboty - neděle', 1, 1, 'C');

        // Data tabulky
        $pdf->SetFont('freeserif', '', 8);
        foreach ($tableData as $row) {
            // Použijeme tučné písmo pro víkendy pro přehlednost
            $isWeekendRow = !empty($row['vikend_hodiny']);
            if ($isWeekendRow) {
                $pdf->SetFont('freeserif', 'B', 8);
            } else {
                $pdf->SetFont('freeserif', '', 8);
            }

            $pdf->Cell(15, 5, $row['den'], 1, 0, 'C');
            $pdf->Cell(20, 5, $row['zacatek'], 1, 0, 'C');
            $pdf->Cell(20, 5, $row['konec'], 1, 0, 'C');
            $pdf->Cell(20, 5, $row['celkem'], 1, 0, 'C');
            $pdf->Cell(45, 5, $row['poznamka'], 1, 0, 'L');
            $pdf->Cell(15, 5, $row['noni'], 1, 0, 'C');
            $pdf->Cell(25, 5, $row['vikend_hodiny'], 1, 1, 'C');
        }

        $pdf->Ln(3);

        // Sumář
        $pdf->SetFont('freeserif', '', 9);
        $pdf->Cell(95, 6, 'Odpracováno celkem hodin : ' . $totalHoursFormat, 0, 0, 'L');
        $pdf->Cell(95, 6, 'Podpis pracovníka : _______________________', 0, 1, 'R');

        $pdf->Cell(95, 6, 'Víkendové hodiny : ' . $totalWeekendHoursFormat, 0, 1, 'L');

        $pdf->Ln(3);
        $pdf->Cell(0, 6, 'Podpis provozního manažera: _______________________', 0, 1, 'L');

        $pdf->Ln(3);
        $pdf->SetFont('freeserif', '', 8);
        $pdf->Cell(0, 5, 'Vygenerováno ' . date('d.m.Y H:i'), 0, 1, 'C');
        $pdf->Cell(0, 5, 'Vytvořeno přes Výkaz práce v4.0', 0, 1, 'C');
        $pdf->Cell(0, 5, '(C) Jan Brunclík', 0, 1, 'C');

        // Nastavení HTTP hlaviček
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="vykaz_prace_' . $employee['prijmeni'] . '_' . $monthName . '_' . $year . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $pdf->Output('vykaz_prace_' . $employee['prijmeni'] . '_' . $monthName . '_' . $year . '.pdf', 'I');
        exit;
    }
}
