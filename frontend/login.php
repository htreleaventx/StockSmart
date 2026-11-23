<?php
require __DIR__ . '/../server/app.php';
require __DIR__ . '/../server/layout.php';

$error = '';
if (postRequest()) {
    requireCsrf();
    if (input(INPUT_POST, 'action') === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        redirect('login.php');
    }

    $username = strtolower(trim(input(INPUT_POST, 'username')));
    if (login($username, input(INPUT_POST, 'password'))) {
        redirect('index.php');
    }
    http_response_code(401);
    $error = 'The username or password is incorrect.';
}

pageStart('Sign In', 'Sign In');
?>
<main style="margin-top: 36px;">
  <aside class="sections"></aside>
  <section class="content">
    <h3>Sign In</h3>
    <?php pageMessage($error); ?>
    <form method="post">
      <?= csrfField() ?>
      <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" autocomplete="username" required>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit">Sign In</button>
    </form>
  </section>
</main>
</body>
</html>
