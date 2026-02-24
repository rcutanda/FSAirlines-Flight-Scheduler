        <div class="version-info" id="footerVersionInfo">
            <div class="version-title"><?php echo $lang['version']; ?> <?php echo VERSION; ?></div>
            <div class="credits">
                <?php echo $lang['footer_credits']; ?>
            </div>
        </div>
    </div> <!-- Close the main container div -->
<script>
    window.addEventListener('load', function() {

    // --- Advanced Options toggle (do not depend on app.js globals) ---
    try {
        const title = document.getElementById('advancedTitle');
        const content = document.getElementById('advancedContent');
        const toggle = document.getElementById('advancedToggle');

        if (title && content && toggle) {
            title.addEventListener('click', function () {
                const isOpen = content.classList.contains('show');
                if (isOpen) {
                    content.classList.remove('show');
                    toggle.innerHTML = '▼';
                } else {
                    content.classList.add('show');
                    toggle.innerHTML = '▲';
                }
            });
        }
    } catch (e) {
        // ignore
    }

    // Scroll behavior:
    // - If results/errors exist: scroll to them
    // - Else: default to top on reloads
    // - Footer only when explicitly requested via #footer
    // - Skip on Add/Edit aircraft screens
    try { if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; } } catch (e) {}

    const qs = new URLSearchParams(window.location.search);
    const isAircraftScreen = (qs.get('add_aircraft') === '1' || qs.get('edit_aircraft') === '1');
    const hash = window.location.hash;

    if (!isAircraftScreen) {
        const resultsEl = document.getElementById('resultsSection') || document.getElementById('resultsAnchor');

        if (resultsEl && resultsEl.scrollIntoView) {
            // Adjust this to change where the scroll "stops":
            // 0   = align results to very top
            // 80  = stop a bit higher (useful if you have a sticky header)
            // -80 = stop a bit lower (show more above the results)
            const RESULTS_SCROLL_OFFSET_PX = -800;

            resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

            // Apply offset after the browser finishes scrollIntoView
            setTimeout(function () {
                if (RESULTS_SCROLL_OFFSET_PX !== 0) {
                    window.scrollBy(0, -RESULTS_SCROLL_OFFSET_PX);
                }
            }, 300);
        } else if (hash === '#footer') {
            const targetEl = document.getElementById('footerVersionInfo');
            if (targetEl && targetEl.scrollIntoView) {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else {
            window.scrollTo(0, 0);
            setTimeout(function () { window.scrollTo(0, 0); }, 50);
        }
    }
	});
</script>
</body>
</html>