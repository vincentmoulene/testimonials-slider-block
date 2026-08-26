import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        this.onDocumentClick = (event) => {
            if (!this.element.contains(event.target)) {
                this.close();
            }
        };
        this.onKeydown = (event) => {
            if ('Escape' === event.key) {
                this.close();
            }
        };
        document.addEventListener('click', this.onDocumentClick);
        document.addEventListener('keydown', this.onKeydown);
    }

    disconnect() {
        document.removeEventListener('click', this.onDocumentClick);
        document.removeEventListener('keydown', this.onKeydown);
    }

    toggle() {
        this.menuTarget.hidden ? this.open() : this.close();
    }

    open() {
        this.menuTarget.hidden = false;
        this.button?.setAttribute('aria-expanded', 'true');
    }

    close() {
        this.menuTarget.hidden = true;
        this.button?.setAttribute('aria-expanded', 'false');
    }

    get button() {
        return this.element.querySelector('.lang-button');
    }
}
