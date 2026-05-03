import './bootstrap';

import { balloons } from 'balloons-js';

window.balloons = balloons;

const REQUIRED_FIELD_SELECTOR = 'input[required], select[required], textarea[required]';
const GENERATED_ASTERISK_ATTR = 'data-generated-required-asterisk';
const GENERATED_ASTERISK_SELECTOR = `.required-asterisk[${GENERATED_ASTERISK_ATTR}="true"]`;

function isRequiredField(field) {
    return !field.disabled && field.type !== 'hidden';
}

function findFieldLabel(field) {
    if (field.id) {
        const explicitLabel = document.querySelector(`label[for="${CSS.escape(field.id)}"]`);
        if (explicitLabel) return explicitLabel;
    }

    const wrappingLabel = field.closest('label');
    if (wrappingLabel) return wrappingLabel;

    let sibling = field.previousElementSibling;
    while (sibling) {
        if (sibling.tagName === 'LABEL') return sibling;
        sibling = sibling.previousElementSibling;
    }

    const fieldGroup = field.closest('div, td, th, li, section, article');
    if (!fieldGroup) return null;

    const groupLabel = fieldGroup.querySelector('label');
    if (!groupLabel || groupLabel.contains(field)) return null;

    return groupLabel;
}

function syncRequiredAsterisks() {
    document.querySelectorAll(GENERATED_ASTERISK_SELECTOR).forEach((asterisk) => asterisk.remove());

    document.querySelectorAll(REQUIRED_FIELD_SELECTOR).forEach((field) => {
        if (!isRequiredField(field)) return;

        const label = findFieldLabel(field);
        if (!label) return;

        if (label.querySelector('.required-asterisk')) return;
        if (label.textContent?.trim().endsWith('*')) return;

        const asterisk = document.createElement('span');
        asterisk.className = 'required-asterisk ml-1 text-red-600';
        asterisk.textContent = '*';
        asterisk.setAttribute(GENERATED_ASTERISK_ATTR, 'true');
        label.appendChild(asterisk);
    });
}

const queueRequiredAsteriskSync = (() => {
    let scheduled = false;

    return () => {
        if (scheduled) return;
        scheduled = true;

        requestAnimationFrame(() => {
            scheduled = false;
            syncRequiredAsterisks();
        });
    };
})();

function initRequiredAsteriskSync() {
    queueRequiredAsteriskSync();

    document.addEventListener('input', queueRequiredAsteriskSync);
    document.addEventListener('change', queueRequiredAsteriskSync);
    document.addEventListener('livewire:navigated', queueRequiredAsteriskSync);

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'childList') {
                queueRequiredAsteriskSync();
                return;
            }

            if (
                mutation.type === 'attributes' &&
                ['required', 'disabled', 'id', 'for', 'class'].includes(mutation.attributeName ?? '')
            ) {
                queueRequiredAsteriskSync();
                return;
            }
        }
    });

    observer.observe(document.body, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['required', 'disabled', 'id', 'for', 'class'],
    });
}

function formatKadPengenalan(value) {
    const digits = value.replace(/\D/g, '').slice(0, 12);
    const first = digits.slice(0, 6);
    const second = digits.slice(6, 8);
    const third = digits.slice(8, 12);

    return [first, second, third].filter(Boolean).join('-');
}

function syncMaskedInputs() {
    document.querySelectorAll('input[data-mask="kad-pengenalan"]').forEach((field) => {
        field.value = formatKadPengenalan(field.value);

        if (field.dataset.maskBound === 'true') {
            return;
        }

        field.addEventListener('input', () => {
            field.value = formatKadPengenalan(field.value);
        });

        field.dataset.maskBound = 'true';
    });
}

const queueMaskedInputSync = (() => {
    let scheduled = false;

    return () => {
        if (scheduled) return;
        scheduled = true;

        requestAnimationFrame(() => {
            scheduled = false;
            syncMaskedInputs();
        });
    };
})();

function initMaskedInputSync() {
    queueMaskedInputSync();
    document.addEventListener('livewire:navigated', queueMaskedInputSync);

    const observer = new MutationObserver(() => {
        queueMaskedInputSync();
    });

    observer.observe(document.body, {
        subtree: true,
        childList: true,
    });
}

const selectableRowClassNames = [
    'bg-primary/10',
    'shadow-[inset_0_0_0_2px_rgba(59,130,246,0.18)]',
    'scale-[0.998]',
];
const selectableCellClassNames = ['bg-sky-100/80', 'text-slate-950'];
const selectableStickyCellClassNames = ['bg-sky-100'];

function clearSelectedRows() {
    document.querySelectorAll('[data-selectable-row].is-selected').forEach((row) => {
        row.classList.remove('is-selected', ...selectableRowClassNames);
        row.querySelectorAll('td').forEach((cell) => {
            cell.classList.remove(...selectableCellClassNames);

            if (cell.classList.contains('sticky')) {
                cell.classList.remove(...selectableStickyCellClassNames);
            }
        });
    });
}

function initSelectableRows() {
    document.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof Element) || target.closest('a, button, input, textarea, select, option, label')) {
            return;
        }

        const row = target.closest('[data-selectable-row]');

        if (!(row instanceof HTMLElement)) {
            return;
        }

        clearSelectedRows();
        row.classList.add('is-selected', ...selectableRowClassNames);
        row.querySelectorAll('td').forEach((cell) => {
            cell.classList.add(...selectableCellClassNames);

            if (cell.classList.contains('sticky')) {
                cell.classList.add(...selectableStickyCellClassNames);
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initRequiredAsteriskSync();
        initMaskedInputSync();
        initSelectableRows();
    }, { once: true });
} else {
    initRequiredAsteriskSync();
    initMaskedInputSync();
    initSelectableRows();
}
