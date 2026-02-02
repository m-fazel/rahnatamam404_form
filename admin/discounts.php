<?php
session_start();

require_once __DIR__ . '/../config.php';

$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$loggedIn) {
    header('Location: index.php');
    exit;
}

$pdo = get_pdo($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
        $title = trim((string) ($_POST['title'] ?? ''));
        $discountType = $_POST['discount_type'] ?? 'amount';
        $discountValue = (int) ($_POST['discount_value'] ?? 0);

        if ($code === '' || $discountValue <= 0) {
            $error = 'کد و مقدار تخفیف باید تکمیل شوند.';
        } elseif (!in_array($discountType, ['amount', 'percent'], true)) {
            $error = 'نوع تخفیف معتبر نیست.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO discount_codes (code, title, discount_type, discount_value, is_active, created_at) VALUES (:code, :title, :discount_type, :discount_value, 1, NOW())');
                $stmt->execute([
                    ':code' => $code,
                    ':title' => $title !== '' ? $title : null,
                    ':discount_type' => $discountType,
                    ':discount_value' => $discountValue,
                ]);
                header('Location: discounts.php');
                exit;
            } catch (PDOException $e) {
                $error = 'کد تخفیف تکراری است یا خطایی رخ داده است.';
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE discount_codes SET is_active = 1 - is_active WHERE id = :id');
            $stmt->execute([':id' => $id]);
            header('Location: discounts.php');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM discount_codes WHERE id = :id');
            $stmt->execute([':id' => $id]);
            header('Location: discounts.php');
            exit;
        }
    }
}

$codes = $pdo->query('SELECT * FROM discount_codes ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مدیریت کدهای تخفیف</title>
    <link href="../assets/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <main class="page-wrapper">
        <section class="form-section">
            <div class="container pt-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h4 fw-bold mb-1">مدیریت کدهای تخفیف</h1>
                        <p class="text-light mb-0">تعریف، فعال/غیرفعال و حذف کدهای تخفیف.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="index.php">بازگشت</a>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">ایجاد کد جدید</h2>
                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <form class="row g-3" method="post">
                            <input type="hidden" name="action" value="create">
                            <div class="col-md-3">
                                <label class="form-label">کد تخفیف</label>
                                <input type="text" class="form-control" name="code" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">عنوان</label>
                                <input type="text" class="form-control" name="title">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">نوع تخفیف</label>
                                <select class="form-select" name="discount_type">
                                    <option value="amount">مبلغ ثابت (تومان)</option>
                                    <option value="percent">درصدی</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">مقدار تخفیف</label>
                                <input type="number" class="form-control" name="discount_value" min="1" required>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">ثبت کد</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>کد</th>
                                <th>عنوان</th>
                                <th>نوع</th>
                                <th>مقدار</th>
                                <th>وضعیت</th>
                                <th>اقدامات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$codes): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">کدی ثبت نشده است.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($codes as $index => $code): ?>
                                    <tr>
                                        <td><?php echo (int) ($index + 1); ?></td>
                                        <td><?php echo htmlspecialchars($code['code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($code['title'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo $code['discount_type'] === 'percent' ? 'درصدی' : 'مبلغ ثابت'; ?></td>
                                        <td><?php echo (int) $code['discount_value']; ?></td>
                                        <td><?php echo $code['is_active'] ? 'فعال' : 'غیرفعال'; ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?php echo (int) $code['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <?php echo $code['is_active'] ? 'غیرفعال' : 'فعال'; ?>
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int) $code['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف شود؟');">حذف</button>
                                            </form>
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
