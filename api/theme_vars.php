<?php
/*
 * includes/theme_vars.php
 *
 * Shared light/dark CSS variable palette for every "cyber theme" page in
 * the app (dashboard, quiz, and every simulator). Include this once in
 * <head>, anywhere - order relative to the page's own <style> block does
 * not matter, since CSS custom properties resolve at used-value time.
 *
 * The page's own CSS should reference these variables (var(--bg-primary),
 * var(--text-primary), etc.) for its CHROME - the page background, nav
 * bar, side panels, card backgrounds, and body text - instead of hardcoded
 * hex values. Deliberately left OUT of this palette: the brand accent
 * colors (#00ff9d green, #0066ff blue, #ffaa00 amber) and the simulated
 * "device screen" content areas inside each simulator (the fake monitor/
 * terminal output) - both are meant to stay visually constant regardless
 * of theme, the same way a real CRT screen doesn't change color scheme
 * with your OS's light/dark setting.
 */
?>
<style>
    :root {
        --bg-primary: #0a0f1e;
        --bg-secondary: #050812;
        --bg-card: rgba(10, 20, 40, 0.7);
        --bg-card-hover: rgba(10, 20, 40, 0.9);
        --bg-navbar: rgba(10, 20, 40, 0.9);
        --bg-panel: rgba(0, 0, 0, 0.4);
        --bg-stat: rgba(0, 0, 0, 0.3);
        --bg-input: rgba(0, 0, 0, 0.5);

        --text-primary: #e0e0ff;
        --text-secondary: #8899cc;
        --text-muted: #94a3b8;

        --border-color: rgba(0, 255, 157, 0.3);
        --border-light: rgba(0, 255, 157, 0.1);

        --shadow-color: rgba(0, 255, 157, 0.2);

        --cyber-bg1: #0a0f1e;
        --cyber-bg2: #050812;
        --grid-color: rgba(0, 255, 157, 0.03);

        --scrollbar-track: #1a1a2e;
        --scrollbar-thumb: #00ff9d;
    }

    [data-theme="light"] {
        --bg-primary: #d5dce6;
        --bg-secondary: #c8d0dd;
        --bg-card: rgba(255, 255, 255, 0.92);
        --bg-card-hover: rgba(255, 255, 255, 0.98);
        --bg-navbar: rgba(240, 244, 248, 0.95);
        --bg-panel: rgba(255, 255, 255, 0.6);
        --bg-stat: rgba(0, 0, 0, 0.04);
        --bg-input: rgba(255, 255, 255, 0.95);

        --text-primary: #1a2332;
        --text-secondary: #3d4a5e;
        --text-muted: #5a6b7e;

        --border-color: rgba(0, 160, 100, 0.4);
        --border-light: rgba(0, 160, 100, 0.2);

        --shadow-color: rgba(0, 160, 100, 0.15);

        --cyber-bg1: #d5dce6;
        --cyber-bg2: #c8d0dd;
        --grid-color: rgba(0, 160, 100, 0.06);

        --scrollbar-track: #c8d0dd;
        --scrollbar-thumb: #00aa77;
    }
</style>