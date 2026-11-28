<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';
requireAccount(true);

$error = '';
if (postRequest()) {
    requireCsrf();
    $username = strtolower(trim(input(INPUT_POST, 'username')));
    try {
        changeAccounts(function (array &$accounts) use ($username) {
            $actor = $accounts[$_SESSION['username']] ?? null;
            if (!$actor || $actor['role'] !== 'manager'
                || !hash_equals(hash('sha256', $actor['password']), $_SESSION['credential'])) {
                throw new DomainException('Your manager session is no longer valid.');
            }
            if ($username === $_SESSION['username']) {
                throw new DomainException('Use another manager account to change your own account.');
            }
            if (input(INPUT_POST, 'action') === 'delete') {
                unset($accounts[$username]);
                return;
            }
            saveAccount($accounts, $username, input(INPUT_POST, 'password'), input(INPUT_POST, 'role'));
        });
        redirect('accounts.php?saved=1');
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        http_response_code(500);
        $error = 'Could not save the account changes.';
    }
}

$accounts = readAccounts();
ksort($accounts);
pageStart('User Management', 'User Management', 'dashboard');
?>
<main>
  <aside class="sections"><a href="index.php">Dashboard</a></aside>
  <section class="content">
    <h3>User Management</h3>
    <?php pageMessage($error, input(INPUT_GET, 'saved') === '1' ? 'Account changes saved.' : ''); ?>
    <form method="post">
      <?= csrfField() ?>
      <label for="username">Username:</label>
      <input type="text" id="username" name="username" required><br>
      <label for="role">Role:</label>
      <select id="role" name="role">
        <option value="employee">Employee</option>
        <option value="manager">Manager</option>
      </select><br>
      <div class="form-group">
        <label for="password">New Password:</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required>
      </div>
      <button type="submit">Save Account</button>
    </form>
        <div class="table-wrap" style="margin-top: 20px;">
      <table>
        <thead><tr><th>Username</th><th>Role</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $username => $account): ?>
          <tr>
            <td><?= e($username) ?></td>
            <td><?= e(ucfirst($account['role'])) ?></td>
            <td>
              <?php if ($username !== $_SESSION['username']): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this account?');">
                  <?= csrfField() ?>
                  <input type="hidden" name="username" value="<?= e($username) ?>">
                  <button type="submit" name="action" value="delete">Delete</button>
                </form>
              <?php else: ?>
                <span class="note">(Current user)</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
</body>
</html>
