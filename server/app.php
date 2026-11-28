<?php
require_once __DIR__ . '/accounts.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function postRequest(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function input(int $source, string $name): string
{
    return (string) filter_input($source, $name);
}

function integer(int $source, string $name, int $min = 1, int $max = 2147483647): ?int
{
    $value = filter_input($source, $name, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max],
    ]);
    return is_int($value) ? $value : null;
}

function validDate(string $value, ?string $minDate = null, ?string $maxDate = null): bool
{
    if (!preg_match('/\A([1-9]\d{3})-(\d{2})-(\d{2})\z/', $value, $parts)
        || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
        return false;
    }
    if ($minDate !== null && $value < $minDate) {
        return false;
    }
    if ($maxDate !== null && $value > $maxDate) {
        return false;
    }
    return true;
}

function validGroceryItem(string $name): bool
{
    if (!preg_match('/[a-zA-Z]/', $name) || !preg_match('/\A[a-zA-Z0-9\s\-\'&.,()%\/]+\z/', $name)) {
        return false;
    }
    $blocked = '/\b(laptop|phone|iphone|macbook|ps5|xbox|television|tv|shirt|shoes|sneakers|car|tire|hammer|drill|furniture|sofa|chair|desk|book|toy|perfume|makeup)\b/i';
    return !preg_match($blocked, $name);
}

function login(string $username, string $password): bool
{
    $account = readAccounts()[$username] ?? null;
    if (!$account || !password_verify($password, $account['password'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION = ['username' => $username, 'credential' => hash('sha256', $account['password'])];
    return true;
}

function requireAccount(bool $manager = false): void
{
    global $isManager;
    $username = $_SESSION['username'] ?? '';
    $account = $username !== '' ? (readAccounts()[$username] ?? null) : null;
    if (!$account || !hash_equals(hash('sha256', $account['password']), $_SESSION['credential'] ?? '')) {
        redirect('login.php');
    }
    $isManager = $account['role'] === 'manager';
    if ($manager && !$isManager) {
        http_response_code(403);
        exit('Manager access is required.');
    }
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function requireCsrf(): void
{
    if (!hash_equals($_SESSION['csrf'], input(INPUT_POST, 'csrf'))) {
        http_response_code(403);
        exit('Reload the form and try again.');
    }
}

function categories(PDO $pdo): array
{
    return $pdo->query('SELECT CategoryID, CategoryName FROM CATEGORY ORDER BY CategoryName')->fetchAll();
}

function categoryFilter(array $categories): int|false|null
{
    // An omitted category or the empty "All Categories" option leaves the results unfiltered.
    $value = filter_input(INPUT_GET, 'category');
    if ($value === null || $value === '') {
        return null;
    }

    $category = integer(INPUT_GET, 'category');
    return in_array($category, array_column($categories, 'CategoryID'), true) ? $category : false;
}

function categoryOptions(array $categories, int|string|null $selected): void
{
    foreach ($categories as $category) {
        $id = (int) $category['CategoryID'];
        $attribute = $id === (int) $selected ? ' selected' : '';
        echo '<option value="' . $id . '"' . $attribute . '>' . e($category['CategoryName']) . '</option>';
    }
}

session_start([
    'use_strict_mode' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
