<?php
// admin/export.php
require_once '../includes/db.php';
sessionStart();
requireAdmin();

$month  = $_GET['month'] ?? '';
$params = [];
$sql    = "SELECT * FROM guests WHERE 1=1";
if ($month) { $sql .= " AND DATE_FORMAT(visit_date,'%Y-%m')=?"; $params[] = $month; }
$sql .= " ORDER BY visit_date DESC, id DESC";
$guests = dbQuery($sql, $params);

$filename = 'Museo_Visitors_' . ($month ?: date('Y-m-d')) . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['#','Visit Date','Visitor Type','Organization','Guest Name','Gender','Residence','Nationality','Total Pax','Male','Female','Purpose','Contact No.','Registered At']);

foreach ($guests as $g) {
    fputcsv($out, [
        $g['id'],
        $g['visit_date'],
        $g['visitor_type'],
        $g['organization'] ?? 'N/A',
        $g['guest_name'],
        $g['gender'],
        $g['residence'],
        $g['nationality'],
        $g['headcount'],
        $g['male_count'],
        $g['female_count'],
        $g['purpose'],
        $g['contact_no'],
        $g['created_at']
    ]);
}
fclose($out);
exit;
