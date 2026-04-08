<?php
// admin/export_artifacts.php
require_once '../includes/db.php';
sessionStart();
requireAdmin();

$format = strtolower(trim((string)($_POST['format'] ?? $_GET['format'] ?? 'pdf')));
$scope = strtolower(trim((string)($_POST['scope'] ?? $_GET['scope'] ?? 'selected')));
if (!in_array($format, ['pdf', 'xlsx', 'csv'], true)) {
    $format = 'pdf';
}
if (!in_array($scope, ['selected', 'all'], true)) {
    $scope = 'selected';
}
$singleLayoutRequested = (string)($_POST['single_layout'] ?? $_GET['single_layout'] ?? '0') === '1';

$search = trim((string)($_POST['q'] ?? $_GET['q'] ?? ''));
$deptId = (int)($_POST['dept'] ?? $_GET['dept'] ?? 0);
$sort = (string)($_POST['sort'] ?? $_GET['sort'] ?? 'newest');
$sortMap = [
    'newest' => 'e.id DESC',
    'oldest' => 'e.id ASC',
    'title_asc' => 'e.title ASC',
    'title_desc' => 'e.title DESC',
    'year_desc' => 'e.artifact_year DESC',
    'year_asc' => 'e.artifact_year ASC',
    'dept_asc' => 'c.name ASC, e.title ASC'
];
if (!isset($sortMap[$sort])) {
    $sort = 'newest';
}

$rawIds = $_POST['artifact_ids'] ?? $_GET['artifact_ids'] ?? $_GET['ids'] ?? [];
if (is_string($rawIds)) {
    $rawIds = array_filter(array_map('trim', explode(',', $rawIds)), 'strlen');
}
if (!is_array($rawIds)) {
    $rawIds = [];
}
$artifactIds = [];
foreach ($rawIds as $id) {
    $intId = (int)$id;
    if ($intId > 0) {
        $artifactIds[$intId] = $intId;
    }
}
$artifactIds = array_values($artifactIds);

if ($scope === 'selected' && empty($artifactIds)) {
    header('Location: artifacts.php?msg=export_none');
    exit;
}

$params = [];
$sql = "SELECT e.id, e.title, e.description, e.artifact_year, e.origin, e.donated_by, e.image_path, c.name AS category_name FROM exhibits e LEFT JOIN categories c ON e.category_id = c.id WHERE 1=1";

if ($scope === 'selected') {
    $placeholders = implode(',', array_fill(0, count($artifactIds), '?'));
    $sql .= " AND e.id IN ($placeholders)";
    foreach ($artifactIds as $artifactId) {
        $params[] = $artifactId;
    }
} else {
    if ($search !== '') {
        $sql .= ' AND e.title LIKE ?';
        $params[] = '%' . $search . '%';
    }
    if ($deptId > 0) {
        $sql .= ' AND e.category_id = ?';
        $params[] = $deptId;
    }
}

$sql .= ' ORDER BY ' . $sortMap[$sort];
$artifacts = dbQuery($sql, $params);

if (empty($artifacts)) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>No Artifacts</title></head><body style="font-family:Arial,sans-serif;padding:24px">';
    echo '<h2>No artifacts found for this export.</h2>';
    echo '<p><a href="artifacts.php">Back to Manage Artifacts</a></p>';
    echo '</body></html>';
    exit;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$dir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$base = $protocol . '://' . $_SERVER['HTTP_HOST'] . $dir . '/';

$rows = [];
foreach ($artifacts as $artifact) {
    $rows[] = [
        (int)$artifact['id'],
        (string)$artifact['title'],
        (string)($artifact['category_name'] ?? 'N/A'),
        (string)($artifact['artifact_year'] ?? ''),
        (string)($artifact['donated_by'] ?? ''),
        (string)($artifact['origin'] ?? ''),
        trim((string)($artifact['description'] ?? '')),
        (string)($artifact['image_path'] ?? '')
    ];
}

$generatedAt = date('l, F j, Y');
$exportLabel = $scope === 'selected' ? 'Selected Artifacts' : 'All Filtered Artifacts';
$columns = ['#', 'Title', 'Department/Category', 'Year/Period', 'Donated By', 'Origin', 'Description', 'Image Filename'];

if ($format === 'csv') {
    $filename = 'Museo_Artifacts_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Museo de Labo - Artifact Export']);
    fputcsv($out, ['Generated At', $generatedAt]);
    fputcsv($out, ['Scope', $exportLabel]);
    fputcsv($out, ['Total Records', count($rows)]);
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

            $spreadsheet = new $spreadsheetClass();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Artifacts');

            $sheet->setCellValue('A1', 'Museo de Labo - Artifact Export');
            $sheet->mergeCells('A1:H1');
            $sheet->setCellValue('A2', 'Generated At');
            $sheet->setCellValue('B2', $generatedAt);
            $sheet->setCellValue('A3', 'Scope');
            $sheet->setCellValue('B3', $exportLabel);
            $sheet->setCellValue('A4', 'Total Records');
            $sheet->setCellValue('B4', count($rows));

            $sheet->fromArray($columns, null, 'A6');
            $sheet->fromArray($rows, null, 'A7');

            $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A1:H1')->getFill()->setFillType($fillSolid)->getStartColor()->setRGB('1F3E56');
            $sheet->getStyle('A6:H6')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A6:H6')->getFill()->setFillType($fillSolid)->getStartColor()->setRGB('2F8FCB');

            $lastRow = 6 + count($rows);
            $sheet->getStyle('A6:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle($borderThin);
            $sheet->freezePane('A7');
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'Museo_Artifacts_' . date('Y-m-d') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new $writerClass($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Throwable $e) {
            // Fall back to HTML-based XLS below.
        }
    }

    $filename = 'Museo_Artifacts_' . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo '<html><head><meta charset="UTF-8"><style>'
        . 'body{font-family:Calibri,Arial,sans-serif}'
        . 'table{border-collapse:collapse;width:100%}'
        . '.meta{margin-bottom:12px}'
        . '.meta td{padding:4px 8px;border:1px solid #d9d9d9;font-size:12px}'
        . '.title{background:#1f3e56;color:#fff;font-size:16px;font-weight:700}'
        . '.head th{background:#2f8fcb;color:#fff;border:1px solid #1f6f9f;padding:7px 8px;font-size:11px;text-align:left}'
        . '.row td{border:1px solid #d9d9d9;padding:6px 8px;font-size:11px;vertical-align:top}'
        . '.row:nth-child(even) td{background:#f7fbff}'
        . '</style></head><body>';

    echo '<table class="meta">';
    echo '<tr><td class="title" colspan="2">Museo de Labo - Artifact Export</td></tr>';
    echo '<tr><td><strong>Generated At</strong></td><td>' . htmlspecialchars($generatedAt) . '</td></tr>';
    echo '<tr><td><strong>Scope</strong></td><td>' . htmlspecialchars($exportLabel) . '</td></tr>';
    echo '<tr><td><strong>Total Records</strong></td><td>' . count($rows) . '</td></tr>';
    echo '</table>';

    echo '<table><tr class="head">';
    foreach ($columns as $column) {
        echo '<th>' . htmlspecialchars($column) . '</th>';
    }
    echo '</tr>';

    foreach ($rows as $row) {
        echo '<tr class="row">';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars((string)$cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
$isSingleExport = count($artifacts) === 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Museo Artifact PDF Export</title>
<style>
  :root {
    --ink:#10202f;
    --ink2:#2c3f51;
    --gold:#c9922a;
    --gold2:#e5be73;
    --paper:#f6f1e8;
    --paper2:#fff;
    --line:#d7c9af;
  }
  * { box-sizing:border-box; }
  body {
    margin:0;
    font-family:"DM Sans","Segoe UI",Arial,sans-serif;
    color:var(--ink);
    background:linear-gradient(160deg,#f9f4ea,#efe4d2 56%,#e8dbc5);
  }
  .sheet {
    max-width:1120px;
    margin:28px auto;
    padding:24px;
  }
  body.single-export .sheet {
    max-width:1500px;
    width:100%;
    margin:0;
    padding:14px 18px 12px;
    min-height:100vh;
  }
  .toolbar {
    display:flex;
    justify-content:flex-end;
    margin-bottom:14px;
    gap:10px;
  }
  .toolbar button,
  .toolbar a {
    border:none;
    border-radius:999px;
    padding:10px 16px;
    background:#173043;
    color:#fff;
    text-decoration:none;
    font-weight:700;
    cursor:pointer;
  }
  .toolbar a { background:#6f7d86; }
  .hero {
    background:radial-gradient(circle at top right, rgba(229,190,115,.35), rgba(22,43,59,.98) 70%);
    color:#fff;
    padding:22px 24px;
    border-radius:16px;
    box-shadow:0 14px 32px rgba(12,24,36,.26);
    border:1px solid rgba(255,255,255,.15);
  }
  .hero-head {
    display:flex;
    align-items:center;
    justify-content:center;
    gap:18px;
  }
  .hero-logo {
    width:84px;
    height:84px;
    border-radius:999px;
    border:2px solid rgba(229,190,115,.68);
    box-shadow:0 6px 14px rgba(5,16,24,.34);
    object-fit:cover;
    background:#1f3345;
  }
  .hero-logo-ph {
    width:84px;
    height:84px;
    border-radius:999px;
    border:2px solid rgba(229,190,115,.68);
    background:linear-gradient(145deg,#2b4f6a,#183244);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#f3ddab;
    font-family:"Playfair Display","Georgia",serif;
    font-weight:700;
    font-size:1.45rem;
    box-shadow:0 6px 14px rgba(5,16,24,.34);
  }
  .hero-text {
    text-align:left;
  }
  .hero h1 {
    margin:0;
    font-family:"Playfair Display","Georgia",serif;
    letter-spacing:.3px;
    font-size:2.7rem;
    line-height:1;
    color:#ffffff;
    text-shadow:0 2px 10px rgba(0,0,0,.55), 0 0 1px rgba(0,0,0,.7);
  }
  .hero-baybayin {
    margin-top:3px;
    color:#fff7da;
    font-size:1.2rem;
    letter-spacing:1px;
    line-height:1.25;
    text-shadow:0 1px 8px rgba(0,0,0,.55), 0 0 1px rgba(0,0,0,.7);
    font-weight:700;
  }
  .meta {
    display:flex;
    justify-content:center;
    margin:16px 0 20px;
  }
  .meta .card {
    background:var(--paper2);
    border:1px solid #e2d7c5;
    border-radius:12px;
    padding:10px 14px;
    min-width:300px;
    text-align:center;
  }
  .meta .label {
    font-size:.7rem;
    text-transform:uppercase;
    letter-spacing:1.2px;
    color:#697987;
    font-weight:700;
    margin-bottom:3px;
  }
  .meta .value {
    font-size:.95rem;
    font-weight:700;
    color:var(--ink);
  }
  .grid {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
  }
  body.single-export .grid {
    grid-template-columns:1fr;
    gap:0;
  }
  .artifact {
    background:var(--paper2);
    border-radius:14px;
    border:1px solid var(--line);
    overflow:hidden;
    display:grid;
    grid-template-columns:360px 1fr;
    min-height:200px;
    box-shadow:0 10px 22px rgba(17,32,46,.08);
    break-inside:avoid;
    page-break-inside:avoid;
  }
  body.single-export .artifact {
    grid-template-columns:minmax(520px,56%) 1fr;
    min-height:calc(100vh - 250px);
    border-radius:18px;
  }
  body.single-export .image-wrap {
    padding:16px;
  }
  body.single-export .image-wrap img {
    width:100%;
    height:100%;
    min-height:460px;
    max-height:none;
  }
  body.single-export .content {
    padding:20px 20px 16px;
    gap:10px;
  }
  body.single-export .title {
    font-size:1.55rem;
  }
  body.single-export .desc {
    font-size:.94rem;
    line-height:1.5;
  }
  body.single-export .chip {
    font-size:.78rem;
    padding:5px 10px;
  }
  .image-wrap {
    background:linear-gradient(150deg,#253f53,#122331);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:10px;
    border-right:1px solid rgba(255,255,255,.12);
  }
  .image-wrap img {
    width:100%;
    height:300px;
    object-fit:contain;
    object-position:center;
    border-radius:8px;
    border:2px solid rgba(229,190,115,.45);
    box-shadow:0 8px 18px rgba(5,16,24,.28);
    background:rgba(10,24,35,.38);
  }
  .image-missing {
    color:#dce7ef;
    font-weight:700;
    text-align:center;
    font-size:.8rem;
    line-height:1.45;
  }
  .content {
    padding:14px 14px 12px;
    display:flex;
    flex-direction:column;
    gap:8px;
  }
  .title {
    margin:0;
    font-family:"Playfair Display","Georgia",serif;
    color:#1b2f42;
    font-size:1.2rem;
    line-height:1.25;
  }
  .chips {
    display:flex;
    gap:7px;
    flex-wrap:wrap;
  }
  .chip {
    background:#ecf3f9;
    color:#21455f;
    border:1px solid #cadced;
    border-radius:999px;
    padding:4px 9px;
    font-size:.72rem;
    font-weight:700;
  }
  .desc {
    margin:0;
    color:#355066;
    font-size:.86rem;
    line-height:1.45;
  }
  .footer-note {
    margin-top:16px;
    font-size:.76rem;
    color:#607283;
    text-align:center;
    line-height:1.5;
    border-top:1px solid #d8cdb9;
    padding-top:10px;
  }
  .footer-note .contacts {
    margin-top:4px;
  }
  @media print {
    @page { margin:10mm; }
    html, body {
      background:linear-gradient(160deg,#f9f4ea,#efe4d2 56%,#e8dbc5) !important;
      -webkit-print-color-adjust:exact;
      print-color-adjust:exact;
    }
    .sheet { margin:0; max-width:none; padding:0; }
    .toolbar { display:none; }
    .hero { border-radius:0; box-shadow:none; }
    .artifact { box-shadow:none; }

    body.single-export .hero {
      padding:12px 14px;
      margin-bottom:8px;
      border-radius:8px;
    }

    body.single-export .meta {
      margin:8px 0 10px;
    }

    body.single-export .meta .card {
      padding:7px 10px;
    }

    body.single-export .grid {
      gap:8px;
    }

    body.single-export .artifact {
      min-height:calc(100vh - 200px);
      page-break-inside:avoid;
      break-inside:avoid;
    }

    body.single-export .image-wrap img {
      height:100%;
      min-height:360px;
      max-height:none;
    }

    body.single-export .content {
      padding:10px 10px 8px;
      gap:6px;
    }

    body.single-export .title {
      font-size:1.05rem;
    }

    body.single-export .desc {
      font-size:.78rem;
      line-height:1.35;
    }

    body.single-export .chip {
      font-size:.65rem;
      padding:3px 7px;
    }

    body.single-export .footer-note {
      margin-top:8px;
      padding-top:8px;
      font-size:.68rem;
    }
  }
  @media (max-width:900px) {
    .hero-head { justify-content:flex-start; }
    .hero-logo,
    .hero-logo-ph { width:66px; height:66px; }
    .hero h1 { font-size:2.1rem; }
    .hero-baybayin { font-size:1rem; }
    .meta { justify-content:stretch; }
    .meta .card { min-width:0; width:100%; }
    .grid { grid-template-columns:1fr; }
    .artifact { grid-template-columns:300px 1fr; }
    .image-wrap { border-right:1px solid rgba(255,255,255,.12); border-bottom:none; }
    .image-wrap img { height:260px; object-fit:contain; }

    body.single-export .artifact {
      grid-template-columns:1fr;
      min-height:0;
    }
    body.single-export .image-wrap {
      border-right:none;
      border-bottom:1px solid rgba(255,255,255,.12);
    }
    body.single-export .image-wrap img {
      min-height:0;
      height:320px;
    }
  }
  @media (max-width:600px) {
    .artifact { grid-template-columns:1fr; }
    .image-wrap { border-right:none; border-bottom:1px solid rgba(255,255,255,.12); }
    .image-wrap img { height:260px; object-fit:contain; }
  }
</style>
</head>
<body class="<?= $isSingleExport ? 'single-export' : '' ?>">
  <div class="sheet">
    <div class="toolbar">
      <a href="artifacts.php">Back to Artifacts</a>
      <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <section class="hero">
      <?php
        $pdfLogoExists = file_exists('../uploads/logo.png');
        $pdfLogoUrl = $base . 'uploads/logo.png';
      ?>
      <div class="hero-head">
        <?php if ($pdfLogoExists): ?>
          <img src="<?= htmlspecialchars($pdfLogoUrl) ?>" alt="Museo logo" class="hero-logo">
        <?php else: ?>
          <div class="hero-logo-ph" aria-hidden="true">ML</div>
        <?php endif; ?>
        <div class="hero-text">
          <h1>Museo de Labo</h1>
          <div class="hero-baybayin">ᜋᜓᜐᜒᜂ ᜇᜒ ᜎᜊᜓ</div>
        </div>
      </div>
    </section>

    <section class="meta">
      <div class="card">
        <div class="label">Generated At</div>
        <div class="value"><?= htmlspecialchars($generatedAt) ?></div>
      </div>
    </section>

    <section class="grid">
      <?php foreach ($artifacts as $artifact): ?>
        <?php
          $imagePath = trim((string)($artifact['image_path'] ?? ''));
          $imageExists = $imagePath !== '' && file_exists('../uploads/' . $imagePath);
          $imageUrl = $imageExists ? ($base . 'uploads/' . rawurlencode($imagePath)) : '';
        ?>
        <article class="artifact">
          <div class="image-wrap">
            <?php if ($imageExists): ?>
              <img src="<?= htmlspecialchars($imageUrl) ?>" alt="Artifact image">
            <?php else: ?>
              <div class="image-missing">No image<br>available</div>
            <?php endif; ?>
          </div>
          <div class="content">
            <h2 class="title"><?= htmlspecialchars($artifact['title']) ?></h2>
            <div class="chips">
              <span class="chip">Department: <?= htmlspecialchars($artifact['category_name'] ?: 'N/A') ?></span>
              <span class="chip">Year: <?= htmlspecialchars($artifact['artifact_year'] ?: 'N/A') ?></span>
              <span class="chip">Donated By: <?= htmlspecialchars($artifact['donated_by'] ?: 'N/A') ?></span>
            </div>
            <p class="desc"><strong>Origin:</strong> <?= htmlspecialchars($artifact['origin'] ?: 'N/A') ?></p>
            <p class="desc"><?= nl2br(htmlspecialchars($artifact['description'] ?: 'No description provided.')) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <div class="footer-note">
      <div>&copy; <?= date('Y') ?> Museo de Labo</div>
      <div class="contacts">Labo People's Park, Labo, Camarines Norte</div>
      <div class="contacts">(054) 885-1074 | +63 0928 661 2138</div>
      <div class="contacts">josecarlosblagatuz@gmail.com | labotourism08@yahoo.com</div>
    </div>
  </div>

  <script>
    window.addEventListener('load', function() {
      setTimeout(function() {
        try {
          window.print();
        } catch (e) {
          // Ignore print dialog errors.
        }
      }, 350);
    });
  </script>
</body>
</html>
