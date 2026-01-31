<?php
session_start();

require_once __DIR__ . '/../config.php';

$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$loggedIn) {
    header('Location: index.php');
    exit;
}

$pdo = get_pdo($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);

$registrationType = $_GET['registration_type'] ?? 'all';
$gender = $_GET['gender'] ?? 'all';

$where = ['payment_status_id = 0'];
$params = [];

$allowedTypes = ['all', 'married', 'student', 'alumni', 'other'];
if (!in_array($registrationType, $allowedTypes, true)) {
    $registrationType = 'all';
}

$allowedGenders = ['all', 'male', 'female'];
if (!in_array($gender, $allowedGenders, true)) {
    $gender = 'all';
}

if ($registrationType !== 'all') {
    $where[] = 'registration_type = :registration_type';
    $params[':registration_type'] = $registrationType;
}

if ($gender !== 'all') {
    $where[] = 'gender = :gender';
    $params[':gender'] = $gender;
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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="registrations.csv"');

$output = fopen('php://output', 'w');

fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    'شناسه',
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
]);

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

    fputcsv($output, [
        $registration['id'],
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
        $registration['children_count'],
        implode(' | ', $memberChunks),
        $registration['formatted_amount'],
        $registration['payment_reference'],
        $registration['payment_status_text'],
        $registration['created_at'],
    ]);
}

fclose($output);
