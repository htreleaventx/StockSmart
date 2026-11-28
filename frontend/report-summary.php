<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount(true);
require __DIR__ . '/db.php';

// Handle saving the currently viewed report into session
if (postRequest() && input(INPUT_POST, 'action') === 'save_report') {
    requireCsrf();
    $saveType = input(INPUT_POST, 'reportType');
    $saveStart = input(INPUT_POST, 'startDate');
    $saveEnd = input(INPUT_POST, 'endDate');
    if (in_array($saveType, ['weekly', 'monthly'], true) && validDate($saveStart) && validDate($saveEnd)) {
        $_SESSION['saved_report'] = [
            'reportType' => $saveType,
            'startDate'  => $saveStart,
            'endDate'    => $saveEnd,
        ];
    }
    redirect('report-summary.php?' . http_build_query([
        'reportType' => $saveType,
        'startDate'  => $saveStart,
        'endDate'    => $saveEnd,
        'saved'      => '1',
    ]));
}

$saved = $_SESSION['saved_report'] ?? null;
$hasParams = isset($_GET['reportType']) || isset($_GET['startDate']) || isset($_GET['endDate']);

if (!$hasParams && $saved) {
    $report = $saved['reportType'];
    $start  = $saved['startDate'];
    $end    = $saved['endDate'];
} else {
    $report = input(INPUT_GET, 'reportType') ?: 'weekly';
    $today  = $pdo->query('SELECT CURRENT_DATE')->fetchColumn();
    $start  = input(INPUT_GET, 'startDate') ?: (new DateTimeImmutable($today))->modify('monday this week')->format('Y-m-d');
    $end    = input(INPUT_GET, 'endDate') ?: $today;
}

$isAlreadySaved = $saved
    && $saved['reportType'] === $report
    && $saved['startDate'] === $start
    && $saved['endDate'] === $end;

$periods = [];
$error = '';
$message = input(INPUT_GET, 'saved') === '1' ? 'Report saved! It will stay loaded when you navigate back to Report Summary.' : '';
$minReportDate = '2020-01-01';
$maxReportDate = date('Y-m-d', strtotime('+1 year'));

if (!in_array($report, ['weekly', 'monthly'], true)
    || !validDate($start, $minReportDate, $maxReportDate)
    || !validDate($end, $minReportDate, $maxReportDate)
    || $start > $end) {
    http_response_code(422);
    $error = "Choose a valid report type and a date range between $minReportDate and $maxReportDate (Start Date must be on or before End Date).";
} else {
    $bucket = $report === 'monthly'
        ? "DATE_FORMAT(SaleDate, '%Y-%m-01')"
        : 'DATE_SUB(DATE(SaleDate), INTERVAL WEEKDAY(SaleDate) DAY)';
    $stmt = $pdo->prepare("
        SELECT $bucket AS PeriodStart, COUNT(*) AS TotalSales,
               SUM(TotalCost) AS TotalCost, SUM(TotalRevenue) AS TotalRevenue,
               SUM(TotalRevenue - TotalCost) AS TotalProfit
        FROM SALE
        WHERE SaleDate >= ? AND SaleDate <= CONCAT(?, ' 23:59:59')
        GROUP BY PeriodStart ORDER BY PeriodStart
    ");
    $stmt->execute([$start, $end]);
    $periods = $stmt->fetchAll();
}
?>
<?php pageStart('Report Summary', 'Sales Reports', 'reports'); ?>

<main>
  <aside class="sections">
    <a href="reports.php">Generate Report</a>
    <a href="report-summary.php" class="active">Report Summary</a>
  </aside>

  <section class="content">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
      <h3 style="margin: 0;">Report Summary</h3>
      <?php if (!$error && !$isAlreadySaved): ?>
        <form method="post" style="margin: 0;">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="save_report">
          <input type="hidden" name="reportType" value="<?= e($report) ?>">
          <input type="hidden" name="startDate" value="<?= e($start) ?>">
          <input type="hidden" name="endDate" value="<?= e($end) ?>">
          <button type="submit" style="margin: 0; padding: 8px 18px; font-size: 0.9rem;">Save Report</button>
        </form>
      <?php elseif (!$error && $isAlreadySaved): ?>
        <span class="btn-pill" style="margin: 0; cursor: default;">✓ Saved Report</span>
      <?php endif; ?>
    </div>

    <?php pageMessage($error, $message); ?>
    <?php if (!$error): ?>
      <p class="note"><?= e(ucfirst($report)) ?> sales from <?= e($start) ?> to <?= e($end) ?> (<strong><?= count($periods) ?></strong> <?= count($periods) === 1 ? 'period' : 'periods' ?>).</p>
    <?php endif; ?>

    <div class="table-wrap">
      <table>
        <thead>
        <tr>
          <th>Period</th>
          <th>Total Sales</th>
          <th>Total Cost</th>
          <th>Total Revenue</th>
          <th>Total Profit</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($periods as $period):
            $first = new DateTimeImmutable($period['PeriodStart']);
            $last = $first->modify($report === 'monthly' ? 'last day of this month' : '+6 days');
        ?>
          <tr>
            <td><?= e(max($first->format('Y-m-d'), $start)) ?> to <?= e(min($last->format('Y-m-d'), $end)) ?></td>
            <td><?= e($period['TotalSales']) ?></td>
            <td><?= e($period['TotalCost']) ?></td>
            <td><?= e($period['TotalRevenue']) ?></td>
            <td><?= e($period['TotalProfit']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$periods): ?><tr><td colspan="5">No sales to display.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

</body>
</html>
