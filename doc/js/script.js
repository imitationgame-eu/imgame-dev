(function () {
    // documentation details
    const documentationPage = document.querySelector('.documentation-page');

    if (documentationPage) {
        const documentationSidebar = documentationPage.querySelector('.documentation-sidebar');
        const documentationSidebarToggler = documentationPage.querySelector('.documentation-sidebar__toggle');

        if (documentationSidebarToggler) {
            documentationSidebarToggler.addEventListener('click', (e) => {
                e.preventDefault();
                documentationSidebar.classList.toggle('is-open');
            });
        }
    }
})();