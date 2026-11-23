<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount(true);
?>
<?php pageStart('Reports', 'Sales Reports', 'reports'); ?>

<main>
  <aside class="sections">
    <a href="reports.php" class="active">Generate Report</a>
    <a href="report-summary.php">Report Summary</a>
  </aside>

  <section class="content">
    <h3>Generate Report</h3>

    <form action="report-summary.php" method="get">

      <label for="reportType">Report Type:</label>
      <select id="reportType" name="reportType" required>
        <option value="">Select Report Type</option>
        <option value="weekly">Weekly</option>
        <option value="monthly">Monthly</option>
      </select>
      <br><br>

      <label for="startDate">Start Date:</label>
      <input type="date" id="startDate"
             name="startDate" required>
      <br><br>

      <label for="endDate">End Date:</label>
      <input type="date" id="endDate"
             name="endDate" required>
      <br><br>

      <button type="submit">Generate Report</button>
      <button type="reset">Clear Form</button>
    </form>
  </section>
</main>

</body>
</html>
