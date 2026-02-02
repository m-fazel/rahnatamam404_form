<?php
session_start();
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

function normalize_captcha($value)
{
    $normalized = normalize_digits($value);
    $normalized = preg_replace('/\s+/', '', (string) $normalized);
    return strtoupper($normalized);
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

    $stmt = $pdo->prepare('SELECT 1 FROM registrations WHERE (national_code = :code OR spouse_national_code = :code) AND payment_status_id = 0 LIMIT 1');
    $stmt->execute([':code' => $code]);

    if ($stmt->fetchColumn()) {
        return true;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM group_members INNER JOIN registrations ON registrations.id = group_members.registration_id WHERE group_members.national_code = :code AND registrations.payment_status_id = 0 LIMIT 1');
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

    $childrenTotal = max((int) $childrenCount, 0) * 0;
    return ($base * 10000) + $childrenTotal;
}

function fetch_discount_code(PDO $pdo, $code)
{
    if ($code === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM discount_codes WHERE code = :code AND is_active = 1 LIMIT 1');
    $stmt->execute([':code' => $code]);
    $discount = $stmt->fetch();
    if (!$discount) {
        return null;
    }

    return $discount;
}

function apply_discount($amount, array $discount = null)
{
    if (!$discount) {
        return [$amount, 0];
    }

    $discountAmount = 0;
    $type = $discount['discount_type'] ?? 'amount';
    $value = (int) ($discount['discount_value'] ?? 0);
    if ($value <= 0) {
        return [$amount, 0];
    }

    if ($type === 'percent') {
        $discountAmount = (int) round($amount * ($value / 100));
    } else {
        $discountAmount = $value * 10000;
    }

    $finalAmount = max($amount - $discountAmount, 0);
    return [$finalAmount, $discountAmount];
}

function redirect_with_error($message)
{
    $_SESSION['form_error'] = $message;
    $_SESSION['security_code'] = (string) random_int(10000, 99999);
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$registrationType = clean_input($_POST['registration_type'] ?? '');
$studentMode = $registrationType === 'student' ? clean_input($_POST['student_mode'] ?? '') : '';
$entryYear = $registrationType === 'student' ? clean_input($_POST['entry_year'] ?? '') : '';
$marriedStatus = $registrationType === 'married' ? clean_input($_POST['married_status'] ?? '') : '';
$academicMajor = '';
$academicLevel = '';
if ($registrationType === 'student' || $registrationType === 'alumni') {
    $academicMajor = clean_input($_POST['academic_major'] ?? '');
    $academicLevel = clean_input($_POST['academic_level'] ?? '');
}
if ($registrationType === 'alumni') {
    $entryYear = sanitize_numeric($_POST['alumni_entry_year'] ?? '');
}

$firstName = clean_input($_POST['first_name'] ?? '');
$lastName = clean_input($_POST['last_name'] ?? '');
$gender = clean_input($_POST['gender'] ?? '');
$nationalCode = sanitize_numeric($_POST['national_code'] ?? '');
$birthDate = clean_input($_POST['birth_date'] ?? '');
$mobile = sanitize_numeric($_POST['mobile'] ?? '');
$paymentType = clean_input($_POST['payment_type'] ?? '');
$discountCodeInput = strtoupper(clean_input($_POST['discount_code'] ?? ''));
$securityCode = normalize_captcha($_POST['security_code'] ?? '');
$sessionCode = normalize_captcha($_SESSION['security_code'] ?? '');

$spouseName = $registrationType === 'married' ? clean_input($_POST['spouse_name'] ?? '') : '';
$spouseNationalCode = $registrationType === 'married' ? sanitize_numeric($_POST['spouse_national_code'] ?? '') : '';
$spouseBirthDate = $registrationType === 'married' ? clean_input($_POST['spouse_birth_date'] ?? '') : '';
$childrenCount = $registrationType === 'married' ? clean_input($_POST['children_count'] ?? '') : 0;

if ($registrationType === '') {
    redirect_with_error('نوع ثبت نام مشخص نشده است.');
}

if ($registrationType === 'student') {
    if ($studentMode === '' || $entryYear === '') {
        redirect_with_error('لطفا نوع ثبت نام دانشجو و سال ورودی را مشخص کنید.');
    }
    if ($academicLevel === '') {
        redirect_with_error('لطفا مقطع تحصیلی دانشجو را مشخص کنید.');
    }
    if ($academicMajor === '') {
        redirect_with_error('لطفا رشته تحصیلی دانشجو را مشخص کنید.');
    }
    if ($paymentType === '') {
        redirect_with_error('لطفا نوع پرداخت دانشجو را مشخص کنید.');
    }
}

if ($registrationType === 'alumni') {
    if ($academicLevel === '') {
        redirect_with_error('لطفا مقطع تحصیلی فارغ التحصیل را مشخص کنید.');
    }
    if ($academicMajor === '') {
        redirect_with_error('لطفا رشته تحصیلی فارغ التحصیل را مشخص کنید.');
    }
    $entryYearInt = (int) $entryYear;
    if ($entryYear === '' || $entryYearInt < 1345 || $entryYearInt > 1404) {
        redirect_with_error('سال ورودی فارغ التحصیل باید بین ۱۳۴۵ تا ۱۴۰۴ باشد.');
    }
    if ($paymentType === '') {
        redirect_with_error('لطفا نوع پرداخت فارغ التحصیل را مشخص کنید.');
    }
}

if ($registrationType === 'married') {
    if ($marriedStatus === '' || $spouseName === '' || $spouseNationalCode === '' || $spouseBirthDate === '') {
        redirect_with_error('لطفا اطلاعات همسر و وضعیت تحصیلی را کامل کنید.');
    }
    if (!is_valid_national_code($spouseNationalCode)) {
        redirect_with_error('کد ملی همسر معتبر نیست.');
    }
}

if (!in_array($paymentType, ['full', 'installment'], true)) {
    $paymentType = 'full';
}

if ($firstName === '' || $lastName === '' || $gender === '' || $nationalCode === '' || $birthDate === '' || $mobile === '') {
    redirect_with_error('لطفا تمام مشخصات فردی را وارد کنید.');
}

if (!is_valid_national_code($nationalCode)) {
    redirect_with_error('کد ملی وارد شده معتبر نیست.');
}

if (!is_valid_mobile($mobile)) {
    redirect_with_error('شماره تماس وارد شده معتبر نیست.');
}

if ($securityCode === '' || $securityCode !== $sessionCode) {
    redirect_with_error('کد امنیتی وارد شده صحیح نیست.');
}

$groupMembers = $_POST['group_members'] ?? [];
$groupCount = (int) ($_POST['group_count'] ?? 0);

if ($registrationType === 'student' && $studentMode === 'group') {
    if ($groupCount !== 3 || !is_array($groupMembers) || count($groupMembers) !== 2) {
        redirect_with_error('ثبت نام گروهی فقط برای ۳ نفر امکان‌پذیر است.');
    }

    foreach ($groupMembers as $member) {
        $memberFirst = clean_input($member['first_name'] ?? '');
        $memberLast = clean_input($member['last_name'] ?? '');
        $memberGender = clean_input($member['gender'] ?? '');
        $memberNational = sanitize_numeric($member['national_code'] ?? '');
        $memberBirth = clean_input($member['birth_date'] ?? '');
        $memberMobile = sanitize_numeric($member['mobile'] ?? '');
        $memberAcademicLevel = clean_input($member['academic_level'] ?? '');
        $memberAcademicMajor = clean_input($member['academic_major'] ?? '');

        if ($memberFirst === '' || $memberLast === '' || $memberGender === '' || $memberNational === '' || $memberBirth === '' || $memberMobile === '' || $memberAcademicLevel === '') {
            redirect_with_error('لطفا مشخصات تمام اعضای گروه را کامل کنید.');
        }
        if ($memberAcademicMajor === '') {
            redirect_with_error('لطفا رشته تحصیلی اعضای گروه را مشخص کنید.');
        }

        if (!is_valid_national_code($memberNational)) {
            redirect_with_error('کد ملی اعضای گروه معتبر نیست.');
        }

        if (!is_valid_mobile($memberMobile)) {
            redirect_with_error('شماره تماس اعضای گروه معتبر نیست.');
        }
    }
}

$pdo = get_pdo($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);

$discountCode = '';
$discountRecord = null;
if ($discountCodeInput !== '') {
    $discountRecord = fetch_discount_code($pdo, $discountCodeInput);
    if (!$discountRecord) {
        redirect_with_error('کد تخفیف وارد شده معتبر نیست.');
    }
    $discountCode = (string) $discountRecord['code'];
}

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

$enableDuplicateCheck = true;
$submittedCodes = array_map(static fn ($record) => $record['code'], $submittedCodeRecords);

if ($enableDuplicateCheck) {
    if (count($submittedCodes) !== count(array_unique($submittedCodes))) {
        redirect_with_error('کد ملی تکراری در فرم وارد شده است.');
    }

    foreach ($submittedCodes as $code) {
        if (is_registered_national_code($pdo, $code)) {
            redirect_with_error('کد ملی قبلا ثبت شده است و امکان ثبت مجدد وجود ندارد.');
        }
    }
}

$totalAmount = calculate_amount($registrationType, $studentMode, $entryYear, $marriedStatus, $childrenCount);
if ($totalAmount <= 0) {
    redirect_with_error('مبلغ قابل محاسبه نیست.');
}

[$discountedAmount, $discountAmount] = apply_discount($totalAmount, $discountRecord);

$amount = $discountedAmount;
if (($registrationType === 'student' || $registrationType === 'alumni') && $paymentType === 'installment') {
    $amount = (int) round($discountedAmount / 2);
}

$formattedAmount = number_format($amount);

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare('INSERT INTO registrations (registration_type, student_mode, entry_year, married_status, academic_major, academic_level, payment_type, total_amount, discount_code_id, discount_code, discount_amount, amount, formatted_amount, first_name, last_name, gender, national_code, birth_date, mobile, spouse_name, spouse_national_code, spouse_birth_date, children_count, created_at) VALUES (:registration_type, :student_mode, :entry_year, :married_status, :academic_major, :academic_level, :payment_type, :total_amount, :discount_code_id, :discount_code, :discount_amount, :amount, :formatted_amount, :first_name, :last_name, :gender, :national_code, :birth_date, :mobile, :spouse_name, :spouse_national_code, :spouse_birth_date, :children_count, NOW())');
    $stmt->execute([
        ':registration_type' => $registrationType,
        ':student_mode' => $studentMode ?: null,
        ':entry_year' => $entryYear ?: null,
        ':married_status' => $marriedStatus ?: null,
        ':academic_major' => $academicMajor ?: null,
        ':academic_level' => $academicLevel ?: null,
        ':payment_type' => $paymentType ?: null,
        ':total_amount' => $discountedAmount,
        ':discount_code_id' => $discountRecord['id'] ?? null,
        ':discount_code' => $discountCode ?: null,
        ':discount_amount' => $discountAmount ?: null,
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
        $memberStmt = $pdo->prepare('INSERT INTO group_members (registration_id, first_name, last_name, gender, national_code, birth_date, mobile, academic_level, academic_major, created_at) VALUES (:registration_id, :first_name, :last_name, :gender, :national_code, :birth_date, :mobile, :academic_level, :academic_major, NOW())');

        foreach ($groupMembers as $member) {
            $memberStmt->execute([
                ':registration_id' => $registrationId,
                ':first_name' => clean_input($member['first_name'] ?? ''),
                ':last_name' => clean_input($member['last_name'] ?? ''),
                ':gender' => clean_input($member['gender'] ?? ''),
                ':national_code' => sanitize_numeric($member['national_code'] ?? ''),
                ':birth_date' => clean_input($member['birth_date'] ?? ''),
                ':mobile' => sanitize_numeric($member['mobile'] ?? ''),
                ':academic_level' => clean_input($member['academic_level'] ?? ''),
                ':academic_major' => clean_input($member['academic_major'] ?? ''),
            ]);
        }
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    if (($e->errorInfo[1] ?? null) === 1062 || $e->getCode() === '23000') {
        redirect_with_error('کد ملی قبلا ثبت شده است و امکان ثبت مجدد وجود ندارد.');
    }
    redirect_with_error('خطا در ذخیره اطلاعات.');
} catch (Exception $e) {
    $pdo->rollBack();
    redirect_with_error('خطا در ذخیره اطلاعات.');
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
    redirect_with_error('خطا در ارتباط با درگاه پرداخت.');
}

$result = json_decode($response, true);
if (!is_array($result) || ($result['Result'] ?? null) !== 0) {
    redirect_with_error('ایجاد درگاه پرداخت با خطا مواجه شد.');
}

$orderId = (int) $result['OrderID'];
$orderGuid = (string) $result['OrderGUID'];

$updateStmt = $pdo->prepare('UPDATE registrations SET payment_order_id = :order_id, payment_order_guid = :order_guid WHERE id = :id');
$updateStmt->execute([
    ':order_id' => $orderId,
    ':order_guid' => $orderGuid,
    ':id' => $registrationId,
]);

unset($_SESSION['security_code']);
$redirectUrl = sprintf('https://pay.sharif.edu/submit2/%s/%s', $orderId, $orderGuid);
header('Location: ' . $redirectUrl);
exit;
