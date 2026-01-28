<?php
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ثبت نام نهایی اردوی راه‌ناتمام ۱۴۰۴</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-3 text-center">ثبت نام قطعی و پرداخت وجه اردوی راه‌ناتمام ۱۴۰۴</h1>
                        <div class="alert alert-light border text-secondary">
                            <p class="mb-2">سلام! به صفحه‌ی ثبت‌نام قطعی و پرداخت‌وجه اردوی راه‌ناتمام ۱۴۰۴ خوش آمدید. لطفا به تمامی سوالات، کامل و بادقت پاسخ دهید.</p>
                            <p class="mb-2">انجام مراحل را تا انتها و پرداخت وجه ادامه دهید.</p>
                            <p class="mb-0">هرگونه اشتباه و خطا در ثبت اطلاعات برعهده تکمیل‌کننده می‌باشد!</p>
                        </div>

                        <form action="submit.php" method="post" id="registrationForm">
                            <div class="mb-3">
                                <label for="registration_type" class="form-label fw-semibold">نوع ثبت نام</label>
                                <select class="form-select" id="registration_type" name="registration_type" required>
                                    <option value="">انتخاب کنید</option>
                                    <option value="student">دانشجوی شریف</option>
                                    <option value="alumni">فارغ التحصیل</option>
                                    <option value="married">متاهلین</option>
                                    <option value="other">سایر</option>
                                </select>
                            </div>

                            <div id="studentFields" class="d-none">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">نوع ثبت نام دانشجو</label>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="student_mode" id="student_individual" value="individual">
                                            <label class="form-check-label" for="student_individual">انفرادی</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="student_mode" id="student_group" value="group">
                                            <label class="form-check-label" for="student_group">گروهی</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="entry_year" class="form-label fw-semibold">ورودی چه سالی هستید؟</label>
                                    <select class="form-select" id="entry_year" name="entry_year">
                                        <option value="">انتخاب کنید</option>
                                        <option value="1404">ورودی ۱۴۰۴</option>
                                        <option value="1403">ورودی ۱۴۰۳</option>
                                        <option value="1402_or_before">ورودی ۱۴۰۲ و ماقبل</option>
                                    </select>
                                </div>
                            </div>

                            <div id="marriedFields" class="d-none">
                                <div class="mb-3">
                                    <label for="married_status" class="form-label fw-semibold">وضعیت تحصیلی (تنها یکی از زوجین کفایت می‌کند)</label>
                                    <select class="form-select" id="married_status" name="married_status">
                                        <option value="">انتخاب کنید</option>
                                        <option value="married_student">متاهل - دانشجو شریف</option>
                                        <option value="married_alumni">متاهل - فارغ التحصیل شریف</option>
                                        <option value="married_other">متاهل - غیرشریفی</option>
                                    </select>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">نام و نام خانوادگی همسر</label>
                                        <input type="text" class="form-control" name="spouse_name" id="spouse_name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">کد ملی همسر</label>
                                        <input type="text" class="form-control" name="spouse_national_code" id="spouse_national_code">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">تاریخ تولد همسر</label>
                                        <input type="date" class="form-control" name="spouse_birth_date" id="spouse_birth_date">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">تعداد فرزندان (درصورت وجود)</label>
                                        <input type="number" class="form-control" name="children_count" id="children_count" min="0" value="0">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h2 class="h5 fw-bold">مشخصات فردی</h2>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">نام</label>
                                        <input type="text" class="form-control" name="first_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">نام خانوادگی</label>
                                        <input type="text" class="form-control" name="last_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">جنسیت</label>
                                        <select class="form-select" name="gender" required>
                                            <option value="">انتخاب کنید</option>
                                            <option value="male">مرد</option>
                                            <option value="female">زن</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">کد ملی</label>
                                        <input type="text" class="form-control" name="national_code" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">تاریخ تولد</label>
                                        <input type="date" class="form-control" name="birth_date" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">شماره تماس</label>
                                        <input type="text" class="form-control" name="mobile" required>
                                    </div>
                                </div>
                            </div>

                            <div id="groupFields" class="d-none mt-4">
                                <h2 class="h5 fw-bold">مشخصات اعضای گروه</h2>
                                <div class="mb-3">
                                    <label class="form-label">تعداد اعضای گروه</label>
                                    <input type="number" class="form-control" id="group_count" name="group_count" min="2" value="2">
                                    <div class="form-text">حداقل ۲ نفر برای ثبت نام گروهی.</div>
                                </div>
                                <div id="groupMembers"></div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">پرداخت و انتقال به درگاه</button>
                                <p class="small text-muted mt-2 text-center">مبلغ نهایی بر اساس نوع ثبت نام محاسبه و درگاه پرداخت ایجاد می‌شود.</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/form.js"></script>
</body>
</html>
