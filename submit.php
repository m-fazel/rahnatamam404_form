<?php
require_once __DIR__ . '/config.php';

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

function sanitize_numeric($value)
{
    $normalized = normalize_digits($value);
    return preg_replace('/\D+/', '', $normalized);
}

function is_valid_national_code($value)
{
    $code = sanitize_numeric($value);
    if (strlen($code) !== 10) {
        return false;
    }
    $sum = 0;
    for ($i = 0; $i < 9; $i += 1) {
        $sum += (int) $code[$i] * (10 - $i);
    }
    $remainder = $sum % 11;
    $checkDigit = (int) $code[9];
    if ($remainder < 2) {
        return $checkDigit === $remainder;
    }

    return $checkDigit === (11 - $remainder);
}

function is_valid_mobile($value)
{
    $mobile = sanitize_numeric($value);
    return (bool) preg_match('/^09\d{9}$/', $mobile);
}

function is_registered_national_code(PDO $pdo, $code)
{
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM national_codes WHERE code = :code LIMIT 1');
        $stmt->execute([':code' => $code]);
        if ($stmt->fetchColumn()) {
            return true;
        }
    } catch (PDOException $e) {
        // If the table does not exist yet, fall back to other checks.
    }

    $stmt = $pdo->prepare('SELECT 1 FROM registrations WHERE national_code = :code OR spouse_national_code = :code LIMIT 1');
    $stmt->execute([':code' => $code]);

    if ($stmt->fetchColumn()) {
        return true;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM group_members WHERE national_code = :code LIMIT 1');
    $stmt->execute([':code' => $code]);

    return (bool) $stmt->fetchColumn();
}

function calculate_amount($registrationType, $studentMode, $entryYear, $marriedStatus, $childrenCount)
{
    $amountTable = [
        'student' => [
            '1404' => ['individual' => 500, 'group' => 1200],
            '1403' => ['individual' => 600, 'group' => 1500],
            '1402_or_before' => ['individual' => 700, 'group' => 1800],
        ],
        'alumni' => 1000,
        'married' => [
            'married_student' => 1500,
            'married_alumni' => 1800,
            'married_other' => 2500,
        ],
        'other' => 1500,
    ];

    $base = 0;

    if ($registrationType === 'student') {
        $base = $amountTable['student'][$entryYear][$studentMode] ?? 0;
    } elseif ($registrationType === 'married') {
        $base = $amountTable['married'][$marriedStatus] ?? 0;
    } else {
        $base = $amountTable[$registrationType] ?? 0;
    }

    $childrenTotal = $registrationType === 'married' ? max((int) $childrenCount, 0) * 5000000 : 0;
    return ($base * 10000) + $childrenTotal;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$registrationType = clean_input($_POST['registration_type'] ?? '');
$studentMode = clean_input($_POST['student_mode'] ?? '');
$entryYear = clean_input($_POST['entry_year'] ?? '');
$marriedStatus = clean_input($_POST['married_status'] ?? '');

$firstName = clean_input($_POST['first_name'] ?? '');
$lastName = clean_input($_POST['last_name'] ?? '');
$gender = clean_input($_POST['gender'] ?? '');
$nationalCode = sanitize_numeric($_POST['national_code'] ?? '');
$birthDate = clean_input($_POST['birth_date'] ?? '');
$mobile = sanitize_numeric($_POST['mobile'] ?? '');

$spouseName = clean_input($_POST['spouse_name'] ?? '');
$spouseNationalCode = sanitize_numeric($_POST['spouse_national_code'] ?? '');
$spouseBirthDate = clean_input($_POST['spouse_birth_date'] ?? '');
$childrenCount = clean_input($_POST['children_count'] ?? '');

if ($registrationType === '') {
    exit('نوع ثبت نام مشخص نشده است.');
}

if ($registrationType === 'student') {
    if ($studentMode === '' || $entryYear === '') {
        exit('لطفا نوع ثبت نام دانشجو و سال ورودی را مشخص کنید.');
    }
}

if ($registrationType === 'married') {
    if ($marriedStatus === '' || $spouseName === '' || $spouseNationalCode === '' || $spouseBirthDate === '') {
        exit('لطفا اطلاعات همسر و وضعیت تحصیلی را کامل کنید.');
    }
    if (!is_valid_national_code($spouseNationalCode)) {
        exit('کد ملی همسر معتبر نیست.');
    }
}

if ($firstName === '' || $lastName === '' || $gender === '' || $nationalCode === '' || $birthDate === '' || $mobile === '') {
    exit('لطفا تمام مشخصات فردی را وارد کنید.');
}

if (!is_valid_national_code($nationalCode)) {
    exit('کد ملی وارد شده معتبر نیست.');
}

if (!is_valid_mobile($mobile)) {
    exit('شماره تماس وارد شده معتبر نیست.');
}

$groupMembers = $_POST['group_members'] ?? [];
$groupCount = (int) ($_POST['group_count'] ?? 0);

if ($registrationType === 'student' && $studentMode === 'group') {
    if ($groupCount !== 3 || !is_array($groupMembers) || count($groupMembers) !== 2) {
        exit('ثبت نام گروهی فقط برای ۳ نفر امکان‌پذیر است.');
    }

    foreach ($groupMembers as $member) {
        $memberFirst = clean_input($member['first_name'] ?? '');
        $memberLast = clean_input($member['last_name'] ?? '');
        $memberGender = clean_input($member['gender'] ?? '');
        $memberNational = sanitize_numeric($member['national_code'] ?? '');
        $memberBirth = clean_input($member['birth_date'] ?? '');
        $memberMobile = sanitize_numeric($member['mobile'] ?? '');

        if ($memberFirst === '' || $memberLast === '' || $memberGender === '' || $memberNational === '' || $memberBirth === '' || $memberMobile === '') {
            exit('لطفا مشخصات تمام اعضای گروه را کامل کنید.');
        }

        if (!is_valid_national_code($memberNational)) {
            exit('کد ملی اعضای گروه معتبر نیست.');
        }

        if (!is_valid_mobile($memberMobile)) {
            exit('شماره تماس اعضای گروه معتبر نیست.');
        }
    }
}

$pdo = get_pdo($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);

$submittedCodeRecords = [
    ['code' => $nationalCode, 'role' => 'primary'],
];

if ($spouseNationalCode !== '') {
    $submittedCodeRecords[] = ['code' => $spouseNationalCode, 'role' => 'spouse'];
}

if ($registrationType === 'student' && $studentMode === 'group') {
    foreach ($groupMembers as $index => $member) {
        $memberNational = sanitize_numeric($member['national_code'] ?? '');
        if ($memberNational !== '') {
            $submittedCodeRecords[] = [
                'code' => $memberNational,
                'role' => sprintf('group_member_%d', $index + 2),
            ];
        }
    }
}

$enableDuplicateCheck = false;
$submittedCodes = array_map(static fn ($record) => $record['code'], $submittedCodeRecords);

if ($enableDuplicateCheck) {
    if (count($submittedCodes) !== count(array_unique($submittedCodes))) {
        exit('کد ملی تکراری در فرم وارد شده است.');
    }

    foreach ($submittedCodes as $code) {
        if (is_registered_national_code($pdo, $code)) {
            exit('کد ملی قبلا ثبت شده است و امکان ثبت مجدد وجود ندارد.');
        }
    }
}

$amount = calculate_amount($registrationType, $studentMode, $entryYear, $marriedStatus, $childrenCount);
if ($amount <= 0) {
    exit('مبلغ قابل محاسبه نیست.');
}

$formattedAmount = number_format($amount);

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare('INSERT INTO registrations (registration_type, student_mode, entry_year, married_status, amount, formatted_amount, first_name, last_name, gender, national_code, birth_date, mobile, spouse_name, spouse_national_code, spouse_birth_date, children_count, created_at) VALUES (:registration_type, :student_mode, :entry_year, :married_status, :amount, :formatted_amount, :first_name, :last_name, :gender, :national_code, :birth_date, :mobile, :spouse_name, :spouse_national_code, :spouse_birth_date, :children_count, NOW())');
    $stmt->execute([
        ':registration_type' => $registrationType,
        ':student_mode' => $studentMode ?: null,
        ':entry_year' => $entryYear ?: null,
        ':married_status' => $marriedStatus ?: null,
        ':amount' => $amount,
        ':formatted_amount' => $formattedAmount,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':gender' => $gender,
        ':national_code' => $nationalCode,
        ':birth_date' => $birthDate,
        ':mobile' => $mobile,
        ':spouse_name' => $spouseName ?: null,
        ':spouse_national_code' => $spouseNationalCode ?: null,
        ':spouse_birth_date' => $spouseBirthDate ?: null,
        ':children_count' => $childrenCount === '' ? null : (int) $childrenCount,
    ]);

    $registrationId = (int) $pdo->lastInsertId();

    if ($registrationType === 'student' && $studentMode === 'group') {
        $memberStmt = $pdo->prepare('INSERT INTO group_members (registration_id, first_name, last_name, gender, national_code, birth_date, mobile, created_at) VALUES (:registration_id, :first_name, :last_name, :gender, :national_code, :birth_date, :mobile, NOW())');

        foreach ($groupMembers as $member) {
            $memberStmt->execute([
                ':registration_id' => $registrationId,
                ':first_name' => clean_input($member['first_name'] ?? ''),
                ':last_name' => clean_input($member['last_name'] ?? ''),
                ':gender' => clean_input($member['gender'] ?? ''),
                ':national_code' => sanitize_numeric($member['national_code'] ?? ''),
                ':birth_date' => clean_input($member['birth_date'] ?? ''),
                ':mobile' => sanitize_numeric($member['mobile'] ?? ''),
            ]);
        }
    }

    if ($enableDuplicateCheck) {
        try {
            $codeStmt = $pdo->prepare('INSERT INTO national_codes (code, registration_id, role, created_at) VALUES (:code, :registration_id, :role, NOW())');
            foreach ($submittedCodeRecords as $record) {
                $codeStmt->execute([
                    ':code' => $record['code'],
                    ':registration_id' => $registrationId,
                    ':role' => $record['role'],
                ]);
            }
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) !== 1146) {
                throw $e;
            }
        }
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    if (($e->errorInfo[1] ?? null) === 1062 || $e->getCode() === '23000') {
        exit('کد ملی قبلا ثبت شده است و امکان ثبت مجدد وجود ندارد.');
    }
    exit('خطا در ذخیره اطلاعات.');
} catch (Exception $e) {
    $pdo->rollBack();
    exit('خطا در ذخیره اطلاعات.');
}

$paymentData = [
    'Ch' => 1,
    'FN' => 'User_Payment_Insert',
    'TerminalID' => 160539,
    'Amount' => (string) $amount,
    'Formatted_Amount' => $formattedAmount,
    'IsForeigner' => false,
    'NC' => $nationalCode,
    'Name' => '',
    'Family' => '',
    'Tel' => '',
    'Mobile' => '',
    'EMail' => '',
    'Memo' => '',
    'Memo2' => '',
];

$ch = curl_init('https://pay.sharif.edu/api/API');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'Input' => json_encode($paymentData, JSON_UNESCAPED_UNICODE),
]);

$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    exit('خطا در ارتباط با درگاه پرداخت.');
}

$result = json_decode($response, true);
if (!is_array($result) || ($result['Result'] ?? null) !== 0) {
    exit('ایجاد درگاه پرداخت با خطا مواجه شد.');
}

$orderId = (int) $result['OrderID'];
$orderGuid = (string) $result['OrderGUID'];

$updateStmt = $pdo->prepare('UPDATE registrations SET payment_order_id = :order_id, payment_order_guid = :order_guid WHERE id = :id');
$updateStmt->execute([
    ':order_id' => $orderId,
    ':order_guid' => $orderGuid,
    ':id' => $registrationId,
]);

$redirectUrl = sprintf('https://pay.sharif.edu/submit2/%s/%s', $orderId, $orderGuid);
header('Location: ' . $redirectUrl);
exit;
