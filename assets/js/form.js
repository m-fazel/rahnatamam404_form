const registrationType = document.getElementById('registration_type');
const studentFields = document.getElementById('studentFields');
const marriedFields = document.getElementById('marriedFields');
const groupFields = document.getElementById('groupFields');
const groupCountInput = document.getElementById('group_count');
const groupMembersContainer = document.getElementById('groupMembers');
const studentModeInputs = document.querySelectorAll('input[name="student_mode"]');
const entryYear = document.getElementById('entry_year');
const marriedStatus = document.getElementById('married_status');
const spouseName = document.getElementById('spouse_name');
const spouseNationalCode = document.getElementById('spouse_national_code');
const spouseBirthDate = document.getElementById('spouse_birth_date');

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

const buildGroupMembers = (count) => {
    groupMembersContainer.innerHTML = '';
    const total = Math.max(parseInt(count || 0, 10), 2);

    for (let i = 0; i < total; i += 1) {
        const card = document.createElement('div');
        card.className = 'member-card';
        card.innerHTML = `
            <h3 class="h6 fw-semibold">عضو ${i + 1}</h3>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نام</label>
                    <input type="text" class="form-control" name="group_members[${i}][first_name]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نام خانوادگی</label>
                    <input type="text" class="form-control" name="group_members[${i}][last_name]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">جنسیت</label>
                    <select class="form-select" name="group_members[${i}][gender]" required>
                        <option value="">انتخاب کنید</option>
                        <option value="male">مرد</option>
                        <option value="female">زن</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">کد ملی</label>
                    <input type="text" class="form-control" name="group_members[${i}][national_code]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">تاریخ تولد</label>
                    <input type="date" class="form-control" name="group_members[${i}][birth_date]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شماره تماس</label>
                    <input type="text" class="form-control" name="group_members[${i}][mobile]" required>
                </div>
            </div>
        `;
        groupMembersContainer.appendChild(card);
    }
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
    }
};

const handleStudentMode = () => {
    const selected = document.querySelector('input[name="student_mode"]:checked');
    const isGroup = selected && selected.value === 'group';
    groupFields.classList.toggle('d-none', !isGroup);
    if (isGroup) {
        buildGroupMembers(groupCountInput.value);
    } else {
        groupMembersContainer.innerHTML = '';
    }
};

registrationType.addEventListener('change', handleRegistrationType);
studentModeInputs.forEach((input) => {
    input.addEventListener('change', handleStudentMode);
});

if (groupCountInput) {
    groupCountInput.addEventListener('input', (event) => {
        buildGroupMembers(event.target.value);
    });
}

handleRegistrationType();
