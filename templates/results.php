<?php if ($error): ?>
    <div class="error-box" id="resultsSection">
        <strong>⚠️ <?php echo $lang['error']; ?>:</strong> <?php echo $error; ?>
        <div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; color: #856404; font-size: 13px;">
            <strong><?php echo $lang['note']; ?>:</strong> <?php echo $lang['fsa_login_note']; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($result): ?>
    <div class="result-box" id="resultsSection">
        <?php if ($result['new_day_warning']): ?>
            <div style="margin-bottom: 20px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; color: #856404; font-size: 13px;">
                <strong>⚠️ <?php echo $lang['note']; ?>:</strong> <?php echo $result['new_day_warning']; ?>
            </div>
        <?php endif; ?>
        <div class="airport-section">
            <div class="airport-title">✈️ <?php echo $lang['departure']; ?></div>
            <div class="info-line">
                <strong><?php echo $lang['icao']; ?>:</strong> <?php echo htmlspecialchars($result['dep_icao']); ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['name']; ?>:</strong> <?php echo htmlspecialchars($result['dep_name']); ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['coordinates']; ?>:</strong> <?php echo number_format($result['dep_lat'], 6); ?>, <?php echo number_format($result['dep_lon'], 6); ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['local_departure_time_reference']; ?>:</strong> <?php echo htmlspecialchars($result['local_departure_time']); ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['utc_departure_time_reference']; ?>:</strong> <?php echo htmlspecialchars($result['utc_departure_time']); ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['departure_range']; ?>:</strong>
                <?php echo $result['minutes_before_departure']; ?> <?php echo $lang['minutes_before']; ?> <?php echo $lang['to']; ?>
                <?php if ($result['flight_mode'] === 'daily_schedule'): ?>
                    <?php echo $result['minutes_after_departure']; ?> <?php echo $lang['minutes_after']; ?> <?php echo $lang['local_departure_text']; ?>
                <?php else: ?>
                    <?php echo number_format($result['hours_after_departure'], 1); ?> <?php echo $lang['hours_after']; ?> <?php echo $lang['local_departure_text']; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="airport-section">
            <div class="airport-title">🛬 <?php echo $lang['arrival']; ?></div>
            <div class="info-line">
                <strong><?php echo $lang['icao']; ?>:</strong> <?php echo htmlspecialchars($result['arr_icao']); ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['name']; ?>:</strong> <?php echo htmlspecialchars($result['arr_name']); ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['coordinates']; ?>:</strong> <?php echo number_format($result['arr_lat'], 6); ?>, <?php echo number_format($result['arr_lon'], 6); ?>
            </div>
        </div>
        
        <div class="flight-info">
            <div class="airport-title">📊 <?php echo $lang['flight_data']; ?> (<?php echo $result['flight_type']; ?>)</div>
            <div class="info-line">
                <strong><?php echo $lang['aircraft']; ?>:</strong> <?php echo $result['aircraft'] === 'custom' ? $lang['custom'] : htmlspecialchars($result['aircraft']); ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['distance']; ?>:</strong> <?php echo number_format($result['distance'], 1); ?> NM
            </div>
            <div class="info-line">
                <strong><?php echo $lang['cruise_speed']; ?>:</strong> 
                <?php 
                if ($result['speed_type'] === 'mach') {
                    echo "Mach " . number_format($result['cruise_speed'], 2) . " (" . number_format($result['cruise_speed_tas'], 0) . " KTAS)";
                } else {
                    echo number_format($result['cruise_speed'], 0) . " KTAS";
                }
                ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['cruise_altitude']; ?>:</strong> <?php echo number_format($result['cruise_altitude']); ?> <?php echo $lang['feet']; ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['climb_descent_speed']; ?>:</strong> <?php echo $result['climb_speed_knots']; ?> <?php echo $lang['knots']; ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['climb_descent_rate']; ?>:</strong> <?php echo number_format($result['climb_rate']); ?> <?php echo $lang['feet_per_minute']; ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['flight_time']; ?>:</strong> <?php echo number_format($result['flight_time'], 0); ?> <?php echo $lang['minutes']; ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['buffer_time']; ?>:</strong> <?php echo $result['buffer_time']; ?> <?php echo $lang['minutes']; ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['total_time']; ?>:</strong> <?php echo number_format($result['flight_time'] + $result['buffer_time'], 0); ?> <?php echo $lang['minutes']; ?> (<?php echo (int)floor(($result['flight_time'] + $result['buffer_time']) / 60); ?>h <?php echo (int)fmod(($result['flight_time'] + $result['buffer_time']), 60); ?>m)
            </div>
        </div>
        
        <div class="times-grid">
            <div class="time-display">
                <div class="time-label">🛫 <?php echo $lang['departure_icao']; ?>:</div>
                <div class="time-value" onclick="copyToClipboard('<?php echo $result['dep_icao']; ?>', this)"><?php echo $result['dep_icao']; ?></div>
            </div>
            
            <div class="time-display">
                <div class="time-label">🛬 <?php echo $lang['arrival_icao']; ?>:</div>
                <div class="time-value" onclick="copyToClipboard('<?php echo $result['arr_icao']; ?>', this)"><?php echo $result['arr_icao']; ?></div>
            </div>
        </div>
        
        <div class="times-grid">
            <div class="time-display">
                <div class="time-label">🛫 <?php echo $lang['departure_time']; ?>:</div>
                <div class="time-value" onclick="copyToClipboard('<?php echo $result['departure_time']; ?>', this)"><?php echo $result['departure_time']; ?></div>
            </div>
            
            <div class="time-display">
                <div class="time-label">🛬 <?php echo $lang['arrival_time']; ?>:</div>
                <div class="time-value" onclick="copyToClipboard('<?php echo $result['arrival_time']; ?>', this)"><?php echo $result['arrival_time']; ?></div>
            </div>
        </div>

        <?php if ($result['timezone_warning']): ?>
            <div style="margin-top: 30px; margin-bottom: 30px; padding: 20px; background: #ffdd57; border-left: 6px solid #ff8c00; border-radius: 8px; color: #000; font-size: 18px; line-height: 1.5;">
                <div style="font-size: 24px; margin-bottom: 10px;">⏰ ⚠️</div>
                <strong><?php echo $lang['timezone_api_warning_title']; ?>:</strong><br><?php echo $lang['timezone_api_warning_message']; ?>
            </div>
        <?php endif; ?>
                
        <div style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div style="padding: 20px; background: #f0f8ff; border-left: 4px solid #0066cc; border-radius: 5px; text-align: center;">
                <div style="font-size: 14px; color: #666; margin-bottom: 8px;"><?php echo $lang['actual_departure_time_local']; ?></div>
                <div style="font-size: 32px; font-weight: bold; color: #0066cc; letter-spacing: 2px;"><?php echo htmlspecialchars($result['local_departure_time_randomized']); ?></div>
            </div>
            <div style="padding: 20px; background: #f0f8ff; border-left: 4px solid #0066cc; border-radius: 5px; text-align: center;">
                <div style="font-size: 14px; color: #666; margin-bottom: 8px;"><?php echo $lang['actual_arrival_time_local']; ?></div>
                <div style="font-size: 32px; font-weight: bold; color: #0066cc; letter-spacing: 2px;"><?php echo htmlspecialchars($result['local_arrival_time']); ?></div>
            </div>
        </div>

        <?php if (($flight_mode === 'daily_schedule' || $is_next_leg) && !$result['new_day_triggered']): ?>
        <div class="turnaround-time-section show" id="turnaroundTimeSection">
            <div class="turnaround-time-form">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="turnaround_time_input"><?php echo $lang['turnaround_time']; ?> (<?php echo $lang['minutes']; ?>):</label>
                    <input 
                        type="number" 
                        id="turnaround_time_input" 
                        name="turnaround_time_input" 
                        min="1"
                        placeholder="<?php echo ($result['speed_type'] === 'mach') ? '60' : '40'; ?>" 
                        value="<?php echo ($result['speed_type'] === 'mach') ? htmlspecialchars($turnaround_time_mach) : htmlspecialchars($turnaround_time_knots); ?>"
                        style="font-size: 16px; padding: 15px; height: 60px;"
                    >
                </div>
            </div>
        </div>
        <?php elseif ($flight_mode === 'daily_schedule' && $result['new_day_triggered']): ?>
        <div style="margin-top: 30px; padding: 20px; background: #e8f4f8; border-left: 4px solid #0066cc; border-radius: 5px;">
            <div style="text-align: center; color: #0066cc; font-size: 14px;">
                <strong><?php echo $lang['note']; ?>:</strong> <?php echo $lang['new_day_text'] ?? 'Next day cycle starting. New randomisation will be applied.'; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="button-group" style="margin-top: 30px; position: relative; z-index: 100;">
            <?php if ($flight_mode === 'daily_schedule' && $result['new_day_triggered']): ?>
            <!-- New day triggered: reset to first-day logic -->
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="next_leg" value="1">
                <input type="hidden" name="new_day_flag" value="1">
                <input type="hidden" name="next_leg_dep" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">
                <input type="hidden" name="aircraft" value="<?php echo htmlspecialchars($result['aircraft']); ?>">
                <?php if ($result['aircraft'] === 'custom'): ?>
                    <input type="hidden" name="custom_speed" value="<?php echo htmlspecialchars($result['custom_speed']); ?>">
                    <input type="hidden" name="custom_speed_type" value="<?php echo htmlspecialchars($result['custom_speed_type']); ?>">
                <?php endif; ?>
                <input type="hidden" name="cruise_altitude" value="<?php echo htmlspecialchars($result['cruise_altitude']); ?>">
                <input type="hidden" name="local_departure_time" value="<?php echo htmlspecialchars($saved_prefs['local_departure_time']); ?>">
                <input type="hidden" name="minutes_before_departure" value="<?php echo htmlspecialchars($result['minutes_before_departure']); ?>">
                <input type="hidden" name="hours_after_departure" value="<?php echo htmlspecialchars($result['hours_after_departure']); ?>">
                <input type="hidden" name="minutes_after_departure" value="<?php echo (isset($_POST['minutes_after_departure'])) ? htmlspecialchars($_POST['minutes_after_departure']) : '30'; ?>">
                <input type="hidden" name="flight_mode" value="<?php echo htmlspecialchars($flight_mode); ?>">
                <input type="hidden" name="latest_arrival_time" value="<?php echo htmlspecialchars($latest_arrival_time); ?>">
                <input type="hidden" name="buffer_time_vfr" value="<?php echo htmlspecialchars($result['buffer_time_vfr']); ?>">
                <input type="hidden" name="buffer_time_ifr" value="<?php echo htmlspecialchars($result['buffer_time_ifr']); ?>">
                <input type="hidden" name="climb_rate_vfr" value="<?php echo htmlspecialchars($result['climb_rate_vfr']); ?>">
                <input type="hidden" name="climb_rate_ifr" value="<?php echo htmlspecialchars($result['climb_rate_ifr']); ?>">
                <input type="hidden" name="climb_speed_knots" value="<?php echo htmlspecialchars($result['climb_speed_knots']); ?>">
                <button type="submit" class="button-next-leg">✈️ <?php echo $lang['next_leg']; ?></button>
            </form>
            <?php else: ?>
            <!-- Normal next leg or charter flight -->
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="next_leg" value="1">
                <input type="hidden" name="next_leg_dep" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">
                <input type="hidden" name="aircraft" value="<?php echo htmlspecialchars($result['aircraft']); ?>">
                <?php if ($result['aircraft'] === 'custom'): ?>
                    <input type="hidden" name="custom_speed" value="<?php echo htmlspecialchars($result['custom_speed']); ?>">
                    <input type="hidden" name="custom_speed_type" value="<?php echo htmlspecialchars($result['custom_speed_type']); ?>">
                <?php endif; ?>
                <input type="hidden" name="cruise_altitude" value="<?php echo htmlspecialchars($result['cruise_altitude']); ?>">
                <input type="hidden" name="local_departure_time" value="<?php echo htmlspecialchars($result['local_departure_time']); ?>">
                <input type="hidden" name="minutes_before_departure" value="<?php echo htmlspecialchars($result['minutes_before_departure']); ?>">
                <input type="hidden" name="hours_after_departure" value="<?php echo htmlspecialchars($result['hours_after_departure']); ?>">
                <input type="hidden" name="minutes_after_departure" value="<?php echo (isset($_POST['minutes_after_departure'])) ? htmlspecialchars($_POST['minutes_after_departure']) : '30'; ?>">
                <input type="hidden" name="flight_mode" value="<?php echo htmlspecialchars($result['flight_mode']); ?>">
                <input type="hidden" name="latest_arrival_time" value="<?php echo htmlspecialchars($latest_arrival_time); ?>">
                <input type="hidden" name="next_leg_turnaround_time" value="<?php echo (isset($_POST['turnaround_time_input'])) ? htmlspecialchars($_POST['turnaround_time_input']) : (($result['speed_type'] === 'mach') ? $turnaround_time_mach : $turnaround_time_knots); ?>">
                <input type="hidden" name="next_leg_departure_time" value="<?php echo htmlspecialchars($result['arrival_time']); ?>">
                <input type="hidden" name="buffer_time_vfr" value="<?php echo htmlspecialchars($result['buffer_time_vfr']); ?>">
                <input type="hidden" name="buffer_time_ifr" value="<?php echo htmlspecialchars($result['buffer_time_ifr']); ?>">
                <input type="hidden" name="climb_rate_vfr" value="<?php echo htmlspecialchars($result['climb_rate_vfr']); ?>">
                <input type="hidden" name="climb_rate_ifr" value="<?php echo htmlspecialchars($result['climb_rate_ifr']); ?>">
                <input type="hidden" name="climb_speed_knots" value="<?php echo htmlspecialchars($result['climb_speed_knots']); ?>">
                <button type="submit" class="button-next-leg">✈️ <?php echo $lang['next_leg']; ?></button>
            </form>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="icao_dep" value="<?php echo htmlspecialchars($result['dep_icao']); ?>">
                <input type="hidden" name="icao_arr" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">
                <input type="hidden" name="aircraft" value="<?php echo htmlspecialchars($result['aircraft']); ?>">
                <?php if ($result['aircraft'] === 'custom'): ?>
                    <input type="hidden" name="custom_speed" value="<?php echo htmlspecialchars($result['custom_speed']); ?>">
                    <input type="hidden" name="custom_speed_type" value="<?php echo htmlspecialchars($result['custom_speed_type']); ?>">
                <?php endif; ?>
                <input type="hidden" name="cruise_altitude" value="<?php echo htmlspecialchars($result['cruise_altitude']); ?>">
                <input type="hidden" name="local_departure_time" value="<?php echo htmlspecialchars($result['local_departure_time']); ?>">
                <input type="hidden" name="minutes_before_departure" value="<?php echo htmlspecialchars($result['minutes_before_departure']); ?>">
                <input type="hidden" name="hours_after_departure" value="<?php echo htmlspecialchars($result['hours_after_departure']); ?>">
                <input type="hidden" name="minutes_after_departure" value="<?php echo htmlspecialchars($result['minutes_after_departure']); ?>">
                <input type="hidden" name="flight_mode" value="<?php echo htmlspecialchars($result['flight_mode']); ?>">
                <input type="hidden" name="latest_arrival_time" value="<?php echo htmlspecialchars($latest_arrival_time); ?>">
                <input type="hidden" name="buffer_time_vfr" value="<?php echo htmlspecialchars($result['buffer_time_vfr']); ?>">
                <input type="hidden" name="buffer_time_ifr" value="<?php echo htmlspecialchars($result['buffer_time_ifr']); ?>">
                <input type="hidden" name="climb_rate_vfr" value="<?php echo htmlspecialchars($result['climb_rate_vfr']); ?>">
                <input type="hidden" name="climb_rate_ifr" value="<?php echo htmlspecialchars($result['climb_rate_ifr']); ?>">
                <input type="hidden" name="climb_speed_knots" value="<?php echo htmlspecialchars($result['climb_speed_knots']); ?>">
                <button type="submit" class="button-secondary" style="pointer-events: auto;">🔄 <?php echo $lang['recalculate']; ?></button>
            </form>
            
            <form method="POST" action="">
                <input type="hidden" name="reset" value="1">
                <button type="submit" class="button-reset">🔃 <?php echo $lang['reset']; ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>
