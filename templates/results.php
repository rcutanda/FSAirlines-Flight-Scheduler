            <div class="error-box" id="resultsSection">
                <strong>⚠️ <?php echo $lang['error']; ?>:</strong> <?php echo $error; ?>
                <div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; color: #856404; font-size: 13px;">
                    <strong><?php echo $lang['note']; ?>:</strong> <?php echo $lang['fsa_login_note']; ?>
                </div>
            </div>
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