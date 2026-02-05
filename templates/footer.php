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

    <div id="copiedNotification" class="copied-notification">
        ✓ <?php echo $lang['copied']; ?>
    </div>

    <script>
        function toggleAdvanced() {
            const content = document.getElementById('advancedContent');
            const toggle = document.getElementById('advancedToggle');
            
            if (content.classList.contains('show')) {
                content.classList.remove('show');
                toggle.textContent = '▼';
            } else {
                content.classList.add('show');
                toggle.textContent = '▲';
            }
        }
        
        function toggleCustomSpeed() {
            const aircraftSelect = document.getElementById('aircraft');
            const customFields = document.getElementById('customSpeedFields');
            
            if (aircraftSelect.value === 'custom') {
                customFields.classList.add('show');
            } else {
                customFields.classList.remove('show');
            }
        }
        
        function updateAltitudeForAircraft() {
            const aircraftSelect = document.getElementById('aircraft');
            const altitudeInput = document.getElementById('cruise_altitude');
            const customSpeedTypeSelect = document.getElementById('custom_speed_type');
            const selectedAircraft = aircraftSelect.value;
            
            const aircraftData = <?php echo json_encode($aircraft_list); ?>;
            
            if (selectedAircraft === 'custom') {
                if (customSpeedTypeSelect && customSpeedTypeSelect.value === 'ktas') {
                    altitudeInput.value = 24000;
                } else {
                    altitudeInput.value = 35000;
                }
            } else if (aircraftData[selectedAircraft]) {
                altitudeInput.value = aircraftData[selectedAircraft]['altitude'];
            }
        }
        
        function updateClimbSpeedForCustom() {
            const aircraftSelect = document.getElementById('aircraft');
            const customSpeedTypeSelect = document.getElementById('custom_speed_type');
            const customSpeedInput = document.getElementById('custom_speed');
            const climbSpeedInput = document.getElementById('climb_speed_knots');
            
            if (aircraftSelect.value === 'custom') {
                const customSpeed = parseFloat(customSpeedInput.value);
                if (!isNaN(customSpeed) && customSpeed > 0) {
                    if (customSpeedTypeSelect.value === 'ktas') {
                        climbSpeedInput.value = Math.round(customSpeed * 0.7);
                    }
                }
            }
        }

        function updateSpeedTypeSelector() {
            const customSpeedTypeSelect = document.getElementById('custom_speed_type');
            const customSpeedInput = document.getElementById('custom_speed');
            const climbSpeedInput = document.getElementById('climb_speed_knots');

            let saved_mach_speed = '<?php echo htmlspecialchars($saved_prefs['custom_speed_mach'] ?? '0.8'); ?>';
            let saved_ktas_speed = '<?php echo htmlspecialchars($saved_prefs['custom_speed_ktas'] ?? '250'); ?>';

            if (customSpeedTypeSelect.value === 'ktas') {
                customSpeedInput.value = saved_ktas_speed;
                climbSpeedInput.value = Math.round(parseFloat(saved_ktas_speed) * 0.7);
            } else if (customSpeedTypeSelect.value === 'mach') {
                customSpeedInput.value = saved_mach_speed;
                climbSpeedInput.value = 250;
            }
        }

        function copyToClipboard(text, element) {
            const textWithoutColon = text.replace(':', '');
            
            const textarea = document.createElement('textarea');
            textarea.value = textWithoutColon;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                showNotification();
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            
            document.body.removeChild(textarea);
        }
        
        function showNotification() {
            const notification = document.getElementById('copiedNotification');
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 2500);
        }
        
        function toggleLatestArrivalTime() {
            const flightMode = document.getElementById('flight_mode').value;
            const latestArrivalInline = document.getElementById('latestArrivalInline');
            const hoursAfterRow = document.getElementById('hoursAfterRow');
            const minutesAfterRow = document.getElementById('minutesAfterRow');
            const minutesBeforeInput = document.getElementById('minutes_before_departure');
            const hoursAfterInput = document.getElementById('hours_after_departure');
            const minutesAfterInput = document.getElementById('minutes_after_departure');
            
            if (flightMode === 'daily_schedule') {
                latestArrivalInline.style.display = 'block';
                hoursAfterRow.style.display = 'none';
                minutesAfterRow.classList.add('show');

                // Set default values for Daily Schedule mode - but NOT if it's already been set by next leg
				if ('<?php echo $is_next_leg ? 'true' : 'false'; ?>' !== 'true') {
					minutesBeforeInput.value = '30';
					minutesAfterInput.value = '30';
					// Only set local_departure_time if not next leg (let PHP handle next leg dynamically)
				}
            } else {
                latestArrivalInline.style.display = 'none';
                hoursAfterRow.style.display = 'block';
                minutesAfterRow.classList.remove('show');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            toggleCustomSpeed();
            updateAltitudeForAircraft();
            toggleLatestArrivalTime();
            if (document.getElementById('resultsSection')) {
                document.querySelector('.version-info').scrollIntoView({ behavior: 'smooth' });
            }
        });
    </script>

</body>
</html>
