<?php

function accountFile(): string
{
    return getenv('STOCKSMART_ACCOUNTS_FILE') ?: dirname(__DIR__) . '/var/accounts.json';
}

function readAccounts(): array
{
    $path = accountFile();
    if (!is_file($path)) {
        return [];
    }
    return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

function changeAccounts(callable $change): void
{
    $path = accountFile();
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the account directory.');
    }

    $lock = fopen($path . '.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Could not lock the account store.');
    }

    $temp = false;
    try {
        $accounts = readAccounts();
        $change($accounts);
        $temp = tempnam($directory, 'accounts-');
        $data = json_encode($accounts, JSON_THROW_ON_ERROR);
        if (!$temp || !chmod($temp, 0600) || file_put_contents($temp, $data) !== strlen($data)
            || !rename($temp, $path)) {
            throw new RuntimeException('Could not save accounts.');
        }
    } finally {
        if ($temp && is_file($temp)) {
            unlink($temp);
        }
        fclose($lock);
    }
}

function saveAccount(array &$accounts, string $username, string $password, string $role): void
{
    if (!preg_match('/\A[a-z][a-z0-9_.-]{2,39}\z/', $username)
        || !in_array($role, ['manager', 'employee'], true)) {
        throw new DomainException('Use a valid role and a username of 3–40 characters starting with a letter.');
    }
    if (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
        throw new DomainException('Enter a password of 8–72 bytes.');
    }
    $accounts[$username] = ['role' => $role, 'password' => password_hash($password, PASSWORD_DEFAULT)];
}
