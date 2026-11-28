<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount(true);

$error = '';
$reportType = input(INPUT_GET, 'reportType');
$startDate  = input(INPUT_GET, 'startDate');
$endDate    = input(INPUT_GET, 'endDate');

$minReportDate = '2020-01-01';
$maxReportDate = date('Y-m-d', strtotime('+1 year'));

// Validate inline on Generate Report before redirecting to Report Summary
if (isset($_GET['generate'])) {
    if (!in_array($reportType, ['weekly', 'monthly'], true)) {
        http_response_code(422);
        $error = 'Select a valid Report Type (Weekly or Monthly).';
    } elseif (!validDate($startDate, $minReportDate, $maxReportDate) || !validDate($endDate, $minReportDate, $maxReportDate)) {
        http_response_code(422);
        $error = "Enter a valid Start Date and End Date between $minReportDate and $maxReportDate.";
    } elseif ($startDate > $endDate) {
        http_response_code(422);
        $error = 'Start Date must be on or before End Date.';
    } else {
        redirect('report-summary.php?' . http_build_query([
            'reportType' => $reportType,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
        ]));
    }
}
?>
<?php pageStart('Reports', 'Sales Reports', 'reports'); ?>

<main>
  <aside class="sections">
    <a href="reports.php" class="active">Generate Report</a>
    <a href="report-summary.php">Report Summary</a>
  </aside>

  <section class="content" style="min-height: 62vh; display: flex; flex-direction: column; justify-content: center;">
    <h3>Generate Report</h3>
    <?php pageMessage($error); ?>

    <form action="reports.php" method="get">
      <input type="hidden" name="generate" value="1">

      <label for="reportType">Report Type:</label>
      <select id="reportType" name="reportType" required>
        <option value="">Select Report Type</option>
        <option value="weekly" <?= $reportType === 'weekly' ? 'selected' : '' ?>>Weekly</option>
        <option value="monthly" <?= $reportType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
      </select>
      <br><br>

      <label for="startDate">Start Date:</label>
      <input type="date" id="startDate"
             name="startDate"
             min="<?= $minReportDate ?>"
             max="<?= $maxReportDate ?>"
             value="<?= e($startDate) ?>" required>
      <br><br>

      <label for="endDate">End Date:</label>
      <input type="date" id="endDate"
             name="endDate"
             min="<?= $minReportDate ?>"
             max="<?= $maxReportDate ?>"
             value="<?= e($endDate) ?>" required>
      <br><br>

      <button type="submit">Generate Report</button>
      <a href="reports.php" class="btn-secondary">Clear Form</a>
    </form>
  </section>
</main>

</body>
</html>
