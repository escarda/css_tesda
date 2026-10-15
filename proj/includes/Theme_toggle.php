<?php

?>
<style>
    .theme-toggle-btn {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(10, 20, 40, 0.95);
        border: 1px solid rgba(0, 255, 157, 0.4);
        color: #00ff9d;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        transition: all 0.3s ease;
        font-size: 1.1rem;
    }

    .theme-toggle-btn:hover {
        transform: scale(1.08);
        background: rgba(0, 255, 157, 0.15);
    }

    /* Re-skin the button itself once light mode is active */
    [data-theme="light"] .theme-toggle-btn {
        background: rgba(255, 255, 255, 0.95);
        border-color: rgba(0, 160, 100, 0.4);
        color: #00aa77;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 480px) {
        .theme-toggle-btn {
            top: 12px;
            right: 12px;
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
    }
</style>

<button type="button" class="theme-toggle-btn" id="themeToggleBtn"
        title="Toggle light / dark mode" aria-label="Toggle light and dark mode">
    <i class="fas fa-moon" id="themeToggleIcon"></i>
</button>

<script>
(function () {
    const btn = document.getElementById('themeToggleBtn');
    const icon = document.getElementById('themeToggleIcon');
    const root = document.documentElement;

    function syncIcon() {
        const isLight = root.getAttribute('data-theme') === 'light';
        icon.className = isLight ? 'fas fa-sun' : 'fas fa-moon';
        btn.title = isLight ? 'Switch to dark mode' : 'Switch to light mode';
    }

    syncIcon(); // reflect whatever theme_preload.php already applied

    btn.addEventListener('click', function () {
        const isLight = root.getAttribute('data-theme') === 'light';
        if (isLight) {
            root.removeAttribute('data-theme');
            localStorage.setItem('theme', 'dark');
        } else {
            root.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
        }
        syncIcon();
    });
})();
</script>