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
});

const initializeForm = () => {
    handleRegistrationType();
    updateAmount();
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
