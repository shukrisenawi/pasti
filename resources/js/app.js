import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

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

function decodeHtml(value) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = value;
    return textarea.value;
}

function extractConfirmMessage(source) {
    if (!source) return 'Adakah anda pasti?';

    const decoded = decodeHtml(source);
    const match = decoded.match(/confirm\((['"`])([\s\S]*?)\1\)/);

    if (!match || !match[2]) {
        return 'Adakah anda pasti?';
    }

    return match[2].replace(/\\'/g, "'").replace(/\\"/g, '"');
}

function swalIconFromAlertClasses(element) {
    if (element.classList.contains('alert-error')) return 'error';
    if (element.classList.contains('alert-warning')) return 'warning';
    if (element.classList.contains('alert-info')) return 'info';
    return 'success';
}

function showSweetAlertsFromDom() {
    const alerts = [...document.querySelectorAll('.alert')].filter((el) => !el.dataset.swalProcessed);

    alerts.forEach((alertEl) => {
        alertEl.dataset.swalProcessed = 'true';

        const html = alertEl.innerHTML?.trim() ?? '';
        if (html === '') {
            alertEl.remove();
            return;
        }

        const icon = swalIconFromAlertClasses(alertEl);
        const title = icon === 'error' ? 'Ralat' : icon === 'warning' ? 'Perhatian' : 'Berjaya';

        Swal.fire({
            icon,
            title,
            html,
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: 'swal2-confirm btn btn-primary',
            },
            buttonsStyling: false,
        });

        alertEl.remove();
    });
}

async function runSweetConfirm(message) {
    const result = await Swal.fire({
        icon: 'question',
        title: 'Pengesahan',
        text: message || 'Adakah anda pasti?',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            confirmButton: 'swal2-confirm btn btn-primary',
            cancelButton: 'swal2-cancel btn btn-outline',
        },
        buttonsStyling: false,
    });

    return result.isConfirmed;
}

function initSweetAlertConfirmInterceptors() {
    document.addEventListener(
        'click',
        async (event) => {
            const trigger = event.target.closest('[onclick*="confirm("]');
            if (!trigger) return;

            const message = extractConfirmMessage(trigger.getAttribute('onclick') || '');

            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            const confirmed = await runSweetConfirm(message);
            if (!confirmed) return;

            if (trigger.tagName === 'A' && trigger.getAttribute('href')) {
                window.location.href = trigger.getAttribute('href');
                return;
            }

            const form = trigger.form || trigger.closest('form');
            if (form) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(trigger.tagName === 'BUTTON' ? trigger : undefined);
                } else {
                    form.submit();
                }
            }
        },
        true
    );

    document.addEventListener(
        'submit',
        async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            const onsubmitValue = form.getAttribute('onsubmit') || '';
            if (!onsubmitValue.includes('confirm(')) return;
            if (form.dataset.swalSubmitting === 'true') return;

            const message = extractConfirmMessage(onsubmitValue);

            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            const confirmed = await runSweetConfirm(message);
            if (!confirmed) return;

            form.dataset.swalSubmitting = 'true';
            form.removeAttribute('onsubmit');
            form.submit();
        },
        true
    );
}

function initSweetAlertEnhancements() {
    initSweetAlertConfirmInterceptors();
    showSweetAlertsFromDom();

    document.addEventListener('livewire:navigated', () => {
        showSweetAlertsFromDom();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSweetAlertEnhancements, { once: true });
} else {
    initSweetAlertEnhancements();
}
