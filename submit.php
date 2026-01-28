<?php
require_once __DIR__ . '/config.php';

function clean_input($value)
{
    return trim((string) $value);
}

function calculate_amount($registrationType, $studentMode, $entryYear, $marriedStatus)
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

    return $base * 10000;
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
$nationalCode = clean_input($_POST['national_code'] ?? '');
$birthDate = clean_input($_POST['birth_date'] ?? '');
$mobile = clean_input($_POST['mobile'] ?? '');

$spouseName = clean_input($_POST['spouse_name'] ?? '');
$spouseNationalCode = clean_input($_POST['spouse_national_code'] ?? '');
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
}

if ($firstName === '' || $lastName === '' || $gender === '' || $nationalCode === '' || $birthDate === '' || $mobile === '') {
    exit('لطفا تمام مشخصات فردی را وارد کنید.');
}

$groupMembers = $_POST['group_members'] ?? [];
$groupCount = (int) ($_POST['group_count'] ?? 0);

if ($registrationType === 'student' && $studentMode === 'group') {
    if ($groupCount < 2 || !is_array($groupMembers) || count($groupMembers) < 2) {
        exit('حداقل دو نفر برای ثبت نام گروهی لازم است.');
    }

    foreach ($groupMembers as $member) {
        $memberFirst = clean_input($member['first_name'] ?? '');
        $memberLast = clean_input($member['last_name'] ?? '');
        $memberGender = clean_input($member['gender'] ?? '');
        $memberNational = clean_input($member['national_code'] ?? '');
        $memberBirth = clean_input($member['birth_date'] ?? '');
        $memberMobile = clean_input($member['mobile'] ?? '');

        if ($memberFirst === '' || $memberLast === '' || $memberGender === '' || $memberNational === '' || $memberBirth === '' || $memberMobile === '') {
            exit('لطفا مشخصات تمام اعضای گروه را کامل کنید.');
        }
    }
}

$amount = calculate_amount($registrationType, $studentMode, $entryYear, $marriedStatus);
if ($amount <= 0) {
    exit('مبلغ قابل محاسبه نیست.');
}

$formattedAmount = number_format($amount);

$pdo = get_pdo($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);

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
                ':national_code' => clean_input($member['national_code'] ?? ''),
                ':birth_date' => clean_input($member['birth_date'] ?? ''),
                ':mobile' => clean_input($member['mobile'] ?? ''),
            ]);
        }
    }

    $pdo->commit();
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
