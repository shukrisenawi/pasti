import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRequiredAsteriskSync, { once: true });
} else {
    initRequiredAsteriskSync();
}
