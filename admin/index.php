<?php
session_start();

require_once __DIR__ . '/../config.php';

$ADMIN_USERNAME = 'rah_natamam';
$ADMIN_PASSWORD = 'Rah_natamam@a1';

$loginError = '';

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    }

    $loginError = 'نام کاربری یا رمز عبور اشتباه است.';
}

$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$loggedIn) {
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ورود ادمین</title>
        <link href="../assets/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
        <link href="../assets/css/styles.css" rel="stylesheet">
    </head>
    <body>
        <main class="page-wrapper">
            <section class="form-section">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="card shadow-lg border-0">
                                <div class="card-body p-4 p-md-5">
                                    <h1 class="h4 fw-bold mb-4">ورود ادمین</h1>
                                    <?php if ($loginError !== ''): ?>
                                        <div class="alert alert-danger mb-3" role="alert">
                                            <?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                    <form method="post" action="index.php">
                                        <input type="hidden" name="action" value="login">
                                        <div class="mb-3">
                                            <label class="form-label">نام کاربری</label>
                                            <input type="text" class="form-control" name="username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">رمز عبور</label>
                                            <input type="password" class="form-control" name="password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">ورود</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
    </html>
    <?php
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

$exportQuery = http_build_query([
    'registration_type' => $registrationType,
    'gender' => $gender,
]);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ادمین - ثبت نام های پرداخت شده</title>
    <link href="../assets/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <style>
        .admin-table td,
        .admin-table th {
            vertical-align: top;
            font-size: 0.85rem;
        }
        .badge-filter {
            font-size: 0.85rem;
        }
        .group-member {
            border-bottom: 1px dashed #cfd8dc;
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .group-member:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
    </style>
</head>
<body>
    <main class="page-wrapper">
        <section class="form-section">
            <div class="container">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h4 fw-bold mb-1">ثبت نام های پرداخت شده</h1>
                        <p class="text-muted mb-0">فقط ثبت نام هایی که پرداخت موفق دارند نمایش داده می شود.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-primary" href="export.php?<?php echo htmlspecialchars($exportQuery, ENT_QUOTES, 'UTF-8'); ?>">خروجی اکسل</a>
                        <a class="btn btn-outline-secondary" href="logout.php">خروج</a>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form class="row g-3 align-items-end" method="get" action="index.php">
                            <div class="col-md-4">
                                <label class="form-label">نوع ثبت نام</label>
                                <select class="form-select" name="registration_type">
                                    <option value="all" <?php echo $registrationType === 'all' ? 'selected' : ''; ?>>همه</option>
                                    <option value="married" <?php echo $registrationType === 'married' ? 'selected' : ''; ?>>متاهل</option>
                                    <option value="student" <?php echo $registrationType === 'student' ? 'selected' : ''; ?>>دانشجو</option>
                                    <option value="alumni" <?php echo $registrationType === 'alumni' ? 'selected' : ''; ?>>فارغ التحصیل</option>
                                    <option value="other" <?php echo $registrationType === 'other' ? 'selected' : ''; ?>>سایر</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">جنسیت</label>
                                <select class="form-select" name="gender">
                                    <option value="all" <?php echo $gender === 'all' ? 'selected' : ''; ?>>همه</option>
                                    <option value="male" <?php echo $gender === 'male' ? 'selected' : ''; ?>>مرد</option>
                                    <option value="female" <?php echo $gender === 'female' ? 'selected' : ''; ?>>زن</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">اعمال فیلتر</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped admin-table">
                        <thead class="table-light">
                            <tr>
                                <th>شناسه</th>
                                <th>نوع ثبت نام</th>
                                <th>مشخصات اصلی</th>
                                <th>اطلاعات تحصیلی</th>
                                <th>مشخصات همسر/فرزندان</th>
                                <th>اعضای گروه</th>
                                <th>پرداخت</th>
                                <th>زمان ثبت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$registrations): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">موردی یافت نشد.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registrations as $registration): ?>
                                    <tr>
                                        <td><?php echo (int) $registration['id']; ?></td>
                                        <td>
                                            <span class="badge bg-primary badge-filter">
                                                <?php echo htmlspecialchars($registrationLabels[$registration['registration_type']] ?? $registration['registration_type'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                            <?php if ($registration['registration_type'] === 'student' && $registration['student_mode']): ?>
                                                <div class="small text-muted mt-1">
                                                    <?php echo htmlspecialchars($studentModeLabels[$registration['student_mode']] ?? $registration['student_mode'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-muted">جنسیت: <?php echo htmlspecialchars($genderLabels[$registration['gender']] ?? $registration['gender'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-muted">کد ملی: <?php echo htmlspecialchars($registration['national_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-muted">موبایل: <?php echo htmlspecialchars($registration['mobile'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-muted">تاریخ تولد: <?php echo htmlspecialchars($registration['birth_date'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td>
                                            <div class="small text-muted">مقطع: <?php echo htmlspecialchars($registration['academic_level'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-muted">رشته: <?php echo htmlspecialchars($registration['academic_major'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-muted">ورودی: <?php echo htmlspecialchars($registration['entry_year'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td>
                                            <?php if ($registration['registration_type'] === 'married'): ?>
                                                <div class="small text-muted">وضعیت: <?php echo htmlspecialchars($marriedStatusLabels[$registration['married_status']] ?? $registration['married_status'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="small text-muted">نام همسر: <?php echo htmlspecialchars($registration['spouse_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="small text-muted">کد ملی همسر: <?php echo htmlspecialchars($registration['spouse_national_code'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="small text-muted">تاریخ تولد همسر: <?php echo htmlspecialchars($registration['spouse_birth_date'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="small text-muted">تعداد فرزندان: <?php echo htmlspecialchars((string) ($registration['children_count'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php $groupMembers = $groupMembersByRegistration[$registration['id']] ?? []; ?>
                                            <?php if ($groupMembers): ?>
                                                <?php foreach ($groupMembers as $member): ?>
                                                    <div class="group-member">
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                        <div class="small text-muted">جنسیت: <?php echo htmlspecialchars($genderLabels[$member['gender']] ?? $member['gender'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                        <div class="small text-muted">کد ملی: <?php echo htmlspecialchars($member['national_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                        <div class="small text-muted">موبایل: <?php echo htmlspecialchars($member['mobile'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                        <div class="small text-muted">تاریخ تولد: <?php echo htmlspecialchars($member['birth_date'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small text-muted">مبلغ: <?php echo htmlspecialchars($registration['formatted_amount'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-muted">کد پیگیری: <?php echo htmlspecialchars($registration['payment_reference'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-muted">وضعیت: <?php echo htmlspecialchars($registration['payment_status_text'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td>
                                            <span class="small text-muted"><?php echo htmlspecialchars($registration['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
