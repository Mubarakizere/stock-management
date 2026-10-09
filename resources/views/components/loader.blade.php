{{-- ⚡ Full-Screen Site Loader --}}
{{-- Minimalist, refined aesthetic: crisp brand mark, hairline spinner, frosted backdrop --}}
<div id="site-full-loader"
     class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm select-none"
     style="transition: opacity 0.2s ease-out, visibility 0.2s ease-out; opacity: 1; visibility: visible;"
     aria-live="polite"
     aria-busy="true">

    <div class="flex flex-col items-center justify-center">
        {{-- App Icon Mark --}}
        <div class="w-12 h-12 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-3.5">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                <path d="m3.3 7 8.7 5 8.7-5"/>
                <path d="M12 22V12"/>
            </svg>
        </div>

        {{-- Hairline Spinner --}}
        <div class="relative w-6 h-6">
            <svg class="animate-spin w-6 h-6 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-20 text-gray-400 dark:text-gray-500" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5"></circle>
                <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-2.5a6.5 6.5 0 0 0-6.5-6.5V3z"></path>
            </svg>
        </div>
    </div>
</div>

<script>
(function () {
    var loader = document.getElementById('site-full-loader');
    if (!loader) return;

    var safetyTimer = null;

    var SiteLoader = {
        show: function () {
            clearTimeout(safetyTimer);
            loader.style.visibility = 'visible';
            loader.style.pointerEvents = 'auto';
            loader.offsetHeight; // force reflow for smooth transition
            loader.style.opacity = '1';

            // Safety fallback: auto-hide after 6 seconds in case navigation is interrupted
            safetyTimer = setTimeout(function () {
                SiteLoader.hide();
            }, 6000);
        },

        hide: function () {
            clearTimeout(safetyTimer);
            loader.style.opacity = '0';
            loader.style.pointerEvents = 'none';
            setTimeout(function () {
                if (loader.style.opacity === '0') {
                    loader.style.visibility = 'hidden';
                }
            }, 220);
        }
    };

    window.SiteLoader = SiteLoader;
    // Backward compatibility with TopLoader references
    window.TopLoader = {
        start: function () { SiteLoader.show(); },
        done: function () { SiteLoader.hide(); },
        set: function () {}
    };

    // Hide loader once the page is ready
    if (document.readyState === 'complete') {
        SiteLoader.hide();
    } else {
        window.addEventListener('DOMContentLoaded', function () {
            SiteLoader.hide();
        });
        window.addEventListener('load', function () {
            SiteLoader.hide();
        });
    }

    // Reset when restored via browser back/forward cache (bfcache)
    window.addEventListener('pageshow', function () {
        SiteLoader.hide();
    });

    window.addEventListener('popstate', function () {
        SiteLoader.hide();
    });

    // Provide immediate feedback on internal link clicks
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!link) return;

        // Skip modified clicks, new tabs, downloads, external links, anchor fragments
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;

        var href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

        try {
            var url = new URL(link.href, window.location.origin);
            if (url.origin === window.location.origin) {
                // Ignore same-page hash jump
                if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
                    return;
                }
                SiteLoader.show();
            }
        } catch (_) {}
    }, true);

    // Provide feedback on standard form submissions
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form && (!form.target || form.target === '_self')) {
            SiteLoader.show();
        }
    }, true);
})();
</script>
