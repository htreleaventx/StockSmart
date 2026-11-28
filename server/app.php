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
    $trimmed = trim($name);
    if (mb_strlen($trimmed) < 2 || mb_strlen($trimmed) > 100) {
        return false;
    }
    if (!preg_match('/[a-zA-Z]/', $trimmed) || !preg_match('/\A[a-zA-Z0-9\s\-\'&.,()%\/]+\z/', $trimmed)) {
        return false;
    }

    // Strip measurement units (e.g., "1 gal", "12 oz", "6 ct")
    $query = trim(preg_replace('/\b\d+(\.\d+)?\s*(oz|fl oz|lb|lbs|g|kg|ml|l|gal|qt|pt|ct|pk|pack|ft|sq ft|roll|rolls)\b/i', '', $trimmed));
    if ($query === '') {
        return false;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 2.5,
            'ignore_errors' => true,
            'header' => "User-Agent: StockSmart-CS4347/1.0 (student-project@utdallas.edu)\r\n",
        ],
    ]);

    // 1. Query Open Facts v2 Search API across Food, Retail Products, Beauty & Pet Food
    $domains = [
        'https://openfoodfacts.org',
        'https://openproductsfacts.org',
        'https://openbeautyfacts.org',
        'https://openpetfoodfacts.org',
    ];

    foreach ($domains as $domain) {
        $url = $domain . '/api/v2/search?search_terms=' . rawurlencode($query) . '&page_size=1&fields=product_name';
        $json = @file_get_contents($url, false, $ctx);
        if ($json) {
            $data = json_decode($json, true);
            if (!empty($data['products'])) {
                return true;
            }
        }
    }

    // 2. Fallback: Wikipedia REST Summary API (automatically resolves plurals like "Paper Towels" -> "Paper towel")
    $wikiTitle = str_replace(' ', '_', ucfirst(strtolower($query)));
    $wikiUrl = 'https://wikipedia.org' . rawurlencode($wikiTitle);
    $wikiJson = @file_get_contents($wikiUrl, false, $ctx);
    if ($wikiJson) {
        $wiki = json_decode($wikiJson, true);
        $extract = strtolower(($wiki['description'] ?? '') . ' ' . ($wiki['extract'] ?? ''));
        if (!empty($wiki['title']) && ($wiki['type'] ?? '') === 'standard') {
            if (!preg_match('/\b(software|application|platform|website|video game|film|movie|television|song|album|band|company|corporation|person|actor|politician|city|country)\b/', $extract)) {
                return true;
            }
        }
    }

    return false;
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
