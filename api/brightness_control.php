<?php
// brightness_control.php - Reusable brightness control for all pages
// This file should be included on every page

// Get current brightness value
$brightness = getGlobalBrightness();
?>

<style>
    /* ===== BRIGHTNESS SIDEBAR CONTROL - ALWAYS VISIBLE ===== */
    .brightness-sidebar-control {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        background: rgba(255, 170, 0, 0.08);
        border: 1px solid rgba(255, 170, 0, 0.25);
        border-radius: 10px;
        margin: 10px 15px;
        cursor: pointer !important;
        transition: all 0.3s ease;
        user-select: none;
    }
    
    .brightness-sidebar-control:hover {
        background: rgba(255, 170, 0, 0.15);
        border-color: #ffaa00;
        transform: scale(1.02);
    }
    
    .brightness-sidebar-control:active {
        transform: scale(0.97);
    }
    
    .brightness-sidebar-control i {
        color: #ffaa00;
        font-size: 1.2rem;
        width: 24px;
        text-align: center;
    }
    
    .brightness-sidebar-control .brightness-text {
        color: var(--text-secondary);
        font-size: 0.9rem;
        flex: 1;
    }
    
    .brightness-sidebar-control .brightness-value-display {
        font-family: 'Orbitron', sans-serif;
        font-size: 0.8rem;
        color: #ffaa00;
        background: rgba(255, 170, 0, 0.1);
        padding: 2px 10px;
        border-radius: 20px;
    }
    
    .brightness-sidebar-control .brightness-arrow {
        color: var(--text-muted);
        font-size: 0.7rem;
        transition: transform 0.3s ease;
    }
    
    .brightness-sidebar-control.active .brightness-arrow {
        transform: rotate(180deg);
    }

    /* ===== BRIGHTNESS PANEL ===== */
    .brightness-panel-sidebar {
        display: none;
        padding: 15px;
        margin: 0 15px 10px 15px;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        animation: slideDown 0.3s ease;
    }
    
    .brightness-panel-sidebar.active {
        display: block;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .brightness-panel-sidebar label {
        font-size: 0.75rem;
        color: var(--text-secondary);
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .brightness-panel-sidebar .brightness-slider {
        width: 100%;
        height: 6px;
        -webkit-appearance: none;
        appearance: none;
        background: linear-gradient(90deg, #3d4a5e, #ffaa00);
        border-radius: 3px;
        outline: none;
        cursor: pointer !important;
        margin: 8px 0;
    }
    
    .brightness-panel-sidebar .brightness-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ffaa00;
        cursor: pointer !important;
        border: 2px solid var(--bg-card);
        box-shadow: 0 2px 10px rgba(255, 170, 0, 0.4);
    }
    
    .brightness-panel-sidebar .brightness-slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ffaa00;
        cursor: pointer !important;
        border: 2px solid var(--bg-card);
    }
    
    .brightness-panel-sidebar .brightness-presets {
        display: flex;
        gap: 6px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    
    .brightness-panel-sidebar .brightness-preset {
        padding: 3px 12px;
        border-radius: 20px;
        background: var(--bg-stat);
        border: 1px solid var(--border-light);
        color: var(--text-secondary);
        font-size: 0.65rem;
        cursor: pointer !important;
        transition: all 0.2s;
        font-family: 'Rajdhani', sans-serif;
    }
    
    .brightness-panel-sidebar .brightness-preset:hover {
        border-color: #ffaa00;
        color: #ffaa00;
        background: rgba(255, 170, 0, 0.1);
    }
    
    .brightness-panel-sidebar .brightness-preset.active {
        border-color: #ffaa00;
        color: #ffaa00;
        background: rgba(255, 170, 0, 0.15);
    }
    
    .brightness-panel-sidebar .reset-btn {
        padding: 4px 12px;
        border-radius: 20px;
        background: rgba(255, 68, 68, 0.1);
        border: 1px solid rgba(255, 68, 68, 0.3);
        color: #ff6666;
        cursor: pointer !important;
        font-size: 0.65rem;
        transition: all 0.2s;
        font-family: 'Rajdhani', sans-serif;
        margin-top: 8px;
        width: 100%;
    }
    
    .brightness-panel-sidebar .reset-btn:hover {
        background: rgba(255, 68, 68, 0.2);
    }

    /* Keep brightness controls unaffected by the brightness filter */
    .brightness-sidebar-control,
    .brightness-panel-sidebar {
        filter: brightness(1) !important;
    }
</style>

<!-- Brightness Control HTML -->
<div class="brightness-sidebar-control" id="brightnessToggleSidebar">
    <i class="fas fa-sun"></i>
    <span class="brightness-text">Brightness</span>
    <span class="brightness-value-display" id="brightnessBadge"><?php echo $brightness; ?>%</span>
    <span class="brightness-arrow"><i class="fas fa-chevron-down"></i></span>
</div>

<!-- Brightness Panel -->
<div class="brightness-panel-sidebar" id="brightnessPanelSidebar">
    <label>
        <span>Adjust Brightness</span>
        <span id="brightnessValueSidebar"><?php echo $brightness; ?>%</span>
    </label>
    <input type="range" class="brightness-slider" id="brightnessSliderSidebar" min="50" max="150" value="<?php echo $brightness; ?>">
    
    <div class="brightness-presets">
        <button class="brightness-preset" data-value="60">Dim</button>
        <button class="brightness-preset <?php echo $brightness == 100 ? 'active' : ''; ?>" data-value="100">Normal</button>
        <button class="brightness-preset" data-value="130">Bright</button>
        <button class="brightness-preset" data-value="150">Extra</button>
    </div>
    
    <button class="reset-btn" id="resetBrightnessSidebar"><i class="fas fa-undo"></i> Reset to 100%</button>
</div>

<script>
    // ============================================
    // BRIGHTNESS CONTROL - WORKS ON ALL PAGES
    // ============================================
    
    (function() {
        // Get elements
        const brightnessToggle = document.getElementById('brightnessToggleSidebar');
        const brightnessPanel = document.getElementById('brightnessPanelSidebar');
        const brightnessSlider = document.getElementById('brightnessSliderSidebar');
        const brightnessValueDisplay = document.getElementById('brightnessValueSidebar');
        const brightnessBadge = document.getElementById('brightnessBadge');
        const resetBtn = document.getElementById('resetBrightnessSidebar');
        const presetBtns = document.querySelectorAll('.brightness-preset');

        // Skip if elements don't exist
        if (!brightnessToggle || !brightnessPanel) return;

        // Toggle panel
        brightnessToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            brightnessPanel.classList.toggle('active');
            this.classList.toggle('active');
        });

        // Update brightness function
        function updateBrightness(value) {
            // Update CSS variable
            document.documentElement.style.setProperty('--global-brightness', value + '%');
            
            // Update the page brightness
            document.body.style.filter = 'brightness(' + value + '%)';
            
            // Update display values if they exist
            if (brightnessValueDisplay) brightnessValueDisplay.textContent = value + '%';
            if (brightnessBadge) brightnessBadge.textContent = value + '%';
            
            // Update slider
            if (brightnessSlider) brightnessSlider.value = value;
            
            // Update preset buttons
            presetBtns.forEach(btn => {
                btn.classList.remove('active');
                if (parseInt(btn.dataset.value) === parseInt(value)) {
                    btn.classList.add('active');
                }
            });
            
            // Save to localStorage
            localStorage.setItem('brightness', value);
            
            // Save to database via AJAX
            fetch('brightness_control.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update_brightness&value=' + value
            }).catch(err => console.log('Brightness save error:', err));
        }

        // Load saved brightness
        function loadBrightness() {
            const saved = localStorage.getItem('brightness') || '<?php echo $brightness; ?>';
            updateBrightness(parseInt(saved));
        }

        // Load on page load
        loadBrightness();

        // Slider event
        if (brightnessSlider) {
            brightnessSlider.addEventListener('input', function() {
                updateBrightness(this.value);
            });
        }

        // Preset buttons
        presetBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                updateBrightness(this.dataset.value);
            });
        });

        // Reset button
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                updateBrightness(100);
            });
        }

        // Close panel when clicking outside
        document.addEventListener('click', function(e) {
            if (brightnessPanel.classList.contains('active')) {
                if (!brightnessPanel.contains(e.target) && 
                    e.target !== brightnessToggle && 
                    !brightnessToggle.contains(e.target)) {
                    brightnessPanel.classList.remove('active');
                    brightnessToggle.classList.remove('active');
                }
            }
        });
    })();
</script>