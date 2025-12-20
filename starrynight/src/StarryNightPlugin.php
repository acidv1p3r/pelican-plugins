<?php

namespace JoanFo\StarryNight;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;

class StarryNightPlugin implements Plugin
{
    public function getId(): string
    {
        return 'starrynight';
    }

    public function register(Panel $panel): void
    {
        $source = __DIR__.'/../../css/starry-night.css';
        $destination = public_path('plugins/starrynight/css/starry-night.css');

        if (! File::exists($destination)) {
            $dir = dirname($destination);
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            File::copy($source, $destination);
        }

        $panel->colors([
            'danger' => Color::Rose,
            'gray' => Color::Slate,
            'info' => Color::Pink,
            'primary' => Color::Purple,
            'success' => Color::Emerald,
            'warning' => Color::Amber,
        ]);

        $panel->renderHook('panels::head.end', function () {
            return '<link rel="stylesheet" href="'.asset('plugins/starrynight/css/starry-night.css').'">';
        });

        $panel->renderHook('panels::head.end', function () {
                return <<<'JS'
    <script>
        try {
            localStorage.setItem('theme', 'dark');
            document.documentElement.style.setProperty('--default-theme-mode', 'dark');
            document.documentElement.classList.add('dark');
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: 'dark' }));
        } catch (e) {
        }
    </script>
    JS;
        });

        $panel->renderHook('panels::body.start', function () {
              $meteorHtml = '<section class="starrynight-meteors">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </section>';
            $meteorCss = <<<'CSS'
    <style>
    .starrynight-meteors { position: absolute; top: 0; left: 0; width: 100%; height: 100vh; pointer-events: none; z-index: 2; overflow: hidden; }
    .starrynight-meteors span { position: absolute; top: 50%; left: 50%; width: 4px; height: 4px; background: #fff; border-radius: 50%; box-shadow: 0 0 0 4px rgba(255,255,255,0.1),0 0 0 8px rgba(255,255,255,0.1),0 0 20px rgba(255,255,255,0.1); animation: animate 3s linear infinite; }
    .starrynight-meteors span::before { content: ""; position: absolute; top: 50%; transform: translateY(-50%); width: 300px; height: 1px; background: linear-gradient(90deg,#fff,transparent); }
    @keyframes animate { 0% { transform: rotate(315deg) translateX(0); opacity: 1; } 70% { opacity: 1; } 100% { transform: rotate(315deg) translateX(-1000px); opacity: 0; } }
    .starrynight-meteors span:nth-child(1) { top: 0; right: 0; left: initial; animation-delay: 0s; animation-duration: 1s; }
    .starrynight-meteors span:nth-child(2) { top: 0; right: 80px; left: initial; animation-delay: 0.2s; animation-duration: 3s; }
    .starrynight-meteors span:nth-child(3) { top: 80px; right: 0px; left: initial; animation-delay: 0.4s; animation-duration: 2s; }
    .starrynight-meteors span:nth-child(4) { top: 0; right: 180px; left: initial; animation-delay: 0.6s; animation-duration: 1.5s; }
    .starrynight-meteors span:nth-child(5) { top: 0; right: 400px; left: initial; animation-delay: 0.8s; animation-duration: 2.5s; }
    .starrynight-meteors span:nth-child(6) { top: 0; right: 600px; left: initial; animation-delay: 1s; animation-duration: 3s; }
    .starrynight-meteors span:nth-child(7) { top: 300px; right: 0px; left: initial; animation-delay: 1.2s; animation-duration: 1.75s; }
    .starrynight-meteors span:nth-child(8) { top: 0px; right: 700px; left: initial; animation-delay: 1.4s; animation-duration: 1.25s; }
    .starrynight-meteors span:nth-child(9) { top: 0px; right: 1000px; left: initial; animation-delay: 0.75s; animation-duration: 2.25s; }
    .starrynight-meteors span:nth-child(10) { top: 0px; right: 450px; left: initial; animation-delay: 2.75s; animation-duration: 2.75s; }
    </style>
    CSS;
            $starDiv = '<div id="starrynight-stars" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 3;"></div>';
            $starStyle = <<<'CSS'
<style>
.static-star, .flickering-star {
    transition: opacity 1.2s cubic-bezier(0.4,0,0.2,1), filter 1.2s cubic-bezier(0.4,0,0.2,1);
}
.static-star {
    opacity: 0.8;
    filter: brightness(1);
}
.flickering-star {
    opacity: 1;
    filter: brightness(1.7) drop-shadow(0 0 6px #fff);
}
</style>
CSS;
            $starJs = <<<'JS'
<script>
(function () {
    const totalStars = 400;
    const flickerCount = 80;

    function createContainerIfMissing() {
        let container = document.getElementById('starrynight-stars');
        if (!container) {
            container = document.createElement('div');
            container.id = 'starrynight-stars';
            container.style.position = 'fixed';
            container.style.top = '0';
            container.style.left = '0';
            container.style.width = '100%';
            container.style.height = '100%';
            container.style.pointerEvents = 'none';
            container.style.zIndex = '3';
            document.body.appendChild(container);
        }
        return container;
    }

    function initStars() {
        const container = createContainerIfMissing();

        if (container.dataset.ctInitialized === '1') return;

        container.innerHTML = '';

        const stars = [];
        const starPositions = [];
        function isFarEnough(top, left, minDist) {
            for (const pos of starPositions) {
                const dx = left - pos.left;
                const dy = top - pos.top;
                if (Math.sqrt(dx*dx + dy*dy) < minDist) return false;
            }
            return true;
        }
        let attempts = 0;
        for (let i = 0; i < totalStars; i++) {
            let top, left, size;
            do {
                top = 5 + Math.random() * 90;
                left = 5 + Math.random() * 90;
                size = 1 + Math.random() * 2;
                attempts++;
            } while (!isFarEnough(top, left, 2.5) && attempts < 1000);
            starPositions.push({top, left});
            const star = document.createElement('div');
            star.style.position = 'absolute';
            star.style.top = top + '%';
            star.style.left = left + '%';
            star.style.width = size + 'px';
            star.style.height = size + 'px';
            star.className = 'static-star';
            container.appendChild(star);
            stars.push(star);
        }

        function updateFlickeringStars() {
            if (!stars.length) return;
            stars.forEach(star => {
                star.classList.remove('flickering-star');
                star.classList.add('static-star');
            });
            const flickerIndices = new Set();
            while (flickerIndices.size < flickerCount) {
                flickerIndices.add(Math.floor(Math.random() * totalStars));
            }
            flickerIndices.forEach(idx => {
                const star = stars[idx];
                if (!star) return;
                star.classList.remove('static-star');
                star.classList.add('flickering-star');
                star.style.animationDelay = (Math.random() * 2).toFixed(1) + 's';
                star.style.animationDuration = (1.5 + Math.random() * 2).toFixed(1) + 's';
            });
        }

        if (window.__starrynight_stars_interval) {
            clearInterval(window.__starrynight_stars_interval);
        }
        updateFlickeringStars();
        window.__starrynight_stars_interval = setInterval(updateFlickeringStars, 3000);

        container.dataset.ctInitialized = '1';
    }

    window.StarryNight = window.StarryNight || {};
    window.StarryNight.initStars = initStars;

    function runInit() {
        try { initStars(); } catch (e) { console.error('StarryNight init error', e); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runInit);
    } else {
        runInit();
    }

    window.addEventListener('turbo:load', runInit);
    window.addEventListener('turbo:render', runInit);
    window.addEventListener('pjax:end', runInit);
    window.addEventListener('popstate', runInit);
    window.addEventListener('spa:navigate', runInit);

    const observer = new MutationObserver(() => {
        runInit();
    });
    observer.observe(document.documentElement || document.body, { childList: true, subtree: true });

})();
</script>
JS;

            return $meteorCss.$meteorHtml.$starDiv.$starStyle.$starJs;
        });
    }

    public function boot(Panel $panel): void {}
}
