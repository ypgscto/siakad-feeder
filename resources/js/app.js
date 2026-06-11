import './bootstrap';

import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        collapsed: localStorage.getItem('sifeeder-sidebar-collapsed') === '1',
        mobileOpen: false,

        toggleCollapse() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sifeeder-sidebar-collapsed', this.collapsed ? '1' : '0');
        },

        openMobile() {
            this.mobileOpen = true;
        },

        closeMobile() {
            this.mobileOpen = false;
        },
    });
});

window.Alpine = Alpine;

Alpine.start();
