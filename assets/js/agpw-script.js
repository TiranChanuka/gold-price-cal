document.addEventListener('DOMContentLoaded', function () {
    const priceElements = document.querySelectorAll('.agpw-price-buy, .agpw-price-sell, .agpw-grid .agpw-row strong, .agpw-ticker-item .price');

    // Store initial values
    priceElements.forEach(el => {
        // Try to parse the clean number from text (remove commas, spaces, "Buy:", "Sell:")
        let cleanText = el.textContent.replace(/[^\d.]/g, '');
        let val = parseFloat(cleanText);
        if (!isNaN(val)) {
            el.dataset.basePrice = val;
            el.dataset.originalText = el.textContent; // Keep label prefix if any
        }
    });

    function formatNumber(num) {
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Volatility Settings
    // Defaults: Range 2000, Interval 2000ms
    const range = (typeof agpw_config !== 'undefined' && agpw_config.volatility_range)
        ? parseFloat(agpw_config.volatility_range)
        : 2000;

    const intervalTime = (typeof agpw_config !== 'undefined' && agpw_config.volatility_interval)
        ? parseFloat(agpw_config.volatility_interval)
        : 2000;

    // Volatility Loop
    setInterval(() => {
        priceElements.forEach(el => {
            const base = parseFloat(el.dataset.basePrice);
            if (isNaN(base)) return;

            // Random fluctuation ± range
            const delta = (Math.random() * range * 2) - range;
            const newVal = base + delta;

            // Preserve text prefix (like "Buy: ")
            if (el.dataset.originalText && el.dataset.originalText.includes(':')) {
                const parts = el.dataset.originalText.split(':');
                el.textContent = parts[0] + ': ' + formatNumber(newVal);
            } else {
                el.textContent = formatNumber(newVal);
            }

            el.style.transition = 'color 0.5s';
        });
    }, intervalTime);

    // ====== Real-Time Clock ======
    const timeDisplay = document.getElementById('agpw-time-display');
    const dateDisplay = document.getElementById('agpw-date-display');

    if (timeDisplay && dateDisplay) {
        const timezone = (typeof agpw_config !== 'undefined' && agpw_config.timezone)
            ? agpw_config.timezone
            : 'Asia/Colombo';

        function updateClock() {
            const now = new Date();

            // Time Options: HH:mm (24-hour format)
            const timeOptions = {
                timeZone: timezone,
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            };

            // Date Options: DD-MM-YYYY
            const dateOptions = {
                timeZone: timezone,
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            };

            try {
                const timeFormatter = new Intl.DateTimeFormat('en-GB', timeOptions);
                const dateFormatter = new Intl.DateTimeFormat('en-GB', dateOptions); // en-GB uses DD/MM/YYYY order

                // Format: 12:34
                timeDisplay.textContent = timeFormatter.format(now);

                // Format: 29/12/2025 -> Change to 29-12-2025 if strictly matching image
                let dateStr = dateFormatter.format(now);
                dateDisplay.textContent = dateStr.replace(/\//g, '-');
            } catch (e) {
                console.error('Timezone error:', e);
            }
        }

        updateClock(); // Initial run
        setInterval(updateClock, 1000); // Create pulsating colon effect? Or just regular update. Regular is fine.
    }
});
