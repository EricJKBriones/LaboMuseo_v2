<?php
// admin/charts.php
require_once '../includes/init.php';
sessionStart();
requireAdmin();

$period = $_GET['period'] ?? 'monthly';
$allowedPeriods = ['monthly', 'quarterly', 'yearly'];
if (!in_array($period, $allowedPeriods, true)) $period = 'monthly';

$yearsRaw = dbQuery("SELECT DISTINCT YEAR(visit_date) AS yr FROM guests WHERE visit_date IS NOT NULL ORDER BY yr DESC");
$years = [];
foreach ($yearsRaw as $y) {
  $yr = (int)($y['yr'] ?? 0);
  if ($yr > 0) $years[] = $yr;
}

$selectedYear = $_GET['year'] ?? 'all';
if ($selectedYear !== 'all') {
  $selectedYear = (int)$selectedYear;
  if (!in_array($selectedYear, $years, true)) {
    $selectedYear = 'all';
  }
}

$dateWhere = '';
$dateParams = [];
if ($selectedYear !== 'all') {
  $dateWhere = " WHERE YEAR(visit_date)=?";
  $dateParams[] = $selectedYear;
}

$totalVisitors = dbCount("SELECT COALESCE(SUM(headcount), 0) FROM guests");
$totalArtifacts = dbCount("SELECT COUNT(*) FROM exhibits");
$totalDepartments = dbCount("SELECT COUNT(*) FROM categories");
$totalNews = dbCount("SELECT COUNT(*) FROM news_events");

$seriesLabels = [];
$seriesData = [];
$seriesTitle = 'Visitors by Month';

if ($period === 'monthly') {
  $seriesTitle = $selectedYear === 'all' ? 'Visitors by Month (All Years)' : ('Visitors by Month (' . $selectedYear . ')');
  if ($selectedYear === 'all') {
    $rows = dbQuery(
      "SELECT DATE_FORMAT(visit_date, '%Y-%m') AS k, COALESCE(SUM(headcount), 0) AS pax
       FROM guests
       GROUP BY DATE_FORMAT(visit_date, '%Y-%m')
       ORDER BY k ASC"
    );
    foreach ($rows as $row) {
      $key = $row['k'] ?? '';
      $dt = DateTime::createFromFormat('Y-m', $key);
      $seriesLabels[] = $dt ? $dt->format('M Y') : $key;
      $seriesData[] = (int)($row['pax'] ?? 0);
    }
  } else {
    $rows = dbQuery(
      "SELECT MONTH(visit_date) AS m, COALESCE(SUM(headcount), 0) AS pax
       FROM guests
       WHERE YEAR(visit_date)=?
       GROUP BY MONTH(visit_date)
       ORDER BY m ASC",
      [$selectedYear]
    );
    $map = [];
    foreach ($rows as $row) {
      $map[(int)$row['m']] = (int)($row['pax'] ?? 0);
    }
    for ($m = 1; $m <= 12; $m++) {
      $dt = DateTime::createFromFormat('!m', (string)$m);
      $seriesLabels[] = $dt ? $dt->format('M') : (string)$m;
      $seriesData[] = $map[$m] ?? 0;
    }
  }
} elseif ($period === 'quarterly') {
  $seriesTitle = $selectedYear === 'all' ? 'Visitors by Quarter (All Years)' : ('Visitors by Quarter (' . $selectedYear . ')');
  if ($selectedYear === 'all') {
    $rows = dbQuery(
      "SELECT YEAR(visit_date) AS y, QUARTER(visit_date) AS q, COALESCE(SUM(headcount), 0) AS pax
       FROM guests
       GROUP BY YEAR(visit_date), QUARTER(visit_date)
       ORDER BY y ASC, q ASC"
    );
    foreach ($rows as $row) {
      $seriesLabels[] = 'Q' . (int)$row['q'] . ' ' . (int)$row['y'];
      $seriesData[] = (int)($row['pax'] ?? 0);
    }
  } else {
    $rows = dbQuery(
      "SELECT QUARTER(visit_date) AS q, COALESCE(SUM(headcount), 0) AS pax
       FROM guests
       WHERE YEAR(visit_date)=?
       GROUP BY QUARTER(visit_date)
       ORDER BY q ASC",
      [$selectedYear]
    );
    $map = [];
    foreach ($rows as $row) {
      $map[(int)$row['q']] = (int)($row['pax'] ?? 0);
    }
    for ($q = 1; $q <= 4; $q++) {
      $seriesLabels[] = 'Q' . $q;
      $seriesData[] = $map[$q] ?? 0;
    }
  }
} else {
  $seriesTitle = 'Visitors by Year';
  $rows = dbQuery(
    "SELECT YEAR(visit_date) AS y, COALESCE(SUM(headcount), 0) AS pax
     FROM guests
     GROUP BY YEAR(visit_date)
     ORDER BY y ASC"
  );
  foreach ($rows as $row) {
    $seriesLabels[] = (string)((int)$row['y']);
    $seriesData[] = (int)($row['pax'] ?? 0);
  }
}

$typeRows = dbQuery("SELECT visitor_type, COALESCE(SUM(headcount), 0) AS pax FROM guests" . $dateWhere . " GROUP BY visitor_type ORDER BY visitor_type ASC", $dateParams);
$typeLabels = [];
$typeData = [];
foreach ($typeRows as $row) {
    $typeLabels[] = $row['visitor_type'] ?? 'Unknown';
    $typeData[] = (int)($row['pax'] ?? 0);
}

$genderRows = dbOne("SELECT COALESCE(SUM(male_count), 0) AS male_total, COALESCE(SUM(female_count), 0) AS female_total FROM guests" . $dateWhere, $dateParams);
$genderLabels = ['Male', 'Female'];
$genderData = [
    (int)($genderRows['male_total'] ?? 0),
    (int)($genderRows['female_total'] ?? 0)
];

$pageTitle = 'Charts — ' . SITE_NAME;
require_once 'admin_header.php';
?>

<div class="adm-layout">
  <?php require_once 'sidebar.php'; ?>

  <main class="adm-main">
    <div class="adm-welcome">
      <h2>&#128200; Charts & Analytics</h2>
      <p>Visual overview of visitors and museum data trends. Choose period and year to refine chart insights.</p>
    </div>

    <form method="GET" action="charts.php" class="mbar" data-auto-submit="1" data-debounce="250">
      <label for="periodFilter">Period:</label>
      <select id="periodFilter" name="period" class="mi">
        <option value="monthly" <?= $period==='monthly'?'selected':'' ?>>Monthly</option>
        <option value="quarterly" <?= $period==='quarterly'?'selected':'' ?>>Quarterly</option>
        <option value="yearly" <?= $period==='yearly'?'selected':'' ?>>Yearly</option>
      </select>

      <label for="yearFilter">Year:</label>
      <select id="yearFilter" name="year" class="mi" <?= $period==='yearly' ? 'disabled' : '' ?>>
        <option value="all" <?= $selectedYear==='all'?'selected':'' ?>>All Years</option>
        <?php foreach ($years as $yr): ?>
          <option value="<?= $yr ?>" <?= $selectedYear===$yr?'selected':'' ?>><?= $yr ?></option>
        <?php endforeach; ?>
      </select>

      <a href="charts.php" class="btn-clf" style="text-decoration:none;display:inline-flex;align-items:center">Reset</a>
    </form>

    <div class="adm-stats">
      <div class="astat blue"><div><div class="astat-n"><?= $totalVisitors ?></div><div class="astat-l">Total Visitors</div></div><div class="astat-i">&#128101;</div></div>
      <div class="astat green"><div><div class="astat-n"><?= $totalArtifacts ?></div><div class="astat-l">Artifacts</div></div><div class="astat-i">&#127994;</div></div>
      <div class="astat purple"><div><div class="astat-n"><?= $totalDepartments ?></div><div class="astat-l">Departments</div></div><div class="astat-i">&#128193;</div></div>
      <div class="astat orange"><div><div class="astat-n"><?= $totalNews ?></div><div class="astat-l">News &amp; Events</div></div><div class="astat-i">&#128240;</div></div>
    </div>

    <div class="adm-charts-grid">
      <section class="adm-chart-card">
        <h3><?= htmlspecialchars($seriesTitle) ?></h3>
        <canvas id="monthlyVisitorsChart" height="120"></canvas>
      </section>

      <section class="adm-chart-card">
        <h3>Visitors by Type</h3>
        <canvas id="visitorTypeChart" height="120"></canvas>
      </section>

      <section class="adm-chart-card">
        <h3>Gender Distribution</h3>
        <div class="adm-chart-compact-wrap">
          <canvas id="genderChart" height="170"></canvas>
        </div>
      </section>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
  var monthlyLabels = <?= json_encode($seriesLabels, JSON_UNESCAPED_UNICODE) ?>;
  var monthlyData = <?= json_encode($seriesData, JSON_UNESCAPED_UNICODE) ?>;
  var typeLabels = <?= json_encode($typeLabels, JSON_UNESCAPED_UNICODE) ?>;
  var typeData = <?= json_encode($typeData, JSON_UNESCAPED_UNICODE) ?>;
  var genderLabels = <?= json_encode($genderLabels, JSON_UNESCAPED_UNICODE) ?>;
  var genderData = <?= json_encode($genderData, JSON_UNESCAPED_UNICODE) ?>;

  var commonLegend = { labels: { color: '#4f6478', font: { family: 'DM Sans' } } };

  new Chart(document.getElementById('monthlyVisitorsChart'), {
    type: 'line',
    data: {
      labels: monthlyLabels,
      datasets: [{
        label: 'Visitors',
        data: monthlyData,
        borderColor: '#2f8fcb',
        backgroundColor: 'rgba(47,143,203,.15)',
        tension: .35,
        fill: true,
        pointRadius: 3
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: commonLegend },
      scales: {
        x: { ticks: { color: '#6b7d8f' }, grid: { color: '#edf2f7' } },
        y: { beginAtZero: true, ticks: { color: '#6b7d8f' }, grid: { color: '#edf2f7' } }
      }
    }
  });

  new Chart(document.getElementById('visitorTypeChart'), {
    type: 'bar',
    data: {
      labels: typeLabels,
      datasets: [{
        label: 'Visitors',
        data: typeData,
        backgroundColor: ['#1f7a8c', '#f39c12', '#6a5acd', '#2ecc71']
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: commonLegend },
      scales: {
        x: { ticks: { color: '#6b7d8f' }, grid: { display: false } },
        y: { beginAtZero: true, ticks: { color: '#6b7d8f' }, grid: { color: '#edf2f7' } }
      }
    }
  });

  new Chart(document.getElementById('genderChart'), {
    type: 'doughnut',
    data: {
      labels: genderLabels,
      datasets: [{
        data: genderData,
        backgroundColor: ['#4f6d8a', '#9fb3c8'],
        borderColor: 'transparent',
        borderWidth: 0,
        hoverOffset: 4,
        radius: '78%'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '72%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: '#4f6478',
            boxWidth: 12,
            boxHeight: 12,
            padding: 14,
            font: { family: 'DM Sans', size: 11 }
          }
        }
      }
    }
  });

  var periodFilter = document.getElementById('periodFilter');
  var yearFilter = document.getElementById('yearFilter');
  if (periodFilter && yearFilter) {
    function syncYearFilterState() {
      var isYearly = periodFilter.value === 'yearly';
      yearFilter.disabled = isYearly;
      if (isYearly) {
        yearFilter.value = 'all';
      }
    }
    periodFilter.addEventListener('change', syncYearFilterState);
    syncYearFilterState();
  }
})();
</script>

<?php require_once 'admin_footer.php'; ?>
