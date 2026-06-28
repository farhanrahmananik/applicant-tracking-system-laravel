(function () {
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-company-select]').forEach(function (companySelect) {
            const targetId = companySelect.getAttribute('data-department-target');
            const departmentSelect = document.getElementById(targetId);

            if (!departmentSelect) {
                return;
            }

            function filterDepartments(clearInvalid) {
                const companyId = companySelect.value;
                const selectedOption = departmentSelect.selectedOptions[0];

                departmentSelect.querySelectorAll('option[data-company-id]').forEach(function (option) {
                    const matches = companyId === '' || option.dataset.companyId === companyId;

                    option.hidden = !matches;
                    option.disabled = !matches;
                });

                if (
                    clearInvalid
                    && selectedOption
                    && selectedOption.value !== ''
                    && selectedOption.dataset.companyId !== companyId
                ) {
                    departmentSelect.value = '';
                }
            }

            filterDepartments(false);
            companySelect.addEventListener('change', function () {
                filterDepartments(true);
            });
        });
    });
})();
