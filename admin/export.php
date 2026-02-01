<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/lib/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$loggedIn) {
    header('Location: index.php');
    exit;
}

$pdo = get_pdo($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);

$filter = $_GET['filter'] ?? 'married';

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

$rows = [[
    'ردیف',
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
    'اعضای گروه',
    'مبلغ',
    'کد پیگیری',
    'وضعیت پرداخت',
    'زمان ثبت',
]];

$rowNumber = 1;
foreach ($registrations as $registration) {
    $members = $groupMembersByRegistration[$registration['id']] ?? [];
    $memberChunks = [];
    foreach ($members as $member) {
        $memberChunks[] = sprintf(
            '%s %s (%s - %s)',
            $member['first_name'],
            $member['last_name'],
            $member['gender'],
            $member['national_code']
        );
    }

    $rows[] = [
        (string) $rowNumber,
        $registration['registration_type'],
        $registration['student_mode'],
        $registration['entry_year'],
        $registration['married_status'],
        $registration['first_name'],
        $registration['last_name'],
        $registration['gender'],
        $registration['national_code'],
        $registration['birth_date'],
        $registration['mobile'],
        $registration['academic_level'],
        $registration['academic_major'],
        $registration['spouse_name'],
        $registration['spouse_national_code'],
        $registration['spouse_birth_date'],
        (string) $registration['children_count'],
        implode(' | ', $memberChunks),
        $registration['formatted_amount'],
        $registration['payment_reference'],
        $registration['payment_status_text'],
        $registration['created_at'],
    ];
    $rowNumber++;
}

SimpleXLSXGen::fromArray($rows, 'Registrations')
    ->downloadAs('registrations.xlsx');
exit;
