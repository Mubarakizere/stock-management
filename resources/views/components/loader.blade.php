{{-- ✨ Nezerwa Plus — Branded Site Loader --}}
{{-- Visible by default (no display:none) so it covers content instantly --}}
<div
    id="site-loader"
    class="fixed inset-0 z-[9999] flex items-center justify-center"
    style="background: linear-gradient(135deg, #064e3b 0%, #059669 50%, #064e3b 100%);"
>
    <div class="flex flex-col items-center gap-6">

        {{-- Logo Icon --}}
        <div class="relative">
            <div class="w-20 h-20 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center shadow-lg shadow-emerald-900/30 animate-pulse">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            {{-- Glow ring --}}
            <div class="absolute -inset-2 rounded-2xl border-2 border-white/10 animate-ping" style="animation-duration: 2s;"></div>
        </div>

        {{-- Company Name --}}
        <div class="text-center">
            <h1 class="text-3xl font-bold text-white tracking-tight" style="text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                Nezerwa<span class="text-emerald-300">Plus</span>
            </h1>
            <p class="text-emerald-200/70 text-sm mt-1 font-medium tracking-wide">
                The premium solution for modern commerce
            </p>
        </div>

        {{-- Animated Progress Bar --}}
        <div class="w-48 h-1 bg-white/20 rounded-full overflow-hidden mt-2">
            <div class="h-full bg-white/80 rounded-full animate-loader-bar"></div>
        </div>
    </div>
</div>

{{-- Pure JS (no Alpine dependency) — runs immediately --}}
<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        var loader = document.getElementById('site-loader');
        if (loader) {
            loader.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            loader.style.opacity = '0';
            loader.style.transform = 'scale(0.97)';
            setTimeout(function () { loader.remove(); }, 500);
        }
    }, 800);
});
</script>

<style>
    @keyframes loader-bar {
        0%   { width: 0%; }
        50%  { width: 70%; }
        100% { width: 100%; }
    }
    .animate-loader-bar {
        animation: loader-bar 1s ease-in-out forwards;
    }
</style>
