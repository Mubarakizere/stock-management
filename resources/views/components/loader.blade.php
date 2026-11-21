{{-- ⚡ Executive Enterprise Site Loader --}}
{{-- Two-tier luxury architecture: Hairline Top Progress Shimmer + Frosted Glass Ambient Brand Modal --}}

{{-- 1. Hairline Top Progress Bar (Zero-latency instant feedback) --}}
<div id="site-top-progress"
     class="fixed top-0 left-0 right-0 h-[2.5px] z-[999999] pointer-events-none opacity-0 transition-opacity duration-200"
     aria-hidden="true">
    <div id="site-top-progress-bar"
         class="h-full w-0 bg-gradient-to-r from-indigo-500 via-teal-400 to-emerald-400 shadow-[0_0_12px_rgba(20,184,166,0.8)] transition-all duration-300 ease-out"></div>
</div>

{{-- 2. Frosted Ambient Brand Overlay --}}
<div id="site-full-loader"
     class="fixed inset-0 z-[999998] flex flex-col items-center justify-center bg-slate-950/40 dark:bg-slate-950/70 backdrop-blur-md select-none transition-all duration-250 ease-out opacity-0 pointer-events-none"
     aria-live="polite"
     aria-busy="false"
     role="status">

    <div class="relative flex flex-col items-center justify-center p-8 rounded-3xl bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800 shadow-2xl max-w-xs w-full text-center transform transition-transform duration-300 scale-95"
         id="site-loader-card">

        {{-- Ambient Glowing Orb Background --}}
        <div class="absolute -top-10 -left-10 w-24 h-24 bg-indigo-500/20 dark:bg-indigo-500/30 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-10 -right-10 w-24 h-24 bg-teal-500/20 dark:bg-teal-500/30 rounded-full blur-2xl pointer-events-none"></div>

        {{-- Brand Icon with Radiating Rings --}}
        <div class="relative mb-5 flex items-center justify-center">
            {{-- Outer Radiating Pulsing Halo --}}
            <span class="absolute w-16 h-16 rounded-2xl bg-teal-500/20 dark:bg-teal-400/20 animate-ping opacity-60"></span>
            
            {{-- Secondary Halo --}}
            <span class="absolute w-14 h-14 rounded-2xl bg-indigo-500/20 dark:bg-indigo-400/20 animate-pulse"></span>

            {{-- Center Badge --}}
            <div class="relative w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 via-teal-600 to-emerald-500 text-white shadow-lg shadow-teal-500/30 flex items-center justify-center">
                <svg class="w-6 h-6 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                    <path d="m3.3 7 8.7 5 8.7-5"/>
                    <path d="M12 22V12"/>
                </svg>
            </div>
        </div>

        {{-- Brand Title & Micro-Pill --}}
        <div class="space-y-1 mb-4">
            <h3 class="text-sm font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center justify-center gap-1.5">
                <span>{{ config('app.name', 'Nezerwa Plus') }}</span>
                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-teal-500/10 dark:bg-teal-400/10 text-teal-600 dark:text-teal-400 border border-teal-500/20">
                    ERP
                </span>
            </h3>
            <p id="site-loader-status-text" class="text-xs text-slate-500 dark:text-slate-400 font-medium tracking-wide">
                Synchronizing workspace...
            </p>
        </div>

        {{-- Hairline Progress Shimmer Bar --}}
        <div class="w-36 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden relative">
            <div class="absolute inset-0 h-full w-1/2 bg-gradient-to-r from-transparent via-teal-500 to-transparent animate-[shimmer_1.4s_infinite]"></div>
        </div>
    </div>
</div>

<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
}
</style>

<script>
(function () {
    var loader = document.getElementById('site-full-loader');
    var card = document.getElementById('site-loader-card');
    var statusText = document.getElementById('site-loader-status-text');
    var topProgress = document.getElementById('site-top-progress');
    var progressBar = document.getElementById('site-top-progress-bar');

    if (!loader) return;

    var overlayDelayTimer = null;
    var safetyTimer = null;
    var progressInterval = null;
    var currentProgress = 0;

    var SiteLoader = {
        /**
         * Start top progress bar immediately, and schedule frosted modal after brief delay
         */
        show: function (message) {
            clearTimeout(safetyTimer);
            clearTimeout(overlayDelayTimer);
            clearInterval(progressInterval);

            if (message && statusText) {
                statusText.textContent = message;
            } else if (statusText) {
                statusText.textContent = 'Synchronizing workspace...';
            }

            // 1. Trigger hairline top progress bar instantly
            if (topProgress && progressBar) {
                topProgress.style.opacity = '1';
                currentProgress = 15;
                progressBar.style.width = currentProgress + '%';

                progressInterval = setInterval(function () {
                    if (currentProgress < 85) {
                        currentProgress += Math.random() * 12;
                        if (currentProgress > 85) currentProgress = 85;
                        progressBar.style.width = currentProgress + '%';
                    }
                }, 180);
            }

            // 2. Schedule full frosted overlay only if navigation takes > 180ms
            overlayDelayTimer = setTimeout(function () {
                loader.style.pointerEvents = 'auto';
                loader.style.opacity = '1';
                if (card) {
                    card.style.transform = 'scale(1)';
                }
                loader.setAttribute('aria-busy', 'true');
            }, 180);

            // 3. Safety auto-dismiss fallback (max 5.5 seconds)
            safetyTimer = setTimeout(function () {
                SiteLoader.hide();
            }, 5500);
        },

        /**
         * Complete progress bar & smoothly fade out overlay
         */
        hide: function () {
            clearTimeout(safetyTimer);
            clearTimeout(overlayDelayTimer);
            clearInterval(progressInterval);

            // Finish top progress bar smoothly
            if (topProgress && progressBar) {
                progressBar.style.width = '100%';
                setTimeout(function () {
                    topProgress.style.opacity = '0';
                    setTimeout(function () {
                        progressBar.style.width = '0%';
                        currentProgress = 0;
                    }, 250);
                }, 180);
            }

            // Hide full-screen overlay
            loader.style.opacity = '0';
            loader.style.pointerEvents = 'none';
            if (card) {
                card.style.transform = 'scale(0.95)';
            }
            loader.setAttribute('aria-busy', 'false');
        },

        setText: function (text) {
            if (statusText && text) {
                statusText.textContent = text;
            }
        }
    };

    window.SiteLoader = SiteLoader;
    // Backward compatibility for existing modules
    window.TopLoader = {
        start: function (msg) { SiteLoader.show(msg); },
        done: function () { SiteLoader.hide(); },
        set: function () {}
    };

    // Auto-dismiss on page readiness
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

    // Provide intelligent feedback on internal links
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!link) return;

        // Skip modified clicks, new tabs, downloads, external links, anchor fragments
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download') || link.hasAttribute('data-no-loader')) return;

        var href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

        // Skip direct file download URLs like PDFs, CSVs, Excel exports
        var lowerHref = href.toLowerCase();
        if (lowerHref.includes('/export/') || lowerHref.endsWith('.pdf') || lowerHref.endsWith('.csv') || lowerHref.endsWith('.xlsx') || lowerHref.endsWith('.zip')) {
            return;
        }

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

    // Provide feedback on standard form submissions (exclude forms with data-no-loader or file downloads)
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form) return;
        if (form.hasAttribute('data-no-loader') || (form.target && form.target !== '_self')) return;

        var action = (form.getAttribute('action') || '').toLowerCase();
        if (action.includes('/export/') || action.endsWith('.csv') || action.endsWith('.pdf')) {
            return;
        }

        SiteLoader.show('Saving changes...');
    }, true);
})();
</script>
