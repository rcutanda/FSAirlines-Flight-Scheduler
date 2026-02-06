<form method="POST" action="" id="mainForm" onsubmit="if(event.submitter && event.submitter.classList.contains('button-next-leg')) { return false; }">
    <div class="form-row">
        <div class="form-group">
            <label for="icao_dep"><?php echo $lang['departure_icao']; ?>:</label>
            <input 
                type="text" 
                id="icao_dep" 
                name="icao_dep" 
                maxlength="4" 
                placeholder="<?php echo $lang['placeholder_departure']; ?>" 
                value="<?php echo htmlspecialchars($icao_dep); ?>"
                required 
                <?php echo $next_leg_dep ? '' : 'autofocus'; ?>
            >
        </div>
        
        <div class="form-group">
            <label for="icao_arr"><?php echo $lang['arrival_icao']; ?>:</label>
            <input 
                type="text" 
                id="icao_arr" 
                name="icao_arr" 
                maxlength="4" 
                placeholder="<?php echo $lang['placeholder_arrival']; ?>" 
                value="<?php echo (isset($_POST['icao_arr']) && !$next_leg_dep) ? htmlspecialchars($_POST['icao_arr']) : ''; ?>"
                required
                <?php echo $next_leg_dep ? 'autofocus' : ''; ?>
            >
        </div>
    </div>
    
<div class="form-row flight-row">
        <div class="form-group">
            <label for="local_departure_time"><?php echo $lang['local_departure_time']; ?> <span class="info-icon" title="<?php echo $lang['local_departure_time_help']; ?>" style="cursor: help;">i</span></label>
            <div>
                <input
					type="text"
					inputmode="numeric"
					placeholder="08:00"
					id="local_departure_time"
					name="local_departure_time"
					value="<?php echo htmlspecialchars($local_departure_time); ?>"
					style="font-size: 16px; padding: 15px 10px; height: 60px; width: 160px; box-sizing: border-box;"
					maxlength="5"
					oninput="this.value = this.value.replace(/[^0-9:]/g, '').replace(/^([0-9]{2})([0-9]+)/, '$1:$2').substring(0,5);"
				>
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 160px;" onclick="saveDeparturDefault()">💾 <?php echo $lang['save_default'] ?? 'Save default'; ?></button>
            </div>
        </div>

        <div class="form-group">
            <label for="flight_mode"><?php echo $lang['flight_mode']; ?>:</label>
            <select name="flight_mode" id="flight_mode" required style="font-size: 16px; padding: 15px; height: 60px; width: 180px;" onchange="toggleLatestArrivalTime()">
                <option value="charter" <?php echo (!isset($_POST['flight_mode']) || $_POST['flight_mode'] === 'charter') ? 'selected' : ''; ?>>
                    <?php echo $lang['charter_flight']; ?>
                </option>
                <option value="daily_schedule" <?php echo (isset($_POST['flight_mode']) && $_POST['flight_mode'] === 'daily_schedule') ? 'selected' : ''; ?>>
                    <?php echo $lang['daily_schedule']; ?>
                </option>
            </select>
        </div>

        <div class="form-group" id="latestArrivalInline" style="display: none;">
            <label for="latest_arrival_time"><?php echo $lang['latest_arrival_time']; ?>:<br></label>
            <div>
                <input
					type="text"
					inputmode="numeric"
					placeholder="23:55"
					id="latest_arrival_time"
					name="latest_arrival_time"
					value="<?php echo htmlspecialchars($latest_arrival_time); ?>"
					style="font-size: 16px; padding: 15px 10px; height: 60px; width: 160px; box-sizing: border-box;"
					maxlength="5"
					oninput="this.value = this.value.replace(/[^0-9:]/g, '').replace(/^([0-9]{2})([0-9]+)/, '$1:$2').substring(0,5);"
				>
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 160px;" onclick="saveArrivalDefault()">💾 <?php echo $lang['save_default'] ?? 'Save default'; ?></button>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="aircraft"><?php echo $lang['aircraft']; ?>:</label>
        <select name="aircraft" id="aircraft" onchange="toggleCustomSpeed(); updateAltitudeForAircraft()" required>
            <option value="custom" <?php echo (isset($_POST['aircraft']) && $_POST['aircraft'] === 'custom') || (!isset($_POST['aircraft']) && !isset($result)) ? 'selected' : ''; ?>><?php echo $lang['custom_speed']; ?></option>
            <?php foreach ($aircraft_list as $aircraft_name => $aircraft_data): ?>
                <option value="<?php echo htmlspecialchars($aircraft_name); ?>" <?php echo (isset($_POST['aircraft']) && $_POST['aircraft'] === $aircraft_name) || (!isset($_POST['aircraft']) && isset($result) && $result['aircraft'] === $aircraft_name) ? 'selected' : ''; ?>>
                    <?php 
                    echo htmlspecialchars($aircraft_name) . ' (';
                    if ($aircraft_data['type'] === 'mach') {
                        echo "Mach " . number_format($aircraft_data['speed'], 2);
                    } else {
                        echo $aircraft_data['speed'] . " kt";
                    }
                    echo ')';
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <div id="customSpeedFields" class="<?php echo (isset($_POST['aircraft']) && $_POST['aircraft'] === 'custom') ? 'show' : ''; ?>">
            <div class="custom-speed-group">
                <input 
                    type="number" 
                    id="custom_speed" 
                    name="custom_speed" 
                    step="0.01"
                    placeholder="<?php echo $lang['placeholder_custom_speed']; ?>" 
                    value="<?php echo isset($_POST['custom_speed']) ? htmlspecialchars($_POST['custom_speed']) : htmlspecialchars($saved_prefs['custom_speed_mach'] ?? $result['custom_speed'] ?? 0.8); ?>"
                    onchange="updateClimbSpeedForCustom()"
                >
                <select name="custom_speed_type" id="custom_speed_type" onchange="updateAltitudeForAircraft(); updateSpeedTypeSelector()">
                    <option value="mach" <?php echo (!isset($_POST['custom_speed_type']) || $_POST['custom_speed_type'] === 'mach') ? 'selected' : ''; ?>>Mach</option>
                    <option value="ktas" <?php echo (isset($_POST['custom_speed_type']) && $_POST['custom_speed_type'] === 'ktas') ? 'selected' : ''; ?>>KTAS</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="minutes_before_departure"><?php echo $lang['minutes_before_departure']; ?>:</label>
        <input 
            type="number" 
            id="minutes_before_departure" 
            name="minutes_before_departure" 
            min="0"
            placeholder="90" 
            value="<?php echo htmlspecialchars($minutes_before); ?>"
            required
        >
    </div>
    <div class="form-group" id="hoursAfterRow">
        <label for="hours_after_departure"><?php echo $lang['hours_after_departure']; ?>:</label>
        <input 
            type="number" 
            id="hours_after_departure" 
            name="hours_after_departure" 
            step="0.5"
            min="0.5"
            placeholder="15" 
            value="<?php echo htmlspecialchars($hours_after); ?>"
            required
        >
    </div>
    
    <div class="form-group minutes-after-row" id="minutesAfterRow">
        <label for="minutes_after_departure"><?php echo $lang['minutes_after_departure']; ?>:</label>
        <input 
            type="number" 
            id="minutes_after_departure" 
            name="minutes_after_departure" 
            min="0"
            placeholder="30" 
            value="<?php echo (isset($_POST['minutes_after_departure'])) ? htmlspecialchars($_POST['minutes_after_departure']) : '30'; ?>"
        >
    </div>

    <div class="help-text"><?php echo $lang['departure_randomized']; ?></div>
    
    <div class="advanced-options">
        <div class="advanced-title" onclick="toggleAdvanced()">
            ⚙️ <?php echo $lang['advanced_options']; ?> <span id="advancedToggle">▼</span>
        </div>
        <div class="advanced-content" id="advancedContent">
            <div class="form-group">
                <label for="cruise_altitude"><?php echo $lang['cruise_altitude']; ?> (<?php echo $lang['feet']; ?>):</label>
                <input 
                    type="number" 
                    id="cruise_altitude" 
                    name="cruise_altitude" 
                    step="100"
                    placeholder="<?php echo $lang['placeholder_cruise_altitude']; ?>" 
                    value="<?php echo htmlspecialchars($result['cruise_altitude'] ?? 35000); ?>"
                    required
                >
            </div>
                               
            <div class="form-group">
                <label for="climb_speed_knots"><?php echo $lang['climb_descent_speed']; ?> (<?php echo $lang['knots']; ?>):</label>
                <input 
                    type="number" 
                    id="climb_speed_knots" 
                    name="climb_speed_knots" 
                    min="1"
                    placeholder="250" 
                    value="<?php echo htmlspecialchars($climb_speed_knots); ?>"
                    required
                >
                <div class="help-text"><?php echo $lang['climb_speed_help']; ?></div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="buffer_time_vfr"><?php echo $lang['buffer_time_knots']; ?> (<?php echo $lang['minutes']; ?>):</label>
                    <input 
                        type="number" 
                        id="buffer_time_vfr" 
                        name="buffer_time_vfr" 
                        min="0"
                        placeholder="10" 
                        value="<?php echo htmlspecialchars($buffer_time_vfr); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="buffer_time_ifr"><?php echo $lang['buffer_time_mach']; ?> (<?php echo $lang['minutes']; ?>):</label>
                    <input 
                        type="number" 
                        id="buffer_time_ifr" 
                        name="buffer_time_ifr" 
                        min="0"
                        placeholder="30" 
                        value="<?php echo htmlspecialchars($buffer_time_ifr); ?>"
                        required
                    >
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="climb_rate_vfr"><?php echo $lang['climb_rate_knots']; ?> (<?php echo $lang['feet_per_minute']; ?>):</label>
                    <input 
                        type="number" 
                        id="climb_rate_vfr" 
                        name="climb_rate_vfr" 
                        min="1"
                        placeholder="800" 
                        value="<?php echo htmlspecialchars($climb_rate_vfr); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="climb_rate_ifr"><?php echo $lang['climb_rate_mach']; ?> (<?php echo $lang['feet_per_minute']; ?>):</label>
                    <input 
                        type="number" 
                        id="climb_rate_ifr" 
                        name="climb_rate_ifr" 
                        min="1"
                        placeholder="1800" 
                        value="<?php echo htmlspecialchars($climb_rate_ifr); ?>"
                        required
                    >
                </div>
            </div>
        </div>
    </div>
    
    <button type="submit"><?php echo $lang['calculate_times']; ?></button>
</form>
