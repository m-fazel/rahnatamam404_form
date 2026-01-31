<?php
session_start();

require_once __DIR__ . '/../config.php';

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

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="registrations.xls"');

echo '<html lang="fa" dir="rtl"><head><meta charset="UTF-8"></head><body>';
echo '<table border="1">';
echo '<thead><tr>';
echo '<th>شناسه</th>';
echo '<th>نوع ثبت نام</th>';
echo '<th>حالت دانشجو</th>';
echo '<th>سال ورودی</th>';
echo '<th>وضعیت متاهل</th>';
echo '<th>نام</th>';
echo '<th>نام خانوادگی</th>';
echo '<th>جنسیت</th>';
echo '<th>کد ملی</th>';
echo '<th>تاریخ تولد</th>';
echo '<th>موبایل</th>';
echo '<th>مقطع</th>';
echo '<th>رشته</th>';
echo '<th>نام همسر</th>';
echo '<th>کد ملی همسر</th>';
echo '<th>تاریخ تولد همسر</th>';
echo '<th>تعداد فرزندان</th>';
echo '<th>اعضای گروه</th>';
echo '<th>مبلغ</th>';
echo '<th>کد پیگیری</th>';
echo '<th>وضعیت پرداخت</th>';
echo '<th>زمان ثبت</th>';
echo '</tr></thead><tbody>';

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

    echo '<tr>';
    echo '<td>' . htmlspecialchars((string) $registration['id'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['registration_type'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['student_mode'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['entry_year'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['married_status'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['first_name'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['last_name'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['gender'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['national_code'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['birth_date'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['mobile'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['academic_level'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['academic_major'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['spouse_name'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['spouse_national_code'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['spouse_birth_date'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars((string) $registration['children_count'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars(implode(' | ', $memberChunks), ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['formatted_amount'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['payment_reference'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['payment_status_text'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($registration['created_at'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '</tr>';
}

echo '</tbody></table></body></html>';
