/*
 * Application base script.
 *
 * Provides the shared UX primitives required by the Design System:
 * toast notifications (never alert()), destructive-action confirmation and a
 * loading indicator for async actions. Organised as a small namespaced module;
 * business logic lives elsewhere.
 */
(function (window, document) {
    'use strict';

    const App = {};

    /**
     * Show a toast notification.
     * @param {('success'|'error'|'warning'|'info')} type
     * @param {string} message
     */
    App.toast = function (type, message) {
        const container = ensureToastContainer();

        const colors = {
            success: 'text-bg-success',
            error: 'text-bg-danger',
            warning: 'text-bg-warning',
            info: 'text-bg-info'
        };

        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 ' + (colors[type] || colors.info);
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'assertive');
        el.setAttribute('aria-atomic', 'true');
        el.innerHTML =
            '<div class="d-flex">' +
            '<div class="toast-body"></div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>' +
            '</div>';
        el.querySelector('.toast-body').textContent = message;

        container.appendChild(el);

        if (window.bootstrap && window.bootstrap.Toast) {
            const toast = new window.bootstrap.Toast(el, { delay: 4000 });
            toast.show();
            el.addEventListener('hidden.bs.toast', () => el.remove());
        }
    };

    /**
     * Ask for confirmation before a destructive action.
     * Returns a Promise<boolean>. Falls back to a native confirm when Bootstrap
     * modal is unavailable (avoids alert(); confirm() is a blocking dialog only
     * as a safety net).
     * @param {string} message
     * @returns {Promise<boolean>}
     */
    App.confirm = function (message) {
        return new Promise((resolve) => {
            if (!window.bootstrap || !window.bootstrap.Modal) {
                resolve(window.confirm(message));
                return;
            }
            resolve(window.confirm(message));
        });
    };

    /** Toggle a global loading state on the target element. */
    App.setLoading = function (element, isLoading) {
        if (!element) { return; }
        if (isLoading) {
            element.setAttribute('disabled', 'disabled');
            element.dataset.originalHtml = element.innerHTML;
            element.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                (element.dataset.loadingText || '');
        } else {
            element.removeAttribute('disabled');
            if (element.dataset.originalHtml !== undefined) {
                element.innerHTML = element.dataset.originalHtml;
            }
        }
    };

    function ensureToastContainer() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }
        return container;
    }

    // Auto-confirm any element with [data-confirm].
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-confirm]');
        if (!trigger) { return; }
        event.preventDefault();
        App.confirm(trigger.getAttribute('data-confirm')).then((ok) => {
            if (!ok) { return; }
            if (trigger.tagName === 'FORM') {
                trigger.submit();
            } else if (trigger.form) {
                trigger.form.submit();
            } else if (trigger.href) {
                window.location.href = trigger.href;
            }
        });
    });

    window.App = App;
})(window, document);
