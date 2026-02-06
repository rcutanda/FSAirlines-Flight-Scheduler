<style>
    /* Override any parent styles for this container close */
</style>

        <div class="version-info">
            <div class="version-title"><?php echo $lang['version']; ?> <?php echo VERSION; ?></div>
            <div class="credits">
                <strong>Ramón Cutanda</strong><br>
                <a href="https://github.com/rcutanda/FSAirlines-Flight-Scheduler" target="_blank">https://github.com/rcutanda/FSAirlines-Flight-Scheduler</a>
            </div>
        </div>
    </div> <!-- Close the main container div -->

<script>
    function showSavedNotification() {
    let notification = document.getElementById('savedNotification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'savedNotification';
        notification.innerHTML = '✓ <?php echo htmlspecialchars($lang['saved_default']); ?>';
        // Apply all styles inline to avoid CSS loading issues
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: #48bb78;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            z-index: 1000;
            pointer-events: none;
        `;
        document.body.appendChild(notification);
    }
    // Show: move to full visibility and position in center-top
    requestAnimationFrame(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(-50%) translateY(0)';
    });
    setTimeout(() => {
        // Hide: slide up out of view
        notification.style.opacity = '0';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300); // Match transition duration
    }, 2500);
}

    // ... (rest of the file, copiedNotification code remains unchanged)

    document.addEventListener('DOMContentLoaded', function() {
    toggleLatestArrivalTime();
    if (document.getElementById('resultsSection')) {
        document.querySelector('.version-info').scrollIntoView({ behavior: 'smooth' });
		}
	});
</script>
<script>
    window.onload = function() {
        // No notification div to reset anymore (it's dynamic)
    };
</script>
</body>
</html>
