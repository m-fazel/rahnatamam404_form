const getRefs = () => ({
    registrationType: document.getElementById('registration_type'),
    studentFields: document.getElementById('studentFields'),
    marriedFields: document.getElementById('marriedFields'),
    groupFields: document.getElementById('groupFields'),
    entryYear: document.getElementById('entry_year'),
    marriedStatus: document.getElementById('married_status'),
    spouseName: document.getElementById('spouse_name'),
    spouseNationalCode: document.getElementById('spouse_national_code'),
    spouseBirthDate: document.getElementById('spouse_birth_date'),
    childrenCount: document.getElementById('children_count'),
    finalAmount: document.getElementById('finalAmount'),
    amountDetails: document.getElementById('amountDetails'),
});

const getStudentModeInputs = () => document.querySelectorAll('input[name="student_mode"]');
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
    if (/^(\d)\1{9}$/.test(code)) {
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

const updateAmount = () => {
    const { registrationType, childrenCount, finalAmount, amountDetails, entryYear } = getRefs();
    if (!registrationType || !finalAmount || !amountDetails) {
        return;
    }

    const base = calculateBaseAmount();
    if (!base) {
        finalAmount.textContent = '—';
        amountDetails.textContent = 'برای مشاهده مبلغ، نوع ثبت نام و گزینه‌های لازم را انتخاب کنید.';
        return;
    }

    const children = registrationType.value === 'married' ? Math.max(parseInt(childrenCount?.value || 0, 10), 0) : 0;
    const amount = base * 1000 + (children * 50000);

    finalAmount.textContent = `${amount.toLocaleString('fa-IR')} تومان`;

    const details = [];
    if (registrationType.value === 'student') {
        const studentMode = document.querySelector('input[name="student_mode"]:checked');
        details.push(`دانشجو - ${studentMode?.value === 'group' ? 'گروهی ۴ نفره' : 'انفرادی'}`);
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

    amountDetails.textContent = details.join(' · ');
};

const handleRegistrationType = () => {
    const {
        registrationType,
        studentFields,
        marriedFields,
        groupFields,
        entryYear,
        marriedStatus,
        spouseName,
        spouseNationalCode,
        spouseBirthDate,
    } = getRefs();
    const studentModeInputs = getStudentModeInputs();
    const groupRequiredFields = getGroupRequiredFields();
    if (!registrationType || !studentFields || !marriedFields || !groupFields) {
        return;
    }
    const value = registrationType.value;
    studentFields.classList.toggle('d-none', value !== 'student');
    marriedFields.classList.toggle('d-none', value !== 'married');

    toggleRequired(entryYear, value === 'student');
    toggleRequired(marriedStatus, value === 'married');
    toggleRequired(spouseName, value === 'married');
    toggleRequired(spouseNationalCode, value === 'married');
    toggleRequired(spouseBirthDate, value === 'married');
    studentModeInputs.forEach((input) => {
        toggleRequired(input, value === 'student');
    });

    if (value !== 'student') {
        studentModeInputs.forEach((input) => {
            input.checked = false;
        });
        groupFields.classList.add('d-none');
        groupRequiredFields.forEach((field) => {
            toggleRequired(field, false);
        });
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
            form.classList.add('was-validated');
        });
    }
};

const datePickerComponent = window.Vue3PersianDatetimePicker || window.VuePersianDatetimePicker;

if (window.Vue && datePickerComponent) {
    const registrationApp = Vue.createApp({
        data() {
            return {
                birthDate: '',
                spouseBirthDate: '',
                groupBirthDates: ['', '', '', ''],
            };
        },
    });
    registrationApp.component('date-picker', datePickerComponent);
    registrationApp.mount('#registrationApp');
    initializeForm();
} else {
    initializeForm();
}
