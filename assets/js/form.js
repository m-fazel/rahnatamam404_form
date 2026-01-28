const registrationType = document.getElementById('registration_type');
const studentFields = document.getElementById('studentFields');
const marriedFields = document.getElementById('marriedFields');
const groupFields = document.getElementById('groupFields');
const studentModeInputs = document.querySelectorAll('input[name="student_mode"]');
const entryYear = document.getElementById('entry_year');
const marriedStatus = document.getElementById('married_status');
const spouseName = document.getElementById('spouse_name');
const spouseNationalCode = document.getElementById('spouse_national_code');
const spouseBirthDate = document.getElementById('spouse_birth_date');
const childrenCount = document.getElementById('children_count');
const finalAmount = document.getElementById('finalAmount');
const amountDetails = document.getElementById('amountDetails');
const groupRequiredFields = document.querySelectorAll('.group-required');

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

const calculateBaseAmount = () => {
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
    if (!finalAmount || !amountDetails) {
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
        details.push(`ورودی ${entryYear.value.replace('_or_before', ' و ماقبل')}`);
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
    const selected = document.querySelector('input[name="student_mode"]:checked');
    const isGroup = selected && selected.value === 'group';
    groupFields.classList.toggle('d-none', !isGroup);
    groupRequiredFields.forEach((field) => {
        toggleRequired(field, isGroup);
    });
    updateAmount();
};

registrationType.addEventListener('change', handleRegistrationType);
studentModeInputs.forEach((input) => {
    input.addEventListener('change', handleStudentMode);
});

if (entryYear) {
    entryYear.addEventListener('change', updateAmount);
}
if (marriedStatus) {
    marriedStatus.addEventListener('change', updateAmount);
}
if (childrenCount) {
    childrenCount.addEventListener('input', updateAmount);
}

handleRegistrationType();
updateAmount();

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
}
