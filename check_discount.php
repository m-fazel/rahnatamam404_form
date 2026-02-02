<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

function clean_input($value)
{
    return trim((string) $value);
}

function normalize_digits($value)
{
    $digitMap = [
        '۰' => '0',
        '۱' => '1',
        '۲' => '2',
        '۳' => '3',
        '۴' => '4',
        '۵' => '5',
        '۶' => '6',
        '۷' => '7',
        '۸' => '8',
        '۹' => '9',
        '٠' => '0',
        '١' => '1',
        '٢' => '2',
        '٣' => '3',
        '٤' => '4',
        '٥' => '5',
        '٦' => '6',
        '٧' => '7',
        '٨' => '8',
        '٩' => '9',
    ];

    return strtr((string) $value, $digitMap);
}

function fetch_discount_code(PDO $pdo, $code)
{
    if ($code === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, title, discount_type, discount_value FROM discount_codes WHERE code = :code AND is_active = 1 LIMIT 1');
    $stmt->execute([':code' => $code]);
    $discount = $stmt->fetch();
    if (!$discount) {
        return null;
    }

    return $discount;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'valid' => false,
        'message' => 'روش درخواست نامعتبر است.',
    ]);
    exit;
}

$pdo = null;
try {
    $pdo = get_pdo($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'message' => 'خطای اتصال به پایگاه داده رخ داده است.',
    ]);
    exit;
}

$code = strtoupper(clean_input(normalize_digits($_POST['code'] ?? '')));

if ($code === '') {
    http_response_code(422);
    echo json_encode([
        'valid' => false,
        'message' => 'کد تخفیف وارد نشده است.',
    ]);
    exit;
}

$discount = fetch_discount_code($pdo, $code);

if (!$discount) {
    http_response_code(404);
    echo json_encode([
        'valid' => false,
        'message' => 'کد تخفیف معتبر نیست.',
    ]);
    exit;
}

echo json_encode([
    'valid' => true,
    'message' => 'کد تخفیف معتبر است.',
    'discount' => [
        'id' => (int) $discount['id'],
        'title' => $discount['title'],
        'type' => $discount['discount_type'],
        'value' => (int) $discount['discount_value'],
    ],
]);
