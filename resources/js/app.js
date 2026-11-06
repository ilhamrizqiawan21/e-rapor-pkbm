import Alpine from 'alpinejs';

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

window.mobileNavigation = () => ({
    mobileSidebarOpen: false,
    menuTrigger: null,

    init() {
        this.$watch('mobileSidebarOpen', (isOpen) => {
            if (isOpen) {
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => this.$refs.closeButton?.focus());
            }
        });
    },

    openMobileSidebar(event) {
        this.menuTrigger = event.currentTarget;
        this.mobileSidebarOpen = true;
    },

    closeMobileSidebar() {
        this.mobileSidebarOpen = false;
        document.body.classList.remove('overflow-hidden');
        this.$nextTick(() => {
            const main = document.querySelector('main');
            main?.removeAttribute('inert');
            main?.removeAttribute('aria-hidden');
            requestAnimationFrame(() => this.menuTrigger?.focus());
        });
    },

    trapFocus(event) {
        if (!this.mobileSidebarOpen || event.key !== 'Tab') {
            return;
        }

        const focusable = [...this.$refs.drawer.querySelectorAll(focusableSelector)]
            .filter((element) => element.offsetParent !== null);

        if (focusable.length === 0) {
            event.preventDefault();
            this.$refs.closeButton?.focus();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
});

window.Alpine = Alpine;

Alpine.start();
