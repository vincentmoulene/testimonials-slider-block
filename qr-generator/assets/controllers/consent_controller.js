import { Controller } from '@hotwired/stimulus';

const STORAGE_KEY = 'consent.ads';

/**
 * Minimal consent gate. No third-party script — advertising or analytics — is
 * ever loaded before an explicit opt-in, and the choice is stored locally
 * (no cookie, nothing sent to the server).
 */
export default class extends Controller {
    static targets = ['banner'];
    static values = { adsenseClient: String, analyticsId: String };

    connect() {
        const stored = this.read();
        if ('granted' === stored) {
            this.load();
        } else if ('denied' !== stored && this.hasBannerTarget) {
            this.bannerTarget.hidden = false;
        }
    }

    accept() {
        this.write('granted');
        this.hide();
        this.updateConsentMode('granted');
        this.load();
    }

    reject() {
        this.write('denied');
        this.hide();
        this.updateConsentMode('denied');
    }

    reopen(event) {
        event?.preventDefault();
        if (this.hasBannerTarget) {
            this.bannerTarget.hidden = false;
        }
    }

    hide() {
        if (this.hasBannerTarget) {
            this.bannerTarget.hidden = true;
        }
    }

    updateConsentMode(state) {
        if ('function' !== typeof window.gtag) {
            return;
        }
        window.gtag('consent', 'update', {
            ad_storage: state,
            ad_user_data: state,
            ad_personalization: state,
            analytics_storage: state,
        });
    }

    load() {
        if (this.adsenseClientValue) {
            this.injectScript(`https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${encodeURIComponent(this.adsenseClientValue)}`, {
                crossOrigin: 'anonymous',
                onload: () => this.fillSlots(),
            });
        }
        if (this.analyticsIdValue) {
            this.injectScript(`https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(this.analyticsIdValue)}`, {
                onload: () => {
                    window.dataLayer = window.dataLayer || [];
                    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
                    window.gtag('js', new Date());
                    window.gtag('config', this.analyticsIdValue, { anonymize_ip: true });
                },
            });
        }
    }

    fillSlots() {
        window.adsbygoogle = window.adsbygoogle || [];
        document.querySelectorAll('ins.adsbygoogle:not([data-adsbygoogle-status])').forEach(() => {
            try {
                window.adsbygoogle.push({});
            } catch (error) {
                // A blocked or unavailable ad script must never break the tool.
            }
        });
    }

    injectScript(src, { crossOrigin, onload } = {}) {
        if (document.querySelector(`script[src="${src}"]`)) {
            return;
        }
        const script = document.createElement('script');
        script.async = true;
        script.src = src;
        if (crossOrigin) {
            script.crossOrigin = crossOrigin;
        }
        if (onload) {
            script.addEventListener('load', onload);
        }
        document.head.appendChild(script);
    }

    read() {
        try {
            return window.localStorage.getItem(STORAGE_KEY);
        } catch (error) {
            return null;
        }
    }

    write(value) {
        try {
            window.localStorage.setItem(STORAGE_KEY, value);
        } catch (error) {
            // Private mode: the choice simply is not remembered.
        }
    }
}
