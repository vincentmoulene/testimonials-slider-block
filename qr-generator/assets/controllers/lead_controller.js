import { Controller } from '@hotwired/stimulus';

const STORAGE_KEY = 'lead.email';

/**
 * Collects the visitor's email address on their first download.
 *
 * Once given, the address is remembered locally so the same person is never
 * asked twice; every later download is still reported to the server so the
 * generation is counted against that address.
 */
export default class extends Controller {
    static targets = ['dialog', 'form', 'error', 'success'];
    static values = { endpoint: String, mode: String, tool: String };

    connect() {
        this.pendingHref = null;
    }

    intercept(event) {
        const link = event.target.closest('a[download]');
        if (!link || 'download' !== this.modeValue) {
            return;
        }

        const known = this.readEmail();
        if (known) {
            // Already identified: count the generation, do not interrupt.
            this.send({ email: known, consent: false }, { keepalive: true });
            return;
        }

        event.preventDefault();
        this.pendingHref = link.href;
        this.open();
    }

    open() {
        if (!this.hasDialogTarget) {
            return;
        }
        if ('function' === typeof this.dialogTarget.showModal) {
            this.dialogTarget.showModal();
        } else {
            this.dialogTarget.setAttribute('open', 'open');
        }
        this.dialogTarget.querySelector('input[type=email]')?.focus();
    }

    close() {
        if (!this.hasDialogTarget) {
            return;
        }
        if ('function' === typeof this.dialogTarget.close) {
            this.dialogTarget.close();
        } else {
            this.dialogTarget.removeAttribute('open');
        }
    }

    async submit(event) {
        event.preventDefault();

        const data = new FormData(this.formTarget);
        const email = String(data.get('email') ?? '').trim();
        if (!email || !email.includes('@')) {
            this.showError(this.formTarget.querySelector('input[type=email]')?.validationMessage || 'Invalid email');
            return;
        }

        const submitButton = this.formTarget.querySelector('button[type=submit]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        const result = await this.send({
            email,
            consent: '1' === data.get('consent'),
            company: String(data.get('company') ?? ''),
        });

        if (submitButton) {
            submitButton.disabled = false;
        }

        if (!result.ok) {
            this.showError(result.error);
            return;
        }

        this.writeEmail(email);
        this.hideError();

        if (this.hasSuccessTarget) {
            this.successTarget.hidden = false;
        }

        this.close();
        this.startPendingDownload();
    }

    startPendingDownload() {
        if (!this.pendingHref) {
            return;
        }
        const href = this.pendingHref;
        this.pendingHref = null;
        // The endpoint answers with Content-Disposition: attachment, so this
        // downloads the file without navigating away from the page.
        window.location.href = href;
    }

    async send(payload, options = {}) {
        try {
            const response = await fetch(this.endpointValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tool: this.toolValue,
                    locale: document.documentElement.lang,
                    source: window.location.href,
                    ...payload,
                }),
                keepalive: options.keepalive ?? false,
            });

            if (429 === response.status) {
                return { ok: false, error: 'Too many attempts, please try again later.' };
            }

            return await response.json();
        } catch (error) {
            return { ok: false, error: error.message };
        }
    }

    showError(message) {
        if (!this.hasErrorTarget) {
            return;
        }
        this.errorTarget.textContent = message;
        this.errorTarget.hidden = false;
    }

    hideError() {
        if (this.hasErrorTarget) {
            this.errorTarget.hidden = true;
        }
    }

    readEmail() {
        try {
            return window.localStorage.getItem(STORAGE_KEY);
        } catch (error) {
            return null;
        }
    }

    writeEmail(email) {
        try {
            window.localStorage.setItem(STORAGE_KEY, email);
        } catch (error) {
            // Private mode: the visitor will simply be asked again next time.
        }
    }
}
