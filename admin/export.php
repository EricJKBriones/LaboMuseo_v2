<?php
// admin/export.php
require_once '../includes/init.php';
sessionStart();
requireAdmin();

$month  = $_GET['month'] ?? '';
$format = strtolower(trim($_GET['format'] ?? 'xlsx'));
$params = [];
$sql    = "SELECT * FROM guests WHERE 1=1";
if ($month) { $sql .= " AND DATE_FORMAT(visit_date,'%Y-%m')=?"; $params[] = $month; }
$sql .= " ORDER BY visit_date DESC, id DESC";
$guests = dbQuery($sql, $params);

$totalPax = 0;
$totalMale = 0;
$totalFemale = 0;
foreach ($guests as $g) {
    $totalPax += (int)($g['headcount'] ?? 0);
    $totalMale += (int)($g['male_count'] ?? 0);
    $totalFemale += (int)($g['female_count'] ?? 0);
}

$columns = ['#','Visit Date','Visitor Type','Organization','Guest Name','Gender','Residence','Nationality','Total Pax','Male','Female','Purpose','Contact No.','Registered At'];

$rows = [];
foreach ($guests as $g) {
    $rows[] = [
        (int)$g['id'],
        $g['visit_date'],
        $g['visitor_type'],
        $g['organization'] ?: 'N/A',
        $g['guest_name'],
        $g['gender'],
        $g['residence'],
        $g['nationality'],
        (int)$g['headcount'],
        (int)$g['male_count'],
        (int)$g['female_count'],
        $g['purpose'],
        $g['contact_no'],
        $g['created_at']
    ];
}

if ($format === 'csv') {
    $filename = 'Museo_Visitors_' . ($month ?: date('Y-m-d')) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM helps Excel render special characters correctly.
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, ['Museo de Labo - Visitor Export']);
    fputcsv($out, ['Generated At', date('Y-m-d H:i:s')]);
    fputcsv($out, ['Filter Month', $month ?: 'All']);
    fputcsv($out, ['Total Records', count($guests)]);
    fputcsv($out, ['Total Visitors (Pax)', $totalPax]);
    fputcsv($out, []);
    fputcsv($out, $columns);

    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

if ($format === 'xlsx') {
    $spreadsheetClass = 'PhpOffice\\PhpSpreadsheet\\Spreadsheet';
    $writerClass = 'PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx';
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    if (class_exists($spreadsheetClass) && class_exists($writerClass)) {
        try {
            $fillSolid = defined('PhpOffice\\PhpSpreadsheet\\Style\\Fill::FILL_SOLID')
                ? constant('PhpOffice\\PhpSpreadsheet\\Style\\Fill::FILL_SOLID')
                : 'solid';
            $borderThin = defined('PhpOffice\\PhpSpreadsheet\\Style\\Border::BORDER_THIN')
                ? constant('PhpOffice\\PhpSpreadsheet\\Style\\Border::BORDER_THIN')
                : 'thin';
            $alignRight = defined('PhpOffice\\PhpSpreadsheet\\Style\\Alignment::HORIZONTAL_RIGHT')
                ? constant('PhpOffice\\PhpSpreadsheet\\Style\\Alignment::HORIZONTAL_RIGHT')
                : 'right';

            $spreadsheet = new $spreadsheetClass();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Visitors');

            $sheet->setCellValue('A1', 'Museo de Labo - Visitor Export');
            $sheet->mergeCells('A1:N1');
            $sheet->setCellValue('A2', 'Generated At');
            $sheet->setCellValue('B2', date('Y-m-d H:i:s'));
            $sheet->setCellValue('A3', 'Filter Month');
            $sheet->setCellValue('B3', $month ?: 'All');
            $sheet->setCellValue('A4', 'Total Records');
            $sheet->setCellValue('B4', count($rows));
            $sheet->setCellValue('A5', 'Total Visitors (Pax)');
            $sheet->setCellValue('B5', $totalPax);
            $sheet->setCellValue('A6', 'Total Male');
            $sheet->setCellValue('B6', $totalMale);
            $sheet->setCellValue('A7', 'Total Female');
            $sheet->setCellValue('B7', $totalFemale);

            $sheet->fromArray($columns, null, 'A9');
            if (!empty($rows)) {
                $sheet->fromArray($rows, null, 'A10');
            }

            $sheet->getStyle('A1:N1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A1:N1')->getFill()->setFillType($fillSolid)->getStartColor()->setRGB('1F3E56');
            $sheet->getStyle('A9:N9')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A9:N9')->getFill()->setFillType($fillSolid)->getStartColor()->setRGB('2F8FCB');

            $lastRow = 9 + count($rows);
            $sheet->getStyle('A9:N' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle($borderThin);
            $sheet->getStyle('I10:K' . max(10, $lastRow))->getAlignment()->setHorizontal($alignRight);
            $sheet->freezePane('A10');

            foreach (range('A', 'N') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'Museo_Visitors_' . ($month ?: date('Y-m-d')) . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new $writerClass($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Throwable $e) {
            // Graceful fallback to HTML-based XLS export below.
        }
    }
}

$filename = 'Museo_Visitors_' . ($month ?: date('Y-m-d')) . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo '<html><head><meta charset="UTF-8">';
echo '<style>
  body { font-family: Calibri, Arial, sans-serif; }
  table { border-collapse: collapse; width: 100%; }
  .meta { margin-bottom: 12px; }
  .meta td { padding: 4px 8px; border: 1px solid #d9d9d9; font-size: 12px; }
  .title { background: #1f3e56; color: #ffffff; font-size: 16px; font-weight: 700; }
  .head th { background: #2f8fcb; color: #ffffff; border: 1px solid #1f6f9f; padding: 7px 8px; font-size: 11px; text-align: left; }
  .row td { border: 1px solid #d9d9d9; padding: 6px 8px; font-size: 11px; vertical-align: top; }
  .row:nth-child(even) td { background: #f7fbff; }
  .num { text-align: right; }
</style>';
echo '</head><body>';

echo '<table class="meta">';
echo '<tr><td class="title" colspan="2">Museo de Labo - Visitor Export</td></tr>';
echo '<tr><td><strong>Generated At</strong></td><td>' . htmlspecialchars(date('Y-m-d H:i:s')) . '</td></tr>';
echo '<tr><td><strong>Filter Month</strong></td><td>' . htmlspecialchars($month ?: 'All') . '</td></tr>';
echo '<tr><td><strong>Total Records</strong></td><td>' . count($guests) . '</td></tr>';
echo '<tr><td><strong>Total Visitors (Pax)</strong></td><td>' . $totalPax . '</td></tr>';
echo '<tr><td><strong>Total Male</strong></td><td>' . $totalMale . '</td></tr>';
echo '<tr><td><strong>Total Female</strong></td><td>' . $totalFemale . '</td></tr>';
echo '</table>';

echo '<table>';
echo '<tr class="head">'
    . '<th>#</th><th>Visit Date</th><th>Visitor Type</th><th>Organization</th><th>Guest Name</th>'
    . '<th>Gender</th><th>Residence</th><th>Nationality</th><th>Total Pax</th><th>Male</th><th>Female</th>'
    . '<th>Purpose</th><th>Contact No.</th><th>Registered At</th>'
    . '</tr>';

foreach ($guests as $g) {
    echo '<tr class="row">'
        . '<td>' . (int)$g['id'] . '</td>'
        . '<td>' . htmlspecialchars($g['visit_date']) . '</td>'
        . '<td>' . htmlspecialchars($g['visitor_type']) . '</td>'
        . '<td>' . htmlspecialchars($g['organization'] ?: 'N/A') . '</td>'
        . '<td>' . htmlspecialchars($g['guest_name']) . '</td>'
        . '<td>' . htmlspecialchars($g['gender']) . '</td>'
        . '<td>' . htmlspecialchars($g['residence']) . '</td>'
        . '<td>' . htmlspecialchars($g['nationality']) . '</td>'
        . '<td class="num">' . (int)$g['headcount'] . '</td>'
        . '<td class="num">' . (int)$g['male_count'] . '</td>'
        . '<td class="num">' . (int)$g['female_count'] . '</td>'
        . '<td>' . htmlspecialchars($g['purpose']) . '</td>'
        . '<td>' . htmlspecialchars($g['contact_no']) . '</td>'
        . '<td>' . htmlspecialchars($g['created_at']) . '</td>'
        . '</tr>';
}

echo '</table>';
echo '</body></html>';
exit;
