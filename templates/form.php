<form method="POST" action="" id="mainForm" onsubmit="return validateIcaoFields();">
    <?php
    $config_ok = defined('FSA_VA_ID') && defined('FSA_API_KEY') &&
                 FSA_VA_ID !== 'ADD_HERE_YOUR_AIRLINE_ID' &&
                 FSA_API_KEY !== 'ADD_HERE_YOUR_API_KEY';
    if (!$config_ok):
        $warning_text = $lang['config_warning'] ?? ($lang['missing_translation'] ?? '');
        echo '<div style="background: #ffcccc; border-left: 6px solid #cc0000; padding: 16px; margin-bottom: 20px; border-radius: 4px; font-size: 15px;">' .
             $warning_text .
             '</div>';
    endif;
    ?>
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
            value="<?php echo htmlspecialchars($form_icao_arr_value); ?>"
            <?php echo $next_leg_dep ? 'autofocus' : ''; ?>
        >
    </div>
</div>

<!-- Local_departure_time, flight_mode, latest_departure_time -->
<div class="form-row">
    <div class="form-group">
        <label for="local_departure_time"><?php echo $lang['local_departure_time']; ?> <span class="info-icon" title="<?php echo $lang['local_departure_time_help']; ?>" style="cursor: help;">i</span></label>
        <div style="display: inline-flex; flex-direction: column; gap: 8px;">
            <input
                type="text"
                inputmode="numeric"
                placeholder="08:00"
                id="local_departure_time"
                name="local_departure_time"
                value="<?php echo htmlspecialchars($local_departure_time); ?>"
                style="font-size: 16px; padding: 12px 15px; width: auto; min-width: 100px; box-sizing: border-box;"
                maxlength="5"
                oninput="this.value = this.value.replace(/[^0-9:]/g, '').replace(/^([0-9]{2})([0-9]+)/, '$1:$2').substring(0,5);"
            >
            <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveDepartureDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            <input type="hidden" id="local_departure_time_saved" name="local_departure_time_saved" value="<?php echo htmlspecialchars($_POST['local_departure_time_saved'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="flight_mode"><?php echo $lang['flight_mode']; ?>:</label>
        <select name="flight_mode" id="flight_mode" required style="font-size: 16px; padding: 15px; height: 60px; width: 180px;" onchange="toggleLatestArrivalTime(true)">
            <option value="charter" <?php echo ($form_flight_mode === 'charter') ? 'selected' : ''; ?>>
                <?php echo $lang['charter_flight']; ?>
            </option>
            <option value="daily_schedule" <?php echo ($form_flight_mode === 'daily_schedule') ? 'selected' : ''; ?>>
                <?php echo $lang['daily_schedule']; ?>
            </option>
        </select>

        <button type="button" class="button-secondary compact-btn" style="width: 180px; margin-top: 8px; padding: 10px 14px;" onclick="if (typeof window.persistFormPreferences === 'function') { window.persistFormPreferences(); } window.location.href='?';"><?php echo $lang['new_flight'] ?? ($lang['missing_translation'] ?? ''); ?></button>
    </div>

        <div class="form-group" id="latestArrivalInline" style="display: <?php echo ($form_flight_mode === 'daily_schedule') ? 'block' : 'none'; ?>; grid-column: 3;">

        <label for="latest_departure_time"><?php echo $lang['latest_departure_time']; ?>:</label>
        <div style="display: inline-flex; flex-direction: column; gap: 8px;">
            <input 
                type="text"
                inputmode="numeric"
                placeholder="23:00"
                id="latest_departure_time"
                name="latest_departure_time"
                value="<?php echo htmlspecialchars($latest_departure_time); ?>"
                style="font-size: 16px; padding: 12px 15px; width: auto; min-width: 100px; box-sizing: border-box;"
                maxlength="5"
                oninput="this.value = this.value.replace(/[^0-9:]/g, '').replace(/^([0-9]{2})([0-9]+)/, '$1:$2').substring(0,5);"
            >
            <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveArrivalDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
        </div>
    </div>
</div>
<!-- Minutes before and hours after (same row, two columns) -->
<div class="info-box"><?php echo $lang['departure_randomized']; ?></div>
<div class="form-row">
	<div class="form-group">
		<label for="minutes_before_departure"><?php echo $lang['minutes_before_departure']; ?>:</label>
		<div>
			<input 
				type="number" 
				id="minutes_before_departure" 
				name="minutes_before_departure" 
				min="0"
				placeholder="30" 
				value="<?php echo htmlspecialchars($minutes_before); ?>"
				required
				style="-moz-appearance: textfield;"
			>
			<input type="number" step="1" min="0" style="display:none;" />
		</div>
        <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveMinutesBeforeDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
	</div>
	<div class="form-group">
		<label for="local_departure_time"><?php echo $lang['hours_after_departure']; ?>: <span class="info-icon" title="<?php echo $lang['hours_after_departure_help']; ?>" style="cursor: help;">i</span></label>
		<div>
			<input 
				type="number" 
				id="hours_after_departure" 
				name="hours_after_departure" 
				min="0.5"
				step="0.5"
				placeholder="16" 
				value="<?php echo htmlspecialchars($hours_after); ?>"
				required
				style="-moz-appearance: textfield;"
			>
			<input type="number" step="1" min="0" style="display:none;" />
		</div>
        <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveHoursAfterDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
	</div>
</div>
<!-- Aircraft -->
<div class="form-row flight-row aircraft-selector">
	<div class="form-group">
		<label for="aircraft"><?php echo $lang['aircraft']; ?>:</label>
		<select name="aircraft" id="aircraft">
			<?php if (!empty($aircraft_list) && is_array($aircraft_list)): ?>
				<?php foreach ($aircraft_list as $name => $data): ?>
					<?php if ($name !== 'custom'): ?>
						<option value="<?php echo htmlspecialchars($name); ?>" <?php echo (!empty($form_aircraft) && $form_aircraft === $name) ? 'selected' : ''; ?>>
							<?php echo htmlspecialchars($name); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
        <div style="display:flex; gap:8px; margin-top:8px;">
		<button type="button"
				style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;"
				title="<?php echo $lang['add_aircraft']; ?>"
				onclick="window.location.href='?add_aircraft=1';">
			✈️ <?php echo $lang['add_aircraft']; ?>
		</button>

		<button type="button"
				style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;"
				title="<?php echo $lang['edit_aircraft']; ?>"
				onclick="window.location.href='?edit_aircraft=1';">
			✏️ <?php echo $lang['edit_aircraft']; ?>
		</button>

		<button type="button"
				style="padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;"
				onclick="saveAircraftDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
        </div>
	</div>
</div>

<div class="advanced-options">

    <div class="advanced-title" id="advancedTitle">
        ⚙️ <?php echo $lang['advanced_options']; ?> <span id="advancedToggle">▼</span>
    </div>
    <div class="advanced-content" id="advancedContent">
        <!-- Detours magnitude fields -->
		<div class="info-box"><?php echo $lang['interpolated_distance_increase']; ?></div>
                <!-- Row 1: Short + Medium -->
        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label for="short_haul"><?php echo $lang['short_haul']; ?>:</label>
                <input 
                    type="number" 
                    id="short_haul" 
                    name="short_haul" 
                    min="-99"
                    step="0.1"
                    value="<?php echo htmlspecialchars($short_haul); ?>"
                    required
                    style="text-align: right; min-width: 70px; max-width: 100px;"
                >
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveShortHaulDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
            <div class="form-group">
                <label for="medium_haul"><?php echo $lang['medium_haul']; ?>:</label>
                <input 
                    type="number" 
                    id="medium_haul" 
                    name="medium_haul" 
                    min="-99"
                    step="0.1"
                    value="<?php echo htmlspecialchars($medium_haul); ?>"
                    required
                    style="text-align: right; min-width: 70px; max-width: 100px;"
                >
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveMediumHaulDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
        </div>

        <!-- Row 2: Long + Ultra-long -->
        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label for="long_haul"><?php echo $lang['long_haul']; ?>:</label>
                <input 
                    type="number" 
                    id="long_haul" 
                    name="long_haul" 
                    min="-99"
                    step="0.1"
                    value="<?php echo htmlspecialchars($long_haul); ?>"
                    required
                    style="text-align: right; min-width: 70px; max-width: 100px;"
                >
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveLongHaulDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>

            <div class="form-group">
                <label for="ultra_long_haul"><?php echo $lang['ultra_long_haul']; ?>:</label>
                <input 
                    type="number" 
                    id="ultra_long_haul" 
                    name="ultra_long_haul" 
                    min="-99"
                    step="0.1"
                    value="<?php echo htmlspecialchars($ultra_long_haul); ?>"
                    required
                    style="text-align: right; min-width: 70px; max-width: 100px;"
                >
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveUltraLongHaulDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
        </div>

        <!-- CruiseRange-based detour correction (advanced) -->
        <div class="info-box"><?php echo $lang['cruise_range_correction_help'] ?? ($lang['missing_translation'] ?? ''); ?></div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label for="cruise_range_corr_enabled"><?php echo $lang['cruise_range_correction_enable'] ?? ($lang['missing_translation'] ?? ''); ?>:</label>
                <select id="cruise_range_corr_enabled" name="cruise_range_corr_enabled" style="width:auto; min-width: 180px;">
                    <option value="1" <?php echo ((string)$cruise_range_corr_enabled === '1') ? 'selected' : ''; ?>><?php echo $lang['enabled'] ?? ($lang['missing_translation'] ?? ''); ?></option>
                    <option value="0" <?php echo ((string)$cruise_range_corr_enabled !== '1') ? 'selected' : ''; ?>><?php echo $lang['disabled'] ?? ($lang['missing_translation'] ?? ''); ?></option>
                </select>
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveCruiseRangeFieldDefault('cruise_range_corr_enabled')">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
        </div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr;">
            <div class="form-group">
                <label for="cruise_range_thr1_nm"><?php echo $lang['cruise_range_thr1_nm'] ?? ($lang['missing_translation'] ?? ''); ?>:</label>
                <input type="number" id="cruise_range_thr1_nm" name="cruise_range_thr1_nm" min="0" step="1" value="<?php echo htmlspecialchars((string)$cruise_range_thr1_nm); ?>" required>
				<button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveCruiseRangeFieldDefault('cruise_range_thr1_nm')">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
            <div class="form-group">
                <label for="cruise_range_thr2_nm"><?php echo $lang['cruise_range_thr2_nm'] ?? ($lang['missing_translation'] ?? ''); ?>:</label>
                <input type="number" id="cruise_range_thr2_nm" name="cruise_range_thr2_nm" min="0" step="1" value="<?php echo htmlspecialchars((string)$cruise_range_thr2_nm); ?>" required>
				<button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveCruiseRangeFieldDefault('cruise_range_thr2_nm')">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
            <div class="form-group">
                <label for="cruise_range_thr3_nm"><?php echo $lang['cruise_range_thr3_nm'] ?? ($lang['missing_translation'] ?? ''); ?>:</label>
				<input type="number" id="cruise_range_thr3_nm" name="cruise_range_thr3_nm" min="0" step="1" value="<?php echo htmlspecialchars((string)$cruise_range_thr3_nm); ?>" required>
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveCruiseRangeFieldDefault('cruise_range_thr3_nm')">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
        </div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label for="cruise_range_pp_lt_thr1"><?php echo $lang['cruise_range_pp_lt_thr1'] ?? ($lang['missing_translation'] ?? ''); ?>:</label>
                <input type="number" id="cruise_range_pp_lt_thr1" name="cruise_range_pp_lt_thr1" step="0.1" value="<?php echo htmlspecialchars((string)$cruise_range_pp_lt_thr1); ?>" required style="text-align:right;">
				<button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveCruiseRangeFieldDefault('cruise_range_pp_lt_thr1')">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
            <div class="form-group">
                <label for="cruise_range_pp_thr1_thr2"><?php echo $lang['cruise_range_pp_thr1_thr2'] ?? ($lang['missing_translation'] ?? ''); ?>:</label>
                <input type="number" id="cruise_range_pp_thr1_thr2" name="cruise_range_pp_thr1_thr2" step="0.1" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr1_thr2); ?>" required style="text-align:right;">
				<button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveCruiseRangeFieldDefault('cruise_range_pp_thr1_thr2')">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
        </div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label for="cruise_range_pp_thr2_thr3"><?php echo $lang['cruise_range_pp_thr2_thr3'] ?? ($lang['missing_translation'] ?? ''); ?>:</label>
                <input type="number" id="cruise_range_pp_thr2_thr3" name="cruise_range_pp_thr2_thr3" step="0.1" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr2_thr3); ?>" required style="text-align:right;">
				<button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveCruiseRangeFieldDefault('cruise_range_pp_thr2_thr3')">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
            <div class="form-group">
                <label for="cruise_range_pp_ge_thr3"><?php echo $lang['cruise_range_pp_ge_thr3'] ?? ($lang['missing_translation'] ?? ''); ?>:</label>
                <input type="number" id="cruise_range_pp_ge_thr3" name="cruise_range_pp_ge_thr3" step="0.1" value="<?php echo htmlspecialchars((string)$cruise_range_pp_ge_thr3); ?>" required style="text-align:right;">
				<button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveCruiseRangeFieldDefault('cruise_range_pp_ge_thr3')">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
        </div>

		<div class="info-box"><?php echo $lang['buffer_time_help']; ?></div>
        <div class="form-row">
            <div class="form-group">
                <label for="buffer_time_knots"><?php echo $lang['buffer_time_knots']; ?> (<?php echo $lang['minutes']; ?>):</label>
                <input 
                    type="number" 
                    id="buffer_time_knots" 
                    name="buffer_time_knots" 
                    min="0"
                    placeholder="10" 
                    value="<?php echo htmlspecialchars($buffer_time_knots); ?>"
                    required
                >
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveBufferTimeKnotsDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>

            <div class="form-group">
                <label for="buffer_time_mach"><?php echo $lang['buffer_time_mach']; ?> (<?php echo $lang['minutes']; ?>):</label>
                <input 
                    type="number" 
                    id="buffer_time_mach" 
                    name="buffer_time_mach" 
                    min="0"
                    placeholder="30" 
                    value="<?php echo htmlspecialchars($buffer_time_mach); ?>"
                    required
                >
                <button type="button" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: 153px;" onclick="saveBufferTimeMachDefault()">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
        </div>
    </div>
</div>
        <button type="submit" id="calculateTimesBtn"><?php echo $lang['calculate_times']; ?></button>

</form>

<!-- Reset All Button - Outside main form -->
<br><center><button type="button" class="button-reset" onclick="resetAllValues()" style="width: auto; margin-top: 0;">🔃 <?php echo $lang['reset']; ?></button></center>

