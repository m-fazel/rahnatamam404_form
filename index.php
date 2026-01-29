<?php
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ثبت نام نهایی اردوی راه‌ناتمام ۱۴۰۴</title>
    <link href="assets/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <main class="page-wrapper" id="registrationApp">
        <header class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <div>
                        <span class="hero-badge">ثبت نام نهایی</span>
                        <h1 class="hero-title">اردوی راه‌ناتمام ۱۴۰۴</h1>
                        <p class="hero-subtitle">اطلاعات را با دقت تکمیل کنید تا مبلغ نهایی و پرداخت در همان لحظه برایتان نمایش داده شود.</p>
                    </div>
                    <div class="hero-info">
                        <div class="info-card final-card">
                            <h2 class="h6 mb-2">مبلغ نهایی شما</h2>
                            <p class="final-amount" id="finalAmount">—</p>
                            <p class="small text-white mb-0" id="amountDetails">نوع ثبت نام و گزینه‌ها را انتخاب کنید.</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="form-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card shadow-lg border-0">
                            <div class="card-body p-4 p-md-5">
                                <div class="alert alert-light border text-secondary mb-4">
                                    <p class="mb-2">سلام! به صفحه‌ی ثبت‌نام نهایی و پرداخت‌وجه اردوی راه‌ناتمام ۱۴۰۴ خوش آمدید. لطفا به تمامی سوالات، کامل و بادقت پاسخ دهید.</p>
                                    <p class="mb-2">انجام مراحل را تا انتها و پرداخت وجه ادامه دهید.</p>
                                    <p class="mb-0">هرگونه اشتباه و خطا در ثبت اطلاعات برعهده تکمیل‌کننده می‌باشد!</p>
                                </div>
                                <div class="info-card contact-card mb-4">
                                    <h2 class="h6 fw-bold mb-2">راه‌های ارتباطی اردو</h2>
                                    <p class="mb-1 fw-semibold">کانال اردو:</p>
                                    <p class="mb-1">
                                        تلگرام:
                                        <a href="https://t.me/Rah_Natamam_1404" target="_blank" rel="noopener">Rah_Natamam_1404</a>
                                    </p>
                                    <p class="mb-3">
                                        بله:
                                        <a href="https://ble.ir/Rah_Natamam_1404" target="_blank" rel="noopener">Rah_Natamam_1404</a>
                                    </p>
                                    <p class="mb-1 fw-semibold">حساب پشتیبانی:</p>
                                    <p class="mb-1">
                                        تلگرام:
                                        <a href="https://t.me/RahNatamam_Baradaran" target="_blank" rel="noopener">RahNatamam_Baradaran</a>
                                    </p>
                                    <p class="mb-0">
                                        بله:
                                        <a href="https://ble.ir/RahNatamam_Baradaran" target="_blank" rel="noopener">RahNatamam_Baradaran</a>
                                    </p>
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
                                    <div id="marriedStatusField" class="d-none mb-3">
                                        <label for="married_status" class="form-label fw-semibold">وضعیت تحصیلی (تنها یکی از زوجین کفایت می‌کند)</label>
                                        <select class="form-select" id="married_status" name="married_status">
                                            <option value="">انتخاب کنید</option>
                                            <option value="married_student">متاهل - دانشجو شریف</option>
                                            <option value="married_alumni">متاهل - فارغ التحصیل شریف</option>
                                            <option value="married_other">متاهل - غیرشریفی</option>
                                        </select>
                                    </div>

                                    <div id="studentFields" class="d-none">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">نوع ثبت نام دانشجو</label>
                                            <div class="d-flex gap-3 flex-wrap">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="student_mode" id="student_individual" value="individual" required>
                                                    <label class="form-check-label" for="student_individual">انفرادی</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="student_mode" id="student_group" value="group" required>
                                                    <label class="form-check-label" for="student_group">گروهی (۳ نفره)</label>
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

                                    <div class="mt-4">
                                        <h2 class="h5 fw-bold">مشخصات فردی</h2>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">نام</label>
                                                <input type="text" class="form-control" name="first_name" placeholder="نام خود را وارد کنید" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">نام خانوادگی</label>
                                                <input type="text" class="form-control" name="last_name" placeholder="نام خانوادگی خود را وارد کنید" required>
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
                                                <input type="text" class="form-control" name="national_code" inputmode="numeric" maxlength="10" pattern="\d{10}" placeholder="مثال: ۰۰۱۲۳۴۵۶۷۸" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">تاریخ تولد</label>
                                                <date-picker v-model="birthDate" format="jYYYY/jMM/jDD" display-format="jYYYY/jMM/jDD" input-class="form-control" placeholder="انتخاب تاریخ" :max="maxBirthDate" auto-submit color="#003e5f"></date-picker>
                                                <input type="hidden" name="birth_date" :value="birthDate" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">شماره تماس</label>
                                                <input type="tel" class="form-control" name="mobile" inputmode="numeric" maxlength="11" pattern="09\d{9}" placeholder="مثال: ۰۹۱۲۳۴۵۶۷۸۹" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="marriedFields" class="d-none mt-4">
                                        <h2 class="h5 fw-bold">مشخصات همسر و فرزندان</h2>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">نام و نام خانوادگی همسر</label>
                                                <input type="text" class="form-control" name="spouse_name" id="spouse_name" placeholder="نام و نام خانوادگی همسر">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">کد ملی همسر</label>
                                                <input type="text" class="form-control" name="spouse_national_code" id="spouse_national_code" inputmode="numeric" maxlength="10" pattern="\d{10}" placeholder="مثال: ۰۰۱۲۳۴۵۶۷۸">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">تاریخ تولد همسر</label>
                                                <date-picker v-model="spouseBirthDate" format="jYYYY/jMM/jDD" display-format="jYYYY/jMM/jDD" input-class="form-control" placeholder="انتخاب تاریخ" :max="maxBirthDate" auto-submit color="#003e5f"></date-picker>
                                                <input type="hidden" name="spouse_birth_date" id="spouse_birth_date" :value="spouseBirthDate">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">تعداد فرزندان (درصورت وجود)</label>
                                                <input type="number" class="form-control" name="children_count" id="children_count" min="0" value="0" placeholder="مثال: ۰">
                                                <div class="form-text">جهت ثبت‌نام نهایی و حضور فرزندان در اردو، حتما جهت ثبت اطلاعات فرزندان با اکانت پشتیبانی در ارتباط باشید.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="groupFields" class="d-none mt-4">
                                        <h2 class="h5 fw-bold">مشخصات اعضای گروه (۳ نفره)</h2>
                                        <input type="hidden" id="group_count" name="group_count" value="3">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="member-card">
                                                    <h3 class="h6 fw-semibold">عضو ۲</h3>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">نام</label>
                                                            <input type="text" class="form-control group-required" name="group_members[0][first_name]" placeholder="نام عضو">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">نام خانوادگی</label>
                                                            <input type="text" class="form-control group-required" name="group_members[0][last_name]" placeholder="نام خانوادگی عضو">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">جنسیت</label>
                                                            <select class="form-select group-required" name="group_members[0][gender]">
                                                                <option value="">انتخاب کنید</option>
                                                                <option value="male">مرد</option>
                                                                <option value="female">زن</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">کد ملی</label>
                                                            <input type="text" class="form-control group-required" name="group_members[0][national_code]" inputmode="numeric" maxlength="10" pattern="\d{10}" placeholder="مثال: ۰۰۱۲۳۴۵۶۷۸">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">تاریخ تولد</label>
                                                            <date-picker v-model="groupBirthDates[0]" format="jYYYY/jMM/jDD" display-format="jYYYY/jMM/jDD" input-class="form-control" placeholder="انتخاب تاریخ" :max="maxBirthDate" auto-submit color="#003e5f"></date-picker>
                                                            <input type="hidden" class="group-required" name="group_members[0][birth_date]" :value="groupBirthDates[0]">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">شماره تماس</label>
                                                            <input type="tel" class="form-control group-required" name="group_members[0][mobile]" inputmode="numeric" maxlength="11" pattern="09\d{9}" placeholder="مثال: ۰۹۱۲۳۴۵۶۷۸۹">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="member-card">
                                                    <h3 class="h6 fw-semibold">عضو ۳</h3>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">نام</label>
                                                            <input type="text" class="form-control group-required" name="group_members[1][first_name]" placeholder="نام عضو">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">نام خانوادگی</label>
                                                            <input type="text" class="form-control group-required" name="group_members[1][last_name]" placeholder="نام خانوادگی عضو">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">جنسیت</label>
                                                            <select class="form-select group-required" name="group_members[1][gender]">
                                                                <option value="">انتخاب کنید</option>
                                                                <option value="male">مرد</option>
                                                                <option value="female">زن</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">کد ملی</label>
                                                            <input type="text" class="form-control group-required" name="group_members[1][national_code]" inputmode="numeric" maxlength="10" pattern="\d{10}" placeholder="مثال: ۰۰۱۲۳۴۵۶۷۸">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">تاریخ تولد</label>
                                                            <date-picker v-model="groupBirthDates[1]" format="jYYYY/jMM/jDD" display-format="jYYYY/jMM/jDD" input-class="form-control" placeholder="انتخاب تاریخ" :max="maxBirthDate" auto-submit color="#003e5f"></date-picker>
                                                            <input type="hidden" class="group-required" name="group_members[1][birth_date]" :value="groupBirthDates[1]">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">شماره تماس</label>
                                                            <input type="tel" class="form-control group-required" name="group_members[1][mobile]" inputmode="numeric" maxlength="11" pattern="09\d{9}" placeholder="مثال: ۰۹۱۲۳۴۵۶۷۸۹">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-accent btn-lg">انتقال به درگاه و پرداخت</button>
                                        <p class="small text-muted mt-2 text-center">مبلغ نهایی بر اساس نوع ثبت نام محاسبه می‌شود.</p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="assets/js/vue.global.js"></script>
    <script src="assets/js/moment.min.js"></script>
    <script src="assets/js/moment-jalaali.js"></script>
    <script src="assets/js/vue3-persian-datetime-picker.umd.min.js"></script>
    <script src="assets/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/form.js"></script>
</body>
</html>
