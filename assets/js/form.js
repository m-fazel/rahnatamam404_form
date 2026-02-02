const getRefs = () => ({
    registrationType: document.getElementById('registration_type'),
    studentFields: document.getElementById('studentFields'),
    marriedFields: document.getElementById('marriedFields'),
    marriedStatusField: document.getElementById('marriedStatusField'),
    groupFields: document.getElementById('groupFields'),
    entryYear: document.getElementById('entry_year'),
    academicLevelField: document.getElementById('academicLevelField'),
    academicMajorField: document.getElementById('academicMajorField'),
    academicMajor: document.getElementById('academic_major'),
    academicLevel: document.getElementById('academic_level'),
    alumniExtraFields: document.getElementById('alumniExtraFields'),
    alumniEntryYear: document.getElementById('alumni_entry_year'),
    marriedStatus: document.getElementById('married_status'),
    spouseName: document.getElementById('spouse_name'),
    spouseNationalCode: document.getElementById('spouse_national_code'),
    spouseBirthDate: document.getElementById('spouse_birth_date'),
    childrenCount: document.getElementById('children_count'),
    paymentTypeField: document.getElementById('paymentTypeField'),
    discountCode: document.getElementById('discount_code'),
    discountCheckButton: document.getElementById('discountCheckButton'),
    discountFeedback: document.getElementById('discountFeedback'),
    finalAmount: document.getElementById('finalAmount'),
    amountDetails: document.getElementById('amountDetails'),
});

const getStudentModeInputs = () => document.querySelectorAll('input[name="student_mode"]');
const getPaymentTypeInputs = () => document.querySelectorAll('input[name="payment_type"]');
const getGroupRequiredFields = () => document.querySelectorAll('.group-required');

const amountTable = {
    student: {
        '1404': { individual: 500, group: 1200 },
        '1403': { individual: 600, group: 1500 },
        '1402_or_before': { individual: 700, group: 1800 },
    },
    alumni: 1000,
    married: {
        married_student: 1500,
        married_alumni: 1800,
        married_other: 2500,
    },
    other: 1500,
};

const toggleRequired = (element, isRequired) => {
    if (!element) {
        return;
    }
    if (isRequired) {
        element.setAttribute('required', 'required');
    } else {
        element.removeAttribute('required');
    }
};

const clearFieldValue = (field) => {
    if (!field) {
        return;
    }
    if (field instanceof HTMLInputElement) {
        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = false;
        } else {
            field.value = '';
        }
        return;
    }
    if (field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
        field.value = '';
    }
};

const clearFields = (fields) => {
    fields.forEach((field) => clearFieldValue(field));
};

const digitMap = {
    '۰': '0',
    '۱': '1',
    '۲': '2',
    '۳': '3',
    '۴': '4',
    '۵': '5',
    '۶': '6',
    '۷': '7',
    '۸': '8',
    '۹': '9',
    '٠': '0',
    '١': '1',
    '٢': '2',
    '٣': '3',
    '٤': '4',
    '٥': '5',
    '٦': '6',
    '٧': '7',
    '٨': '8',
    '٩': '9',
};

const normalizeDigits = (value) => value.replace(/[۰-۹٠-٩]/g, (digit) => digitMap[digit] ?? digit);

const sanitizeNumeric = (value) => normalizeDigits(value).replace(/\D/g, '');

const isValidNationalCode = (value) => {
    const code = sanitizeNumeric(value);
    if (code.length !== 10) {
        return false;
    }
    const digits = code.split('').map(Number);
    let sum = 0;
    for (let i = 0; i < 9; i += 1) {
        sum += digits[i] * (10 - i);
    }
    const remainder = sum % 11;
    const checkDigit = digits[9];
    return remainder < 2 ? checkDigit === remainder : checkDigit === 11 - remainder;
};

const isValidMobile = (value) => /^09\d{9}$/.test(sanitizeNumeric(value));

const isNationalCodeInput = (input) =>
    input.matches('input[name="national_code"], input[name="spouse_national_code"], input[name^="group_members"][name$="[national_code]"]');

const isMobileInput = (input) =>
    input.matches('input[name="mobile"], input[name^="group_members"][name$="[mobile]"]');

const validateNationalCodeInput = (input) => {
    const isRequired = input.hasAttribute('required');
    const sanitized = sanitizeNumeric(input.value);
    if (input.value !== sanitized) {
        input.value = sanitized;
    }
    if (!sanitized) {
        input.setCustomValidity(isRequired ? 'کد ملی الزامی است.' : '');
        return !isRequired;
    }
    if (!isValidNationalCode(sanitized)) {
        input.setCustomValidity('کد ملی معتبر نیست.');
        return false;
    }
    input.setCustomValidity('');
    return true;
};

const validateMobileInput = (input) => {
    const isRequired = input.hasAttribute('required');
    const sanitized = sanitizeNumeric(input.value);
    if (input.value !== sanitized) {
        input.value = sanitized;
    }
    if (!sanitized) {
        input.setCustomValidity(isRequired ? 'شماره تماس الزامی است.' : '');
        return !isRequired;
    }
    if (!isValidMobile(sanitized)) {
        input.setCustomValidity('شماره تماس معتبر نیست.');
        return false;
    }
    input.setCustomValidity('');
    return true;
};

const calculateBaseAmount = () => {
    const { registrationType, entryYear, marriedStatus } = getRefs();
    if (!registrationType) {
        return null;
    }
    const type = registrationType.value;
    if (!type) {
        return null;
    }

    if (type === 'student') {
        const studentMode = document.querySelector('input[name="student_mode"]:checked');
        if (!studentMode || !entryYear.value) {
            return null;
        }
        return amountTable.student?.[entryYear.value]?.[studentMode.value] ?? null;
    }

    if (type === 'married') {
        if (!marriedStatus.value) {
            return null;
        }
        return amountTable.married?.[marriedStatus.value] ?? null;
    }

    return amountTable[type] ?? null;
};

const getSelectedPaymentType = () => {
    const selected = document.querySelector('input[name="payment_type"]:checked');
    return selected ? selected.value : null;
};

const updateAmount = () => {
    const { registrationType, childrenCount, finalAmount, amountDetails, entryYear } = getRefs();
    if (!registrationType || !finalAmount || !amountDetails) {
        return;
    }

    const amount = getCurrentAmount();
    if (!amount) {
        finalAmount.textContent = '—';
        amountDetails.textContent = 'برای مشاهده مبلغ، نوع ثبت نام و گزینه‌های لازم را انتخاب کنید.';
        return;
    }

    const children = registrationType.value === 'married' ? Math.max(parseInt(childrenCount?.value || 0, 10), 0) : 0;
    const paymentType = getSelectedPaymentType();
    const discountedAmount = discountState.isValid
        ? applyDiscountToAmount(amount, discountState.discountType, discountState.discountValue).finalAmount
        : amount;

    finalAmount.textContent = formatMoney(discountedAmount);

    const details = [];
    if (registrationType.value === 'student') {
        const studentMode = document.querySelector('input[name="student_mode"]:checked');
        details.push(`دانشجو - ${studentMode?.value === 'group' ? 'گروهی ۳ نفره' : 'انفرادی'}`);
        details.push(`ورودی ${entryYear?.value.replace('_or_before', ' و ماقبل')}`);
    } else if (registrationType.value === 'married') {
        details.push('ثبت نام متاهلین');
        if (children > 0) {
            details.push(`${children} فرزند`);
        }
    } else if (registrationType.value === 'alumni') {
        details.push('فارغ التحصیل');
    } else {
        details.push('سایر');
    }

    if (paymentType) {
        details.push(paymentType === 'installment' ? 'پرداخت قسطی' : 'پرداخت کامل');
    }

    if (discountState.isValid && discountState.discountType) {
        details.push(`با تخفیف ${getDiscountDescription(discountState.discountType, discountState.discountValue)}`);
    }

    amountDetails.textContent = details.join(' · ');
};

const discountState = {
    validatedCode: '',
    isValid: false,
    isChecking: false,
    discountType: null,
    discountValue: 0,
    discountTitle: '',
};

const resetDiscountState = () => {
    discountState.validatedCode = '';
    discountState.isValid = false;
    discountState.discountType = null;
    discountState.discountValue = 0;
    discountState.discountTitle = '';
};

const setDiscountFeedback = (message, variant = 'muted') => {
    const { discountFeedback } = getRefs();
    if (!discountFeedback) {
        return;
    }
    discountFeedback.textContent = message;
    discountFeedback.classList.remove('text-success', 'text-danger', 'text-muted');
    if (variant === 'success') {
        discountFeedback.classList.add('text-success');
    } else if (variant === 'danger') {
        discountFeedback.classList.add('text-danger');
    } else {
        discountFeedback.classList.add('text-muted');
    }
};

const getNormalizedDiscountCode = () => {
    const { discountCode } = getRefs();
    if (!discountCode) {
        return '';
    }
    return normalizeDigits(discountCode.value).trim().toUpperCase();
};

const formatMoney = (amount) => `${amount.toLocaleString('fa-IR')} تومان`;

const getCurrentAmount = () => {
    const { registrationType } = getRefs();
    if (!registrationType) {
        return null;
    }
    const base = calculateBaseAmount();
    if (!base) {
        return null;
    }
    let amount = base * 1000;
    const paymentType = getSelectedPaymentType();
    if ((registrationType.value === 'student' || registrationType.value === 'alumni') && paymentType === 'installment') {
        amount = Math.round(amount / 2);
    }
    return amount;
};

const getDiscountDescription = (discountType, discountValue) => {
    if (!discountType) {
        return 'نامشخص';
    }
    if (discountType === 'percent') {
        return `درصدی ${discountValue}٪`;
    }
    const amount = discountValue * 10000;
    return `مبلغی ${amount.toLocaleString('fa-IR')} تومان`;
};

const applyDiscountToAmount = (amount, discountType, discountValue) => {
    if (!discountType || !amount) {
        return { finalAmount: amount, discountAmount: 0 };
    }
    let discountAmount = 0;
    if (discountType === 'percent') {
        discountAmount = Math.round(amount * (discountValue / 100));
    } else {
        discountAmount = discountValue * 10000;
    }
    const finalAmount = Math.max(amount - discountAmount, 0);
    return { finalAmount, discountAmount };
};

const validateDiscountCode = async ({ submitAfter = false } = {}) => {
    const { discountCheckButton } = getRefs();
    const form = document.getElementById('registrationForm');
    const code = getNormalizedDiscountCode();

    if (!code) {
        resetDiscountState();
        setDiscountFeedback('', 'muted');
        return true;
    }

    if (discountState.isValid && discountState.validatedCode === code) {
        if (submitAfter && form) {
            form.submit();
        }
        return true;
    }

    if (discountState.isChecking) {
        return false;
    }

    discountState.isChecking = true;
    if (discountCheckButton) {
        discountCheckButton.disabled = true;
    }
    setDiscountFeedback('در حال بررسی کد تخفیف...', 'muted');

    try {
        const response = await fetch('check_discount.php', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
            },
            body: (() => {
                const payload = new FormData();
                payload.append('code', code);
                return payload;
            })(),
        });

        const data = await response.json();
        if (!response.ok || !data || data.valid !== true) {
            resetDiscountState();
            setDiscountFeedback(data?.message || 'کد تخفیف معتبر نیست.', 'danger');
            return false;
        }

        discountState.validatedCode = code;
        discountState.isValid = true;
        discountState.discountType = data?.discount?.type ?? null;
        discountState.discountValue = Number.isFinite(data?.discount?.value) ? data.discount.value : 0;
        discountState.discountTitle = data?.discount?.title ?? '';

        const currentAmount = getCurrentAmount();
        const discountDescription = getDiscountDescription(discountState.discountType, discountState.discountValue);
        if (currentAmount) {
            const { finalAmount } = applyDiscountToAmount(
                currentAmount,
                discountState.discountType,
                discountState.discountValue,
            );
            setDiscountFeedback(
                `${data.message || 'کد تخفیف معتبر است.'} نوع تخفیف: ${discountDescription} · مبلغ نهایی: ${formatMoney(
                    finalAmount,
                )}`,
                'success',
            );
        } else {
            setDiscountFeedback(
                `${data.message || 'کد تخفیف معتبر است.'} نوع تخفیف: ${discountDescription} · برای نمایش مبلغ نهایی، نوع ثبت‌نام را کامل کنید.`,
                'success',
            );
        }
        updateAmount();
        if (submitAfter && form) {
            form.submit();
        }
        return true;
    } catch (error) {
        resetDiscountState();
        setDiscountFeedback('خطا در ارتباط با سرور. دوباره تلاش کنید.', 'danger');
        return false;
    } finally {
        discountState.isChecking = false;
        if (discountCheckButton) {
            discountCheckButton.disabled = false;
        }
    }
};

let registrationAppInstance = null;

const handleRegistrationType = () => {
    const {
        registrationType,
        studentFields,
        marriedFields,
        marriedStatusField,
        groupFields,
        entryYear,
        academicLevelField,
        academicMajorField,
        academicMajor,
        academicLevel,
        alumniExtraFields,
        alumniEntryYear,
        marriedStatus,
        spouseName,
        spouseNationalCode,
        spouseBirthDate,
        childrenCount,
        paymentTypeField,
    } = getRefs();
    const studentModeInputs = getStudentModeInputs();
    const paymentTypeInputs = getPaymentTypeInputs();
    const groupRequiredFields = getGroupRequiredFields();
    if (!registrationType || !studentFields || !marriedFields || !groupFields || !marriedStatusField) {
        return;
    }
    const value = registrationType.value;
    studentFields.classList.toggle('d-none', value !== 'student');
    marriedStatusField.classList.toggle('d-none', value !== 'married');
    marriedFields.classList.toggle('d-none', value !== 'married');
    if (academicLevelField) {
        academicLevelField.classList.toggle('d-none', value !== 'student' && value !== 'alumni');
    }
    if (academicMajorField) {
        academicMajorField.classList.toggle('d-none', value !== 'student' && value !== 'alumni');
    }
    if (alumniExtraFields) {
        alumniExtraFields.classList.toggle('d-none', value !== 'alumni');
    }
    if (paymentTypeField) {
        paymentTypeField.classList.toggle('d-none', value !== 'student' && value !== 'alumni');
    }

    toggleRequired(entryYear, value === 'student');
    toggleRequired(academicMajor, value === 'student' || value === 'alumni');
    toggleRequired(academicLevel, value === 'student' || value === 'alumni');
    toggleRequired(alumniEntryYear, value === 'alumni');
    toggleRequired(marriedStatus, value === 'married');
    toggleRequired(spouseName, value === 'married');
    toggleRequired(spouseNationalCode, value === 'married');
    toggleRequired(spouseBirthDate, value === 'married');
    toggleRequired(childrenCount, value === 'married');
    studentModeInputs.forEach((input) => {
        toggleRequired(input, value === 'student');
    });
    paymentTypeInputs.forEach((input) => {
        toggleRequired(input, value === 'student' || value === 'alumni');
    });

    if (value !== 'student') {
        studentModeInputs.forEach((input) => {
            input.checked = false;
        });
        clearFields([entryYear]);
        groupFields.classList.add('d-none');
        groupRequiredFields.forEach((field) => {
            toggleRequired(field, false);
        });
        clearFields(Array.from(groupRequiredFields));
        if (registrationAppInstance) {
            registrationAppInstance.groupBirthDates = ['', ''];
        }
    }

    if (value !== 'student' && value !== 'alumni') {
        paymentTypeInputs.forEach((input) => {
            input.checked = false;
        });
    } else if (!getSelectedPaymentType()) {
        const fullPayment = document.getElementById('payment_full');
        if (fullPayment) {
            fullPayment.checked = true;
        }
    }

    if (value !== 'married') {
        clearFields([marriedStatus, spouseName, spouseNationalCode, spouseBirthDate, childrenCount]);
        if (registrationAppInstance) {
            registrationAppInstance.spouseBirthDate = '';
        }
    }

    if (value !== 'alumni') {
        clearFields([alumniEntryYear]);
    }

    if (value !== 'student' && value !== 'alumni') {
        clearFields([academicMajor, academicLevel]);
    }
    updateAmount();
};

const handleStudentMode = () => {
    const { groupFields } = getRefs();
    const groupRequiredFields = getGroupRequiredFields();
    if (!groupFields) {
        return;
    }
    const selected = document.querySelector('input[name="student_mode"]:checked');
    const isGroup = selected && selected.value === 'group';
    groupFields.classList.toggle('d-none', !isGroup);
    groupRequiredFields.forEach((field) => {
        toggleRequired(field, isGroup);
    });
    if (!isGroup) {
        clearFields(Array.from(groupRequiredFields));
        if (registrationAppInstance) {
            registrationAppInstance.groupBirthDates = ['', ''];
        }
    }
    updateAmount();
};

document.addEventListener('change', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        return;
    }
    if (target.id === 'registration_type') {
        handleRegistrationType();
        return;
    }
    if (target.getAttribute('name') === 'student_mode') {
        handleStudentMode();
        return;
    }
    if (target.getAttribute('name') === 'payment_type') {
        updateAmount();
        return;
    }
    if (target.id === 'entry_year' || target.id === 'married_status') {
        updateAmount();
    }
});
document.addEventListener('input', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        return;
    }
    if (target.id === 'children_count') {
        updateAmount();
    }
    if (target instanceof HTMLInputElement) {
        if (isNationalCodeInput(target)) {
            validateNationalCodeInput(target);
        }
        if (isMobileInput(target)) {
            validateMobileInput(target);
        }
    }
    if (target.id === 'discount_code') {
        resetDiscountState();
        setDiscountFeedback('', 'muted');
        updateAmount();
    }
});

const initializeForm = () => {
    handleRegistrationType();
    updateAmount();

    const form = document.getElementById('registrationForm');
    if (form) {
        form.addEventListener('submit', (event) => {
            let valid = true;
            form.querySelectorAll('input').forEach((input) => {
                if (isNationalCodeInput(input)) {
                    if (!validateNationalCodeInput(input)) {
                        valid = false;
                    }
                }
                if (isMobileInput(input)) {
                    if (!validateMobileInput(input)) {
                        valid = false;
                    }
                }
            });

            if (!valid || !form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            if (valid && form.checkValidity()) {
                const discountCode = getNormalizedDiscountCode();
                if (discountCode) {
                    event.preventDefault();
                    event.stopPropagation();
                    validateDiscountCode({ submitAfter: true });
                }
            }
            form.classList.add('was-validated');
        });
    }

    const { discountCheckButton } = getRefs();
    if (discountCheckButton) {
        discountCheckButton.addEventListener('click', () => {
            validateDiscountCode();
        });
    }

    if (window.GLightbox) {
        window.GLightbox({
            selector: '.glightbox',
        });
    }
};

const datePickerComponent = window.Vue3PersianDatetimePicker || window.VuePersianDatetimePicker;

if (window.Vue && datePickerComponent) {
    const registrationApp = Vue.createApp({
        data() {
            const maxBirthDate = window.moment
                ? window.moment().subtract(15, 'years').format('jYYYY/jMM/jDD')
                : '';
            return {
                birthDate: '',
                spouseBirthDate: '',
                groupBirthDates: ['', ''],
                maxBirthDate,
            };
        },
    });
    registrationApp.component('date-picker', datePickerComponent);
    registrationAppInstance = registrationApp.mount('#registrationApp');
    initializeForm();
} else {
    initializeForm();
}
