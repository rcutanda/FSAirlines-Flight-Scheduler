<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="css/style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['page_title']; ?> <?php echo VERSION; ?></title>
<script>
function saveDeparturDefault() {
    const time = document.getElementById('local_departure_time').value;
    if (!time) {
        alert('Please select a time first');
        return;
    }
    const form = document.getElementById('mainForm');
    const formData = new FormData(form);
    formData.append('save_departure_default', '1');
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        body: formData
    }).then(() => {
        // Show notification instead of alert
        showSavedNotification();
    });
}
function saveArrivalDefault() {
    const time = document.getElementById('latest_arrival_time').value;
    if (!time) {
        alert('Please select a time first');
        return;
    }
    const form = document.getElementById('mainForm');
    const formData = new FormData(form);
    formData.append('save_arrival_default', '1');
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        body: formData
    }).then(() => {
        // Show notification instead of alert
        showSavedNotification();
    });
}
</script>
<script>
    function toggleCustomSpeed() {
        const aircraftSelect = document.getElementById('aircraft');
        const customFields = document.getElementById('customSpeedFields');
        const selectedAircraft = aircraftSelect ? aircraftSelect.value : '';
        if (selectedAircraft === 'custom') {
            customFields && customFields.classList.add('show');
        } else {
            customFields && customFields.classList.remove('show');
        }
        // Do not break the logic, so keep updateAltitudeForAircraft replacement
        const altitudeInput = document.getElementById('cruise_altitude');
        const customSpeedTypeSelect = document.getElementById('custom_speed_type');

        const aircraftData = <?php echo json_encode($aircraft_list); ?>;
        if (selectedAircraft === 'custom') {
            if (customSpeedTypeSelect && customSpeedTypeSelect.value === 'ktas') {
                altitudeInput.value = '24000';  // Fixed: Was console.log('36500');
            } else {
                altitudeInput.value = '35000';
            }
        } else if (aircraftData[selectedAircraft]) {
            altitudeInput.value = aircraftData[selectedAircraft]['altitude'];
        }
    }

    function toggleLatestArrivalTime() {
        const flightMode = document.getElementById('flight_mode') ? document.getElementById('flight_mode').value : 'charter';
        const latestArrivalInline = document.getElementById('latestArrivalInline');
        const hoursAfterRow = document.getElementById('hoursAfterRow');
        const minutesAfterRow = document.getElementById('minutesAfterRow');
        const minutesBeforeInput = document.getElementById('minutes_before_departure');
        const hoursAfterInput = document.getElementById('hours_after_departure');
        const minutesAfterInput = document.getElementById('minutes_after_departure');

        if (flightMode === 'daily_schedule') {
            if (latestArrivalInline) latestArrivalInline.style.display = 'block';
            if (hoursAfterRow) hoursAfterRow.style.display = 'none';
            if (minutesAfterRow) minutesAfterRow.classList.add('show');
            if (minutesBeforeInput) minutesBeforeInput.value = '30';
            if (hoursAfterInput) hoursAfterInput.value = '15';
            if (minutesAfterInput) minutesAfterInput.value = '30';
        } else {
            if (latestArrivalInline) latestArrivalInline.style.display = 'none';
            if (hoursAfterRow) hoursAfterRow.style.display = 'block';
            if (minutesAfterRow) minutesAfterRow.classList.remove('show');
        }
    }

    function updateAltitudeForAircraft() {
        const aircraftSelect = document.getElementById('aircraft');
        const altitudeInput = document.getElementById('cruise_altitude');
        const customSpeedTypeSelect = document.getElementById('custom_speed_type');

        const aircraftData = <?php echo json_encode($aircraft_list); ?>;
        const selectedAircraft = aircraftSelect ? aircraftSelect.value : '';
        if (selectedAircraft === 'custom') {
            if (customSpeedTypeSelect && customSpeedTypeSelect.value === 'ktas') {
                altitudeInput.value = '24000';
            } else {
                altitudeInput.value = '35000';
            }
        } else if (aircraftData[selectedAircraft]) {
            altitudeInput.value = aircraftData[selectedAircraft]['altitude'];
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
                // For Mach, climbSpeedInput remains 250 (as in updateSpeedTypeSelector)
            } else {
                // Use default Mach case
                climbSpeedInput.value = 250;
            }
        }
    }
</script>
</head>
<body>
    <div class="language-selector">
            <a href="?lang=es" class="<?php echo $current_language === 'es' ? 'active' : ''; ?>" title="<?php echo 'debug: ' . htmlspecialchars($current_language); ?>">
                <img src="languages/es.svg" alt="Español">
            </a>
            <a href="?lang=en" class="<?php echo $current_language === 'en' ? 'active' : ''; ?>" title="<?php echo 'debug: ' . htmlspecialchars($current_language); ?>">
                <img src="languages/gb.svg" alt="English">
            </a>
    </div>

    <div class="container">
        <h1><?php echo $lang['title']; ?></h1>
        <center><img src="favicon.png"></center>
        <p class="subtitle"><?php echo $lang['subtitle']; ?></p>

        <?php if (!empty($error)): ?>
            <div style="color: red; margin: 10px 0;">
                ⚠️ Error: <?php echo htmlspecialchars($error); ?>
                <?php if (strpos($error, 'not found in the database') !== false): ?>
                    <div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; color: #856404; font-size: 13px;">
                        <strong><?php echo $lang['note']; ?>:</strong> <?php echo $lang['fsa_login_note']; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
