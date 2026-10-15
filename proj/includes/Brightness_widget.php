<?php
if (!isset($global_brightness)) {
    $global_brightness = function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100;
}
$global_brightness = (int) $global_brightness;
?>
<style>
    :root {
        --global-brightness: <?php echo $global_brightness; ?>%;
    }

    /* Apply brightness to the page wrapper instead of <body>.
       This keeps position:fixed working correctly for modals. */
    .page-wrapper {
        filter: brightness(var(--global-brightness));
        transition: filter 0.3s ease;
        min-height: 100vh;
    }

    /* Keep the widget itself readable no matter how dim/bright the page gets */
    .brightness-widget,
    .brightness-widget * {
        filter: brightness(1) !important;
    }

    .brightness-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        font-family: 'Rajdhani', sans-serif;
    }

    .brightness-widget-toggle {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        background: rgba(10, 20, 40, 0.95);
        border: 1px solid rgba(255, 170, 0, 0.4);
        border-radius: 50px;
        color: #ffaa00;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        user-select: none;
        transition: all 0.2s;
    }

    .brightness-widget-toggle:hover {
        background: rgba(255, 170, 0, 0.15);
        transform: scale(1.03);
    }

    .brightness-widget-toggle .bw-value {
        font-family: 'Orbitron', sans-serif;
        font-size: 0.75rem;
        background: rgba(255, 170, 0, 0.15);
        padding: 2px 8px;
        border-radius: 20px;
    }

    .brightness-widget-panel {
        display: none;
        position: absolute;
        bottom: 55px;
        right: 0;
        width: 220px;
        padding: 15px;
        background: rgba(5, 8, 18, 0.97);
        border: 1px solid rgba(255, 170, 0, 0.3);
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
    }

    .brightness-widget-panel.active {
        display: block;
    }

    .brightness-widget-panel label {
        font-size: 0.75rem;
        color: #8899cc;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .bw-slider {
        width: 100%;
        height: 6px;
        -webkit-appearance: none;
        appearance: none;
        background: linear-gradient(90deg, #3d4a5e, #ffaa00);
        border-radius: 3px;
        cursor: pointer;
        margin: 8px 0;
    }

    .bw-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #ffaa00;
        cursor: pointer;
        border: 2px solid #0a0f1e;
    }

    .bw-slider::-moz-range-thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #ffaa00;
        cursor: pointer;
        border: 2px solid #0a0f1e;
    }

    .bw-presets {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 8px;
    }

    .bw-preset {
        padding: 3px 10px;
        border-radius: 20px;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 170, 0, 0.15);
        color: #8899cc;
        font-size: 0.65rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .bw-preset.active,
    .bw-preset:hover {
        border-color: #ffaa00;
        color: #ffaa00;
        background: rgba(255, 170, 0, 0.1);
    }

    .bw-reset {
        width: 100%;
        margin-top: 8px;
        padding: 4px;
        border-radius: 20px;
        background: rgba(255, 68, 68, 0.1);
        border: 1px solid rgba(255, 68, 68, 0.3);
        color: #ff6666;
        font-size: 0.65rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .bw-reset:hover {
        background: rgba(255, 68, 68, 0.2);
    }

    @media (max-width: 480px) {
        .brightness-widget {
            bottom: 12px;
            right: 12px;
        }
    }
</style>

<div class="brightness-widget" id="brightnessWidget">
    <div class="brightness-widget-toggle" id="bwToggle">
        <i class="fas fa-sun"></i>
        <span class="bw-value" id="bwBadge"><?php echo $global_brightness; ?>%</span>
    </div>
    <div class="brightness-widget-panel" id="bwPanel">
        <label><span>Brightness</span><span id="bwValueText"><?php echo $global_brightness; ?>%</span></label>
        <input type="range" class="bw-slider" id="bwSlider" min="50" max="150" value="<?php echo $global_brightness; ?>">
        <div class="bw-presets">
            <button type="button" class="bw-preset" data-value="60">Dim</button>
            <button type="button" class="bw-preset" data-value="100">Normal</button>
            <button type="button" class="bw-preset" data-value="130">Bright</button>
            <button type="button" class="bw-preset" data-value="150">Extra</button>
        </div>
        <button type="button" class="bw-reset" id="bwReset"><i class="fas fa-undo"></i> Reset to 100%</button>
    </div>
</div>

<script>
(function() {
    const toggle = document.getElementById('bwToggle');
    const panel = document.getElementById('bwPanel');
    const slider = document.getElementById('bwSlider');
    const valueText = document.getElementById('bwValueText');
    const badge = document.getElementById('bwBadge');
    const resetBtn = document.getElementById('bwReset');
    const presets = document.querySelectorAll('.bw-preset');

    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        panel.classList.toggle('active');
    });

    function apply(value) {
        value = parseInt(value);

        // Apply brightness to a CSS variable on :root — NOT directly on body.
        // The .page-wrapper element consumes this variable, so <body> never
        // gets a filter, which keeps position:fixed working for modals.
        document.documentElement.style.setProperty('--global-brightness', value + '%');

        if (valueText) valueText.textContent = value + '%';
        if (badge) badge.textContent = value + '%';
        if (slider) slider.value = value;
        presets.forEach(function(btn) {
            btn.classList.toggle('active', parseInt(btn.dataset.value) === value);
        });
        localStorage.setItem('brightness', value);

        fetch('brightness_control.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=update_brightness&value=' + value
        }).catch(function(err) { console.log('Brightness save error:', err); });
    }

    const saved = localStorage.getItem('brightness') || '<?php echo $global_brightness; ?>';
    apply(saved);

    slider.addEventListener('input', function() { apply(this.value); });
    presets.forEach(function(btn) {
        btn.addEventListener('click', function() { apply(this.dataset.value); });
    });
    resetBtn.addEventListener('click', function() { apply(100); });

    document.addEventListener('click', function(e) {
        if (panel.classList.contains('active') &&
            !panel.contains(e.target) &&
            e.target !== toggle &&
            !toggle.contains(e.target)) {
            panel.classList.remove('active');
        }
    });
})();
</script>