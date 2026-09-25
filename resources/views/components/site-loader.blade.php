<div id="site-loader" class="site-loader" role="status" aria-live="polite" aria-label="Loading">
    <div class="site-loader-progress" aria-hidden="true"></div>
    <div class="site-loader-panel">
        <div class="site-loader-brand">
            <span class="site-loader-brand-mark"><i class="fas fa-heart-pulse"></i></span>
            <span>MediForm</span>
        </div>
        <div class="site-loader-status">
            <span class="site-loader-spinner" aria-hidden="true"></span>
            <span>Loading your workspace</span>
            <span class="site-loader-dots" aria-hidden="true"><i></i><i></i><i></i></span>
        </div>
    </div>
</div>

<script>
    (() => {
        const loader = document.getElementById('site-loader');
        if (!loader) return;

        let suppressUnload = false;
        const showLoader = () => loader.classList.add('is-active');
        const isDownloadLink = (link) => link?.hasAttribute('download') || link?.pathname.includes('/export');
        const isInternalNavigation = (link) => {
            if (!link || link.target === '_blank' || isDownloadLink(link)) return false;
            if (link.origin !== window.location.origin) return false;
            if (link.pathname === window.location.pathname && link.search === window.location.search) return false;
            return !link.hash || link.pathname !== window.location.pathname;
        };

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (isDownloadLink(link)) {
                suppressUnload = true;
                window.setTimeout(() => { suppressUnload = false; }, 10000);
                return;
            }
            if (!isInternalNavigation(link) || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            showLoader();
        });

        document.addEventListener('submit', (event) => {
            if (event.defaultPrevented) return;
            showLoader();
        });

        window.addEventListener('beforeunload', (event) => {
            if (suppressUnload) return;
            showLoader();
        });

        window.addEventListener('pageshow', () => loader.classList.remove('is-active'));
    })();
</script>
