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
    sidebarCollapsed: false,
    navGroups: {
        foundation: true,
        assessment: true,
        reports: true,
        settings: true,
        account: true,
    },

    init() {
        // Remember the desktop rail preference without coupling it to server state.
        this.sidebarCollapsed = window.localStorage.getItem('erapor.sidebarCollapsed') === 'true';

        this.$nextTick(() => {
            this.bindSidebarScrollPersistence();
            requestAnimationFrame(() => this.restoreSidebarScrollPositions());
        });

        this.$watch('mobileSidebarOpen', (isOpen) => {
            if (isOpen) {
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => this.$refs.closeButton?.focus());
            }
        });

        this.$watch('sidebarCollapsed', (isCollapsed) => {
            window.localStorage.setItem('erapor.sidebarCollapsed', isCollapsed ? 'true' : 'false');
        });
    },

    toggleSidebar() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
    },

    toggleNavGroup(group) {
        this.navGroups[group] = !this.navGroups[group];
    },

    bindSidebarScrollPersistence() {
        ['desktop-sidebar-navigation', 'mobile-sidebar-navigation'].forEach((id) => {
            const navigation = document.getElementById(id);

            if (!navigation || navigation.dataset.scrollPersistenceBound === 'true') {
                return;
            }

            navigation.dataset.scrollPersistenceBound = 'true';
            navigation.addEventListener('scroll', (event) => this.persistSidebarScroll(event), { passive: true });
        });

        window.addEventListener('pagehide', () => this.persistSidebarScrollPositions(), { once: true });
    },

    persistSidebarScroll(event) {
        try {
            window.sessionStorage.setItem(`erapor.sidebarScrollTop.${event.currentTarget.id}`, String(event.currentTarget.scrollTop));
        } catch {
            // Storage may be unavailable in private browsing; scrolling must still work normally.
        }
    },

    persistSidebarScrollPositions() {
        ['desktop-sidebar-navigation', 'mobile-sidebar-navigation'].forEach((id) => {
            const navigation = document.getElementById(id);

            if (navigation) {
                try {
                    const storageKey = `erapor.sidebarScrollTop.${id}`;
                    const savedScrollTop = Number.parseInt(window.sessionStorage.getItem(storageKey) ?? '0', 10);

                    // Closing the mobile drawer can reset its hidden scroll container to zero.
                    // Keep the last meaningful position instead of overwriting it during pagehide.
                    if (navigation.scrollTop === 0 && Number.isFinite(savedScrollTop) && savedScrollTop > 0) {
                        return;
                    }
                } catch {
                    // Storage may be unavailable in private browsing; scrolling must still work normally.
                }

                this.persistSidebarScroll({ currentTarget: navigation });
            }
        });
    },

    restoreSidebarScrollPositions() {
        ['desktop-sidebar-navigation', 'mobile-sidebar-navigation'].forEach((id) => {
            const navigation = document.getElementById(id);

            if (!navigation) {
                return;
            }

            try {
                const savedScrollTop = Number.parseInt(window.sessionStorage.getItem(`erapor.sidebarScrollTop.${id}`) ?? '0', 10);

                if (Number.isFinite(savedScrollTop) && savedScrollTop > 0) {
                    navigation.scrollTop = savedScrollTop;
                }
            } catch {
                // Storage may be unavailable in private browsing; keep the default scroll position.
            }
        });
    },

    openMobileSidebar(event) {
        this.menuTrigger = event.currentTarget;
        this.mobileSidebarOpen = true;
        this.$nextTick(() => requestAnimationFrame(() => this.restoreSidebarScrollPositions()));
    },

    closeMobileSidebar() {
        this.persistSidebarScrollPositions();
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

window.confirmDialog = () => ({
    isOpen: false,
    message: '',
    trigger: null,

    openDialog(event) {
        this.message = event.detail.message;
        this.trigger = event.detail.trigger;
        this.isOpen = true;
        this.$nextTick(() => this.$refs.cancel?.focus());
    },

    cancel() {
        this.isOpen = false;
        this.message = '';
        this.trigger = null;
    },

    approve() {
        const trigger = this.trigger;
        this.cancel();

        if (! trigger) {
            return;
        }

        if (trigger.form) {
            trigger.form.requestSubmit(trigger);
            return;
        }

        trigger.click();
    },
});

window.Alpine = Alpine;

Alpine.start();
