export const initializeLanding = () => {
    const landing = document.querySelector('[data-landing]');

    if (! landing || landing.dataset.landingInitialized) {
        return;
    }

    landing.dataset.landingInitialized = 'true';

    const menuButton = landing.querySelector('[data-landing-menu-toggle]');
    const mobileMenu = landing.querySelector('#landing-mobile-menu');

    if (menuButton && mobileMenu) {
        menuButton.hidden = false;

        const closeMenu = () => {
            mobileMenu.hidden = true;
            menuButton.setAttribute('aria-expanded', 'false');
            menuButton.setAttribute('aria-label', 'Buka navigasi');
        };

        menuButton.addEventListener('click', () => {
            const isExpanded = menuButton.getAttribute('aria-expanded') === 'true';
            mobileMenu.hidden = isExpanded;
            menuButton.setAttribute('aria-expanded', String(! isExpanded));
            menuButton.setAttribute('aria-label', isExpanded ? 'Buka navigasi' : 'Tutup navigasi');
        });

        mobileMenu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! mobileMenu.hidden) {
                closeMenu();
                menuButton.focus();
            }
        });

        document.addEventListener('click', (event) => {
            if (! mobileMenu.hidden && ! mobileMenu.contains(event.target) && ! menuButton.contains(event.target)) {
                closeMenu();
            }
        });

        window.matchMedia('(min-width: 768px)').addEventListener('change', (event) => {
            if (event.matches) {
                closeMenu();
            }
        });
    }

    const tablist = landing.querySelector('[data-preview-tabs]');
    const tabs = [...landing.querySelectorAll('[data-preview-tab]')];
    const panels = [...landing.querySelectorAll('[data-preview-panel]')];

    if (tablist && tabs.length) {
        tablist.hidden = false;

        panels.forEach((panel) => {
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', `preview-tab-${panel.dataset.previewPanel}`);
            panel.setAttribute('tabindex', '0');
        });

        const selectTab = (selectedTab) => {
            tabs.forEach((tab) => {
                const isSelected = tab === selectedTab;
                tab.setAttribute('aria-selected', String(isSelected));
                tab.tabIndex = isSelected ? 0 : -1;
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.previewPanel !== selectedTab.dataset.previewTab;
            });
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => selectTab(tab));

            tab.addEventListener('keydown', (event) => {
                let nextIndex;

                if (event.key === 'ArrowRight') {
                    nextIndex = (index + 1) % tabs.length;
                } else if (event.key === 'ArrowLeft') {
                    nextIndex = (index - 1 + tabs.length) % tabs.length;
                } else if (event.key === 'Home') {
                    nextIndex = 0;
                } else if (event.key === 'End') {
                    nextIndex = tabs.length - 1;
                } else {
                    return;
                }

                event.preventDefault();
                selectTab(tabs[nextIndex]);
                tabs[nextIndex].focus();
            });
        });
    }

    if ('IntersectionObserver' in window && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('landing-reveal-ready');
                    entry.target.classList.add('landing-reveal-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });

        landing.querySelectorAll('[data-reveal]').forEach((element) => {
            if (element.getBoundingClientRect().top >= window.innerHeight) {
                element.classList.add('landing-reveal-ready');
                observer.observe(element);
            }
        });
    }
};
