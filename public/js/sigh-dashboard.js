(() => {
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-overlay]');
    const openButton = document.querySelector('[data-menu-open]');

    const close = () => {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('show');
    };

    openButton?.addEventListener('click', () => {
        sidebar?.classList.add('open');
        overlay?.classList.add('show');
    });

    overlay?.addEventListener('click', close);
    window.addEventListener('resize', () => {
        if (window.innerWidth > 860) close();
    });
})();
