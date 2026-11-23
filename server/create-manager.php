<?php
require __DIR__ . '/accounts.php';

$username = strtolower(trim($argv[1] ?? ''));
$password = rtrim((string) fgets(STDIN), "\r\n");

try {
    changeAccounts(function (array &$accounts) use ($username, $password) {
        saveAccount($accounts, $username, $password, 'manager');
    });
    echo "Manager account saved.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
