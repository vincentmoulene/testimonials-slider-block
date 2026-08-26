import { Controller } from '@hotwired/stimulus';

/**
 * Live preview for every generator.
 *
 * The form is a plain GET form that works without JavaScript (the server
 * renders the code from the query string). When JS is available we intercept
 * it, debounce the input and swap the preview + download links in place.
 */
export default class extends Controller {
    static targets = ['form', 'image', 'error', 'downloadPng', 'downloadSvg', 'downloadWebp'];
    static values = { endpoint: String, imageBase: String, tool: String };

    connect() {
        this.timer = null;
        this.controller = null;
    }

    disconnect() {
        clearTimeout(this.timer);
        this.controller?.abort();
    }

    schedule() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.update(), 350);
    }

    submit(event) {
        event.preventDefault();
        clearTimeout(this.timer);
        this.update();
    }

    async update() {
        const data = new FormData(this.formTarget);
        const values = {};
        for (const [key, value] of data.entries()) {
            values[key] = value;
        }
        // Unchecked checkboxes are absent from FormData; normalise them.
        this.formTarget.querySelectorAll('input[type=checkbox]').forEach((input) => {
            values[input.name] = input.checked ? '1' : '';
        });

        const body = {
            tool: this.toolValue,
            locale: document.documentElement.lang,
            values,
            margin: Number(values.margin ?? 16),
            ecc: values.ecc ?? 'M',
            fg: values.fg ?? '#111111',
            bg: values.bg ?? '#ffffff',
            transparent: values.transparent === '1',
        };

        this.controller?.abort();
        this.controller = new AbortController();
        this.element.classList.add('is-loading');

        try {
            const response = await fetch(this.endpointValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
                signal: this.controller.signal,
            });

            if (429 === response.status) {
                this.showError(this.element.dataset.rateLimitMessage || 'Too many requests, please slow down.');
                return;
            }

            const result = await response.json();
            if (!result.ok) {
                this.showError(result.error);
                return;
            }

            this.hideError();
            this.imageTarget.src = result.image;
            this.updateDownloads(values);
            this.updateAddressBar(values);
        } catch (error) {
            if ('AbortError' !== error.name) {
                this.showError(error.message);
            }
        } finally {
            this.element.classList.remove('is-loading');
        }
    }

    updateDownloads(values) {
        const targets = { png: this.downloadPngTarget, svg: this.downloadSvgTarget, webp: this.downloadWebpTarget };
        for (const [format, link] of Object.entries(targets)) {
            if (!link) {
                continue;
            }
            const params = this.params(values);
            params.set('size', '1024');
            params.set('download', '1');
            link.href = `${this.imageBaseValue.replace(/png$/, format)}?${params.toString()}`;
        }
    }

    updateAddressBar(values) {
        if (!window.history?.replaceState) {
            return;
        }
        const params = this.params(values);
        window.history.replaceState(null, '', `${window.location.pathname}?${params.toString()}`);
    }

    params(values) {
        const params = new URLSearchParams();
        params.set('t', this.toolValue);
        for (const [key, value] of Object.entries(values)) {
            if ('' !== value && null !== value && undefined !== value) {
                params.set(key, value);
            }
        }

        return params;
    }

    showError(message) {
        this.errorTarget.textContent = message;
        this.errorTarget.hidden = false;
    }

    hideError() {
        this.errorTarget.textContent = '';
        this.errorTarget.hidden = true;
    }
}
