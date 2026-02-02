<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/jdf.php';
require_once __DIR__ . '/lib/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$loggedIn) {
    header('Location: index.php');
    exit;
}

$pdo = get_pdo($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);

$filter = $_GET['filter'] ?? 'male';

$where = ['payment_status_id = 0'];
$params = [];

$allowedFilters = ['married', 'male', 'female'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'married';
}

if ($filter === 'married') {
    $where[] = 'registration_type = :registration_type';
    $params[':registration_type'] = 'married';
}

if ($filter === 'male') {
    $where[] = "registration_type IN ('student', 'alumni', 'other')";
    $where[] = 'gender = :gender';
    $params[':gender'] = 'male';
}

if ($filter === 'female') {
    $where[] = "registration_type IN ('student', 'alumni', 'other')";
    $where[] = 'gender = :gender';
    $params[':gender'] = 'female';
}

$whereSql = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM registrations WHERE {$whereSql} ORDER BY created_at DESC");
$stmt->execute($params);
$registrations = $stmt->fetchAll();

$registrationIds = array_column($registrations, 'id');
$groupMembersByRegistration = [];

if ($registrationIds) {
    $placeholders = implode(',', array_fill(0, count($registrationIds), '?'));
    $groupStmt = $pdo->prepare("SELECT * FROM group_members WHERE registration_id IN ({$placeholders}) ORDER BY id ASC");
    $groupStmt->execute($registrationIds);

    while ($row = $groupStmt->fetch()) {
        $groupMembersByRegistration[$row['registration_id']][] = $row;
    }
}

$registrationLabels = [
    'student' => 'دانشجو',
    'alumni' => 'فارغ التحصیل',
    'married' => 'متاهل',
    'other' => 'سایر',
];

$studentModeLabels = [
    'individual' => 'انفرادی',
    'group' => 'گروهی',
];

$genderLabels = [
    'male' => 'مرد',
    'female' => 'زن',
];

$marriedStatusLabels = [
    'married_student' => 'متاهل - دانشجو شریف',
    'married_alumni' => 'متاهل - فارغ التحصیل شریف',
    'married_other' => 'متاهل - غیرشریفی',
];

$academicLevelLabels = [
    'bachelor' => 'کارشناسی',
    'masters' => 'کارشناسی ارشد',
    'phd' => 'دکتری',
];

$academicMajorLabels = [
    'economics' => 'اقتصاد',
    'business_management' => 'مدیریت کسب و کار',
    'science_policy' => 'سیاستگذاری علم و فناوری',
    'philosophy_of_science' => 'فلسفه علم',
    'mechanical' => 'مهندسی مکانیک',
    'computer' => 'مهندسی کامپیوتر',
    'electrical' => 'مهندسی برق',
    'chemical' => 'مهندسی شیمی',
    'civil' => 'مهندسی عمران',
    'energy' => 'مهندسی انرژی',
    'aerospace' => 'مهندسی هوافضا',
    'industrial' => 'مهندسی صنایع',
    'marine' => 'مهندسی دریا',
    'mathematics' => 'ریاضیات و کاربردها',
    'computer_science' => 'علوم کامپیوتر',
    'materials' => 'مهندسی مواد و متالوژی',
    'physics' => 'فیزیک',
    'chemistry' => 'شیمی',
];

$paymentTypeLabels = [
    'full' => 'پرداخت کامل',
    'installment' => 'پرداخت قسطی',
];

function format_jalali_datetime($dateString)
{
    if (!$dateString) {
        return '-';
    }
    $date = new DateTime($dateString, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Asia/Tehran'));
    $timestamp = $date->getTimestamp();
    return jdate('Y/m/d H:i', $timestamp, '', 'Asia/Tehran', 'fa');
}

$rows = [[
    'ردیف',
    'شماره گروه',
    'نقش',
    'نوع ثبت نام',
    'حالت دانشجو',
    'سال ورودی',
    'وضعیت متاهل',
    'نام',
    'نام خانوادگی',
    'جنسیت',
    'کد ملی',
    'تاریخ تولد',
    'موبایل',
    'مقطع',
    'رشته',
    'نام همسر',
    'کد ملی همسر',
    'تاریخ تولد همسر',
    'تعداد فرزندان',
    'نوع پرداخت',
    'مبلغ کل',
    'کد تخفیف',
    'مقدار تخفیف',
    'مبلغ پرداختی',
    'کد پیگیری',
    'وضعیت پرداخت',
    'زمان ثبت',
]];

$rowNumber = 1;
foreach ($registrations as $registration) {
    $groupNumber = $registration['student_mode'] === 'group' ? (string) $registration['id'] : '';
    $roleLabel = $registration['student_mode'] === 'group' ? 'سرگروه' : 'اصلی';
    $rows[] = [
        (string) $rowNumber,
        $groupNumber,
        $roleLabel,
        $registrationLabels[$registration['registration_type']] ?? $registration['registration_type'],
        $studentModeLabels[$registration['student_mode']] ?? $registration['student_mode'],
        $registration['entry_year'],
        $marriedStatusLabels[$registration['married_status']] ?? $registration['married_status'],
        $registration['first_name'],
        $registration['last_name'],
        $genderLabels[$registration['gender']] ?? $registration['gender'],
        $registration['national_code'],
        $registration['birth_date'],
        $registration['mobile'],
        $academicLevelLabels[$registration['academic_level']] ?? $registration['academic_level'],
        $academicMajorLabels[$registration['academic_major']] ?? $registration['academic_major'],
        $registration['spouse_name'],
        $registration['spouse_national_code'],
        $registration['spouse_birth_date'],
        (string) $registration['children_count'],
        $paymentTypeLabels[$registration['payment_type']] ?? $registration['payment_type'],
        number_format((int) ($registration['total_amount'] ?? $registration['amount'])),
        $registration['discount_code'],
        $registration['discount_amount'] ? number_format((int) $registration['discount_amount']) : '',
        $registration['formatted_amount'],
        $registration['payment_reference'],
        $registration['payment_status_text'],
        format_jalali_datetime($registration['created_at']),
    ];
    $rowNumber++;

    $members = $groupMembersByRegistration[$registration['id']] ?? [];
    foreach ($members as $member) {
        $rows[] = [
            (string) $rowNumber,
            $groupNumber,
            'عضو گروه',
            $registrationLabels[$registration['registration_type']] ?? $registration['registration_type'],
            $studentModeLabels[$registration['student_mode']] ?? $registration['student_mode'],
            $registration['entry_year'],
            $marriedStatusLabels[$registration['married_status']] ?? $registration['married_status'],
            $member['first_name'],
            $member['last_name'],
            $genderLabels[$member['gender']] ?? $member['gender'],
            $member['national_code'],
            $member['birth_date'],
            $member['mobile'],
            $academicLevelLabels[$member['academic_level']] ?? $member['academic_level'],
            $academicMajorLabels[$member['academic_major']] ?? $member['academic_major'],
            '',
            '',
            '',
            '',
            $paymentTypeLabels[$registration['payment_type']] ?? $registration['payment_type'],
            number_format((int) ($registration['total_amount'] ?? $registration['amount'])),
            $registration['discount_code'],
            $registration['discount_amount'] ? number_format((int) $registration['discount_amount']) : '',
            $registration['formatted_amount'],
            $registration['payment_reference'],
            $registration['payment_status_text'],
            format_jalali_datetime($registration['created_at']),
        ];
        $rowNumber++;
    }
}

SimpleXLSXGen::fromArray($rows, 'Registrations')
    ->downloadAs('registrations.xlsx');
exit;
