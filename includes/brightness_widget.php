<?php
/*
|--------------------------------------------------------------------------
| CSS TESDA - GLOBAL BRIGHTNESS WIDGET
|--------------------------------------------------------------------------
| Brightness levels:
| 60%  = Dim
| 100% = Normal
| 125% = Bright
| 150% = Extra Bright
|--------------------------------------------------------------------------
*/

$brightness_value = isset($global_brightness)
    ? (int) $global_brightness
    : 100;

$brightness_value = max(60, min(150, $brightness_value));
?>

<style>
/* =========================================================
   BRIGHTNESS WIDGET
   ========================================================= */

#brightnessWidget {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 2147483000;
    font-family: 'Rajdhani', sans-serif;
}

/* Floating button */
#brightnessToggle {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    border: 2px solid #ffd000;
    background: rgba(5, 8, 18, 0.94);
    color: #ffd000;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 21px;
    box-shadow:
        0 0 10px rgba(255, 208, 0, 0.35),
        0 0 25px rgba(255, 208, 0, 0.15);
    transition: 0.25s ease;
}

#brightnessToggle:hover {
    transform: scale(1.08);
    box-shadow:
        0 0 15px rgba(255, 208, 0, 0.65),
        0 0 35px rgba(255, 208, 0, 0.25);
}

/* Panel */
#brightnessPanel {
    position: absolute;
    right: 0;
    bottom: 64px;
    width: 285px;
    padding: 18px;
    border: 1px solid rgba(255, 208, 0, 0.55);
    border-radius: 14px;
    background: rgba(5, 8, 18, 0.97);
    box-shadow:
        0 0 25px rgba(255, 208, 0, 0.18),
        0 15px 50px rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(14px);
    display: none;
}

#brightnessPanel.active {
    display: block;
}

.brightness-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.brightness-title span:first-child {
    color: #ffd000;
    font-family: 'Orbitron', sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1px;
}

#brightnessPercent {
    color: #fff;
    font-weight: 700;
    font-size: 14px;
}

/* Preset buttons */
.brightness-presets {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 15px;
}

.brightness-preset {
    border: 1px solid rgba(255, 208, 0, 0.35);
    background: rgba(255, 208, 0, 0.06);
    color: #ddd;
    border-radius: 8px;
    padding: 9px 5px;
    cursor: pointer;
    font-family: 'Rajdhani', sans-serif;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s ease;
}

.brightness-preset:hover,
.brightness-preset.active {
    color: #ffd000;
    border-color: #ffd000;
    background: rgba(255, 208, 0, 0.13);
    box-shadow: 0 0 10px rgba(255, 208, 0, 0.18);
}

/* Slider */
#brightnessSlider {
    width: 100%;
    height: 6px;
    appearance: none;
    -webkit-appearance: none;
    border-radius: 10px;
    outline: none;
    cursor: pointer;
    background: linear-gradient(
        to right,
        #ffd000 0%,
        #ffd000 40%,
        #555 40%,
        #555 100%
    );
}

#brightnessSlider::-webkit-slider-thumb {
    appearance: none;
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #ffd000;
    border: 2px solid #fff;
    box-shadow: 0 0 10px rgba(255, 208, 0, 0.65);
    cursor: pointer;
}

#brightnessSlider::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #ffd000;
    border: 2px solid #fff;
    box-shadow: 0 0 10px rgba(255, 208, 0, 0.65);
    cursor: pointer;
}

.brightness-range {
    display: flex;
    justify-content: space-between;
    margin-top: 7px;
    color: #8899aa;
    font-size: 11px;
}

#brightnessReset {
    width: 100%;
    margin-top: 14px;
    padding: 9px;
    border: 1px solid rgba(255, 208, 0, 0.4);
    border-radius: 8px;
    background: rgba(255, 208, 0, 0.06);
    color: #ffd000;
    cursor: pointer;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    transition: 0.2s ease;
}

#brightnessReset:hover {
    background: rgba(255, 208, 0, 0.14);
    box-shadow: 0 0 10px rgba(255, 208, 0, 0.2);
}

/* Dim overlay */
#brightnessOverlay {
    position: fixed;
    inset: 0;
    background: #000;
    opacity: 0;
    pointer-events: none;
    z-index: 2147482990;
    transition: opacity 0.15s ease;
}

/* Mobile */
@media (max-width: 600px) {

    #brightnessWidget {
        right: 14px;
        bottom: 14px;
    }

    #brightnessPanel {
        right: 0;
        bottom: 62px;
        width: 260px;
    }

    #brightnessToggle {
        width: 48px;
        height: 48px;
    }
}
</style>

<div id="brightnessOverlay"></div>

<div id="brightnessWidget">

    <div id="brightnessPanel">

        <div class="brightness-title">
            <span>
                <i class="fas fa-sun"></i>
                BRIGHTNESS
            </span>

            <span id="brightnessPercent">
                <?= $brightness_value ?>%
            </span>
        </div>

        <div class="brightness-presets">

            <button
                type="button"
                class="brightness-preset"
                data-value="60">
                <i class="fas fa-moon"></i>
                Dim
            </button>

            <button
                type="button"
                class="brightness-preset"
                data-value="100">
                <i class="fas fa-circle-half-stroke"></i>
                Normal
            </button>

            <button
                type="button"
                class="brightness-preset"
                data-value="125">
                <i class="fas fa-sun"></i>
                Bright
            </button>

            <button
                type="button"
                class="brightness-preset"
                data-value="150">
                <i class="fas fa-sun"></i>
                Extra Bright
            </button>

        </div>

        <input
            type="range"
            id="brightnessSlider"
            min="60"
            max="150"
            step="5"
            value="<?= $brightness_value ?>"
        >

        <div class="brightness-range">
            <span>60%</span>
            <span>100%</span>
            <span>125%</span>
            <span>150%</span>
        </div>

        <button type="button" id="brightnessReset">
            <i class="fas fa-rotate-left"></i>
            Reset to Normal
        </button>

    </div>

    <button
        type="button"
        id="brightnessToggle"
        title="Brightness">
        <i class="fas fa-sun"></i>
    </button>

</div>

<script>
(function () {

    const STORAGE_KEY = 'css_tesda_brightness';

    const toggle = document.getElementById('brightnessToggle');
    const panel = document.getElementById('brightnessPanel');
    const slider = document.getElementById('brightnessSlider');
    const percent = document.getElementById('brightnessPercent');
    const overlay = document.getElementById('brightnessOverlay');
    const reset = document.getElementById('brightnessReset');
    const presets = document.querySelectorAll('.brightness-preset');

    if (!toggle || !panel || !slider || !overlay) {
        return;
    }

    /* -----------------------------------------------------
       Convert brightness to overlay opacity

       60%  = strong dim
       100% = normal
       125% = bright
       150% = extra bright
       ----------------------------------------------------- */
    function applyBrightness(value) {

        value = Math.max(60, Math.min(150, Number(value)));

        slider.value = value;

        if (percent) {
            percent.textContent = value + '%';
        }

        /*
         * For values below 100%, use a black overlay.
         * At 100% there is no overlay.
         *
         * Values above 100% cannot physically make the
         * webpage brighter with an overlay, so we use
         * CSS brightness() for 100–150%.
         */

        if (value < 100) {

            document.documentElement.style.filter = 'none';
            document.body.style.filter = 'none';

            const opacity = (100 - value) / 100;

            overlay.style.opacity = opacity;

        } else {

            overlay.style.opacity = '0';

            const brightness = value / 100;

            document.documentElement.style.filter =
                'brightness(' + brightness + ')';

            document.body.style.filter = 'none';
        }

        /* Update slider visual */
        const sliderPercent =
            ((value - 60) / (150 - 60)) * 100;

        slider.style.background =
            'linear-gradient(to right, ' +
            '#ffd000 0%, ' +
            '#ffd000 ' + sliderPercent + '%, ' +
            '#555 ' + sliderPercent + '%, ' +
            '#555 100%)';

        /* Active preset */
        presets.forEach(function (button) {

            const buttonValue =
                Number(button.dataset.value);

            button.classList.toggle(
                'active',
                buttonValue === value
            );
        });

        localStorage.setItem(
            STORAGE_KEY,
            String(value)
        );
    }

    /* Toggle panel */
    toggle.addEventListener('click', function (event) {

        event.stopPropagation();

        panel.classList.toggle('active');

    });

    /* Prevent panel click from closing it */
    panel.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    /* Close panel outside */
    document.addEventListener('click', function () {
        panel.classList.remove('active');
    });

    /* Slider */
    slider.addEventListener('input', function () {
        applyBrightness(this.value);
    });

    /* Presets */
    presets.forEach(function (button) {

        button.addEventListener('click', function () {

            applyBrightness(
                Number(this.dataset.value)
            );

        });

    });

    /* Reset */
    reset.addEventListener('click', function () {

        applyBrightness(100);

    });

    /* -----------------------------------------------------
       Restore saved brightness
       ----------------------------------------------------- */

    let saved =
        localStorage.getItem(STORAGE_KEY);

    if (saved !== null) {

        saved = Number(saved);

        if (
            !isNaN(saved) &&
            saved >= 60 &&
            saved <= 150
        ) {
            applyBrightness(saved);
        } else {
            applyBrightness(<?= $brightness_value ?>);
        }

    } else {

        applyBrightness(<?= $brightness_value ?>);

    }

})();
</script>