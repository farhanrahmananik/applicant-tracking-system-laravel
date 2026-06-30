import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

const filterSelectSelector = [
    '.company-toolbar select.form-select',
    '.application-filter-toolbar select.form-select',
    '.interview-filter-toolbar select.form-select',
    '.pipeline-filter-toolbar select.form-select',
    '.offer-filter-toolbar select.form-select',
    '.report-filter-panel select.form-select',
    '.audit-filter-panel select.form-select',
].join(', ');

let activeFilterSelect = null;
let filterSelectIndex = 0;

const closeFilterSelect = (filterSelect, restoreFocus = false) => {
    if (!filterSelect) {
        return;
    }

    filterSelect.wrapper.classList.remove('is-open');
    filterSelect.trigger.setAttribute('aria-expanded', 'false');
    filterSelect.menu.hidden = true;

    if (activeFilterSelect === filterSelect) {
        activeFilterSelect = null;
    }

    if (restoreFocus) {
        filterSelect.trigger.focus();
    }
};

const enhanceFilterSelect = (select) => {
    if (select.dataset.atsFilterSelect === 'true') {
        return;
    }

    filterSelectIndex += 1;

    const wrapper = document.createElement('div');
    const trigger = document.createElement('button');
    const value = document.createElement('span');
    const icon = document.createElement('i');
    const menu = document.createElement('div');
    const menuId = `ats-filter-select-${filterSelectIndex}`;
    const label = select.labels?.[0]?.textContent.trim() || select.name || 'Filter options';
    const filterSelect = { menu, select, trigger, wrapper };

    wrapper.className = 'ats-filter-select';
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);

    select.dataset.atsFilterSelect = 'true';
    select.classList.add('ats-filter-select-native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    trigger.className = 'ats-filter-select-trigger';
    trigger.type = 'button';
    trigger.setAttribute('aria-controls', menuId);
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-label', label);

    value.className = 'ats-filter-select-value';
    icon.className = 'bi bi-chevron-down';
    icon.setAttribute('aria-hidden', 'true');
    trigger.append(value, icon);

    menu.className = 'ats-filter-select-menu';
    menu.id = menuId;
    menu.setAttribute('role', 'listbox');
    menu.hidden = true;
    menu.setAttribute('aria-label', label);
    wrapper.append(trigger, menu);

    const visibleOptions = () => Array.from(menu.querySelectorAll('.ats-filter-select-option:not([hidden]):not(:disabled)'));

    const sync = () => {
        const selectedOption = select.selectedOptions[0] || select.options[0];

        value.textContent = selectedOption?.textContent.trim() || label;
        trigger.disabled = select.disabled;

        menu.querySelectorAll('.ats-filter-select-option').forEach((optionButton) => {
            const option = select.options[Number(optionButton.dataset.optionIndex)];
            const selected = option?.selected === true;

            optionButton.classList.toggle('is-selected', selected);
            optionButton.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
    };

    const selectOption = (optionButton) => {
        const option = select.options[Number(optionButton.dataset.optionIndex)];

        if (!option || option.disabled) {
            return;
        }

        select.value = option.value;
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
        closeFilterSelect(filterSelect, true);
    };

    const rebuildMenu = () => {
        menu.replaceChildren();

        Array.from(select.options).forEach((option, optionIndex) => {
            const optionButton = document.createElement('button');

            optionButton.className = 'ats-filter-select-option';
            optionButton.type = 'button';
            optionButton.setAttribute('role', 'option');
            optionButton.dataset.optionIndex = optionIndex;
            optionButton.textContent = option.textContent.trim();
            optionButton.disabled = option.disabled;
            optionButton.hidden = option.hidden;
            optionButton.addEventListener('click', () => selectOption(optionButton));
            menu.appendChild(optionButton);
        });

        sync();
    };

    const focusOption = (position) => {
        const options = visibleOptions();

        if (options.length === 0) {
            return;
        }

        const selectedIndex = options.findIndex((option) => option.classList.contains('is-selected'));
        let targetIndex = selectedIndex < 0 ? 0 : selectedIndex;

        if (position === 'first') {
            targetIndex = 0;
        } else if (position === 'last') {
            targetIndex = options.length - 1;
        }

        options[targetIndex].focus();
    };

    const open = (focusPosition = null) => {
        if (trigger.disabled) {
            return;
        }

        if (activeFilterSelect && activeFilterSelect !== filterSelect) {
            closeFilterSelect(activeFilterSelect);
        }

        activeFilterSelect = filterSelect;
        wrapper.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        menu.hidden = false;

        if (focusPosition) {
            requestAnimationFrame(() => focusOption(focusPosition));
        }
    };

    trigger.addEventListener('click', () => {
        if (wrapper.classList.contains('is-open')) {
            closeFilterSelect(filterSelect);
        } else {
            open();
        }
    });

    trigger.addEventListener('keydown', (event) => {
        if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            open(event.key === 'ArrowUp' || event.key === 'End' ? 'last' : 'first');
        }

        if (event.key === 'Escape') {
            closeFilterSelect(filterSelect);
        }
    });

    menu.addEventListener('keydown', (event) => {
        const options = visibleOptions();
        const currentIndex = options.indexOf(document.activeElement);

        if (event.key === 'Escape') {
            event.preventDefault();
            closeFilterSelect(filterSelect, true);
            return;
        }

        if (event.key === 'Tab') {
            closeFilterSelect(filterSelect);
            return;
        }

        if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();

            let nextIndex = currentIndex;

            if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = options.length - 1;
            } else if (event.key === 'ArrowDown') {
                nextIndex = Math.min(currentIndex + 1, options.length - 1);
            } else {
                nextIndex = Math.max(currentIndex - 1, 0);
            }

            options[nextIndex]?.focus();
        }
    });

    select.addEventListener('change', sync);
    select.form?.addEventListener('reset', () => window.setTimeout(sync));

    select.labels?.forEach((selectLabel) => {
        selectLabel.addEventListener('click', (event) => {
            event.preventDefault();
            trigger.focus();
        });
    });

    new MutationObserver(rebuildMenu).observe(select, {
        attributes: true,
        childList: true,
        subtree: true,
    });

    rebuildMenu();
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(filterSelectSelector).forEach(enhanceFilterSelect);

    const body = document.body;
    const sidebar = document.querySelector('[data-app-sidebar]');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');

    if (!sidebar) {
        return;
    }

    const setSidebarOpen = (open) => {
        body.classList.toggle('sidebar-open', open);
        sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');

        toggles.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    };

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            setSidebarOpen(!body.classList.contains('sidebar-open'));
        });
    });

    backdrop?.addEventListener('click', () => setSidebarOpen(false));

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setSidebarOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setSidebarOpen(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            setSidebarOpen(false);
            sidebar.removeAttribute('aria-hidden');
        }
    });

    if (window.innerWidth < 768) {
        setSidebarOpen(false);
    }
});

document.addEventListener('click', (event) => {
    if (activeFilterSelect && !activeFilterSelect.wrapper.contains(event.target)) {
        closeFilterSelect(activeFilterSelect);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && activeFilterSelect) {
        closeFilterSelect(activeFilterSelect, true);
    }
});
