<?php if ($error): ?>
    <div class="error-box" id="resultsSection">
        <a id="resultsAnchor"></a>
                <strong>⚠️ <?php echo $lang['error']; ?>:</strong> <?php
					$err = (string)$error;
					// If the error contains the known FSA CrewCenter link, allow <a> rendering (whitelist).
					if (strpos($err, 'fsairlines.net/crewcenter/index.php?icao=') !== false) {
						echo strip_tags($err, '<a><br><strong><b><code>');
					} else {
						echo nl2br(htmlspecialchars($err));
					}
				?>
        <?php if (strpos((string)$error, 'fsairlines.net/crewcenter/index.php?icao=') !== false): ?>
        <div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; color: #856404; font-size: 13px;">
            <strong><?php echo $lang['note']; ?>:</strong> <?php echo $lang['fsa_login_note']; ?>
        </div>
        <script>
            (function () {
                try {
                    var el = document.getElementById('resultsSection') || document.getElementById('resultsAnchor');
                    if (el && el.scrollIntoView) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        window.location.hash = 'resultsAnchor';
                    }
                } catch (e) {}
            })();
        </script>
<?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($result): ?>
    <div class="result-box" id="resultsSection">

        <?php if (!empty($result['manual_timezone_required']) && !empty($result['manual_timezone_context']) && is_array($result['manual_timezone_context'])): ?>
            <?php
                $ctx = $result['manual_timezone_context'];
                $heading = (string)($ctx['heading'] ?? 'Unknown location');
                $time_is_url = (string)($ctx['time_is_url'] ?? 'https://time.is/#time_zone');
                $time_24tz_url = (string)($ctx['time_24tz_url'] ?? 'https://24timezones.com/');
                $manual_for = (string)($ctx['manual_timezone_for'] ?? 'dep');
                $prefill = (string)($ctx['prefill_timezone'] ?? '');
            ?>

            <?php
                $form = "<form id='manualTimezoneForm' method='post' action='" . htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? '')) . "'>"
                      . "<input type='hidden' name='manual_timezone_for' value='" . htmlspecialchars($manual_for) . "'>";

                foreach ((array)$_POST as $k => $v) {
                    if ($k === 'manual_timezone' || $k === 'manual_timezone_for') continue;
                    if (is_array($v)) continue;
                    $form .= "<input type='hidden' name='" . htmlspecialchars((string)$k) . "' value='" . htmlspecialchars((string)$v) . "'>";
                }

                $form .= "</form>";

                echo $form;
            ?>

            <div id='manualTimezoneModalBackdrop' class='manual-tz-backdrop'>
                <div class='manual-tz-modal'>
                    <div class='manual-tz-header'>
                        <div class='manual-tz-title'>⚠️ <?php echo htmlspecialchars((string)($lang['note'] ?? 'NOTE')); ?></div>
                    </div>

                    <div class='manual-tz-body'>
                        <p style="margin-top:0;">
                            <?php echo htmlspecialchars((string)($lang['timezone_lookup_failed_message'] ?? '')); ?>
                        </p>

                        <h3 class='manual-tz-location'><?php echo htmlspecialchars($heading); ?></h3>

                        <div class='manual-tz-link'>
                            <a href="<?php echo htmlspecialchars($time_is_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars($time_is_url); ?>
                            </a>
                        </div>

                        <div class='manual-tz-link'>
                            <a href="<?php echo htmlspecialchars($time_24tz_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars($time_24tz_url); ?>
                            </a>
                        </div>

                        <div class='manual-tz-label'>
                            <?php echo htmlspecialchars((string)($lang['timezone'] ?? 'Timezone')); ?>
                        </div>

                        <select id="manual_timezone_select" class="manual-tz-select">
                            <?php
                                $tz_list = timezone_identifiers_list();
                                foreach ($tz_list as $tz_name) {
                                    $sel = ($prefill !== '' && $tz_name === $prefill) ? ' selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($tz_name) . "\"" . $sel . ">" . htmlspecialchars($tz_name) . "</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div class='manual-tz-actions'>
                        <button type='button' id='manualTimezoneCancelBtn' class='manual-tz-btn manual-tz-btn-cancel'>
                            <?php echo htmlspecialchars((string)($lang['cancel'] ?? 'Cancel')); ?>
                        </button>
                        <button type='button' id='manualTimezoneAcceptBtn' class='manual-tz-btn manual-tz-btn-accept'>
                            <?php echo htmlspecialchars((string)($lang['accept'] ?? 'Accept')); ?>
                        </button>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    var acceptBtn = document.getElementById('manualTimezoneAcceptBtn');
                    var cancelBtn = document.getElementById('manualTimezoneCancelBtn');
                    var backdrop  = document.getElementById('manualTimezoneModalBackdrop');
                    var form      = document.getElementById('manualTimezoneForm');
                    var sel       = document.getElementById('manual_timezone_select');

                    function closeModal() {
                        if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                    }

                    if (acceptBtn) {
                        acceptBtn.addEventListener('click', function () {
                            if (form && sel) {
                                var input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'manual_timezone';
                                input.value = sel.value || '';
                                form.appendChild(input);
                                form.submit();
                            }
                        });
                    }

                    if (cancelBtn) {
                        cancelBtn.addEventListener('click', function () {
                            closeModal();
                            window.location.href = '?jump=top';
                        });
                    }

                    if (backdrop) {
                        backdrop.addEventListener('click', function (e) {
                            if (e && e.target === backdrop) {
                                closeModal();
                                window.location.href = '?jump=top';
                            }
                        });
                    }

                    document.addEventListener('keydown', function (e) {
                        if (e && e.key === 'Escape') {
                            closeModal();
                            window.location.href = '?jump=top';
                        }
                    });
                })();
            </script>

            <?php return; ?>
        <?php endif; ?>
        <?php if ($result['new_day_warning']): ?>
            <div style="margin-bottom: 20px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; color: #856404; font-size: 13px;">
                <strong>⚠️ <?php echo $lang['note']; ?>:</strong> <?php echo nl2br(htmlspecialchars((string)$result['new_day_warning'])); ?>
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
                <?php echo number_format($result['hours_after_departure'], 1); ?> <?php echo $lang['hours_after']; ?> <?php echo $lang['local_departure_text']; ?>
            </div>
        </div>
        
        <div id="scrollDown" class="airport-section">
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
            <div class="airport-title">📊 <?php echo $lang['flight_data']; ?></div>
            <div class="info-line">
                <strong><?php echo $lang['aircraft']; ?>:</strong> <?php echo htmlspecialchars($result['aircraft']); ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['distance']; ?>:</strong>
			<?php
				// Display keeps formatting + unit, but copy is rounded integer digits only (no separators, no unit)
				$distance_display = number_format((float)$result['distance'], 1) . ' NM';
				$distance_copy = (string)(int)round((float)$result['distance'], 0);
			?>
			<span onclick='copyToClipboard(<?php echo json_encode((string)$distance_copy); ?>, this)'
				  style="cursor:pointer; font: inherit; font-size: inherit; font-weight: inherit; color: inherit;">
				<?php echo htmlspecialchars($distance_display); ?>
			</span>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['extra_distance_title']; ?>:</strong> <?php 
                    $extra_dist = number_format($result['extra_distance'], 1);
                    $percent = number_format($result['distance_percentage'], 1);
                    echo strtr($lang['extra_distance_format'], ['%1' => $extra_dist, '%2' => $percent]);

                    // If CruiseRange correction is enabled and applied, show it directly under Extra distance added
                    $pp = (float)($result['cruise_range_pp_applied'] ?? 0.0);
                    $corrEnabled = ((string)($cruise_range_corr_enabled ?? '0') === '1');
                    if ($corrEnabled && abs($pp) > 0.0001) {
                        $pp_txt = ($pp >= 0) ? ('+' . number_format($pp, 1)) : number_format($pp, 1);

                        $rangeNm = $result['cruise_range_nm_used'] ?? null;
                        $rangeTxt = (is_numeric($rangeNm)) ? number_format((float)$rangeNm, 0) : null;

                        echo "<br>";
                        echo "<strong>" . htmlspecialchars((string)($lang['cruise_range_correction_applied'] ?? ($lang['missing_translation'] ?? ''))) . ":</strong> ";
                        if ($rangeTxt !== null) {
                            echo $pp_txt . " pp (" . $rangeTxt . " NM)";
                        } else {
                            echo $pp_txt . " pp";
                        }
                    }
                ?>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['cruise_speed']; ?>:</strong> 
                <?php 
                if ($result['speed_type'] === 'mach') {
                    echo "Mach " . number_format($result['cruise_speed'], 2) . " (" . 
                         number_format($result['cruise_speed_tas'], 0) . " KTAS)";
                } else {
                    echo number_format($result['cruise_speed'], 0) . " KTAS";
                }
                ?>
            </div>

            <div class="info-line">
                <strong><?php echo $lang['wind_component']; ?>:</strong>
                <?php
                    $w = (float)($result['mean_tailwind_kt'] ?? 0.0);
                    $absw_int = (int)round(abs($w), 0);

                    // Display keeps original wording/signs, copy is just the figure:
                    // Headwind -> "-44"
                    // Tailwind -> "39" (no plus)
                    if ($w >= 0) {
                        $wind_display = $lang['tailwind'] . ' +' . number_format($absw_int, 0) . ' ' . $lang['knots'];
                        $wind_copy = (string)$absw_int;
                    } else {
                        $wind_display = $lang['headwind'] . ' -' . number_format($absw_int, 0) . ' ' . $lang['knots'];
                        $wind_copy = '-' . (string)$absw_int;
                    }
                ?>
                <span onclick='copyToClipboard(<?php echo json_encode((string)$wind_copy); ?>, this)'
                      style="cursor:pointer; font: inherit; font-size: inherit; font-weight: inherit; color: inherit;">
                    <?php echo htmlspecialchars($wind_display); ?>
                </span>
            </div>
            <div class="info-line">
                <strong><?php echo $lang['cruise_altitude']; ?>:</strong> <?php echo number_format($result['cruise_altitude']); ?> <?php echo $lang['feet']; ?>
            </div>
			<div class="info-line">
				<strong><?php echo $lang['flight_time']; ?>:</strong> 
				<?php 
				$flight_hours = (int)floor($result['flight_time'] / 60);
				$flight_mins = (int)fmod($result['flight_time'], 60);
				echo $flight_hours . 'h ' . $flight_mins . 'm';
				?>
			</div>
			<div class="info-line">
				<strong><?php echo $lang['buffer_time']; ?>:</strong> <?php echo $result['buffer_time']; ?> <?php echo $lang['minutes']; ?>
			</div>
			<div class="info-line">
				<strong><?php echo $lang['total_time']; ?>:</strong> 
				<?php 
				$total_minutes = $result['flight_time'] + $result['buffer_time'];
				$total_hours = (int)floor($total_minutes / 60);
				$total_mins = (int)fmod($total_minutes, 60);
				$total_time_str = $total_hours . 'h ' . $total_mins . 'm';
				?>
				<span onclick='copyToClipboard(<?php echo json_encode((string)$total_time_str); ?>, this)'
					  style="cursor:pointer; font: inherit; font-size: inherit; font-weight: inherit; color: inherit;">
					<?php echo htmlspecialchars($total_time_str); ?>
				</span>
			</div>
        </div>
        
        <div class="times-grid">
            <div class="time-display">
                <div class="time-label">🛫 <?php echo $lang['departure_icao']; ?>:</div>
                <div class="time-value" onclick='copyToClipboard(<?php echo json_encode((string)$result["dep_icao"]); ?>, this)'><?php echo $result['dep_icao']; ?></div>
            </div>
            
            <div class="time-display">
                <div class="time-label">🛬 <?php echo $lang['arrival_icao']; ?>:</div>
                <div class="time-value" onclick='copyToClipboard(<?php echo json_encode((string)$result["arr_icao"]); ?>, this)'><?php echo $result['arr_icao']; ?></div>
            </div>
        </div>
        
        <div class="times-grid">
            <div class="time-display">
                <div class="time-label">🛫 <?php echo $lang['departure_time']; ?>:</div>
                <div class="time-value" onclick='copyToClipboard(<?php echo json_encode((string)$result["departure_time"]); ?>, this)'><?php echo $result['departure_time']; ?></div>
            </div>
            
            <div class="time-display">
                <div class="time-label">🛬 <?php echo $lang['arrival_time']; ?>:</div>
                <div class="time-value" onclick='copyToClipboard(<?php echo json_encode((string)$result["arrival_time"]); ?>, this)'><?php echo $result['arrival_time']; ?></div>
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

<?php
    $default_local_dep_time = $ui_default_local_dep_time;
?>

        <?php if (($flight_mode === 'daily_schedule' || $is_next_leg) && !$result['new_day_triggered']): ?>
        <div class="turnaround-time-section show" id="turnaroundTimeSection">
            <div class="turnaround-time-form">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="turnaround_time_input"><?php echo $lang['turnaround_time']; ?></label>
            <div>
                <input 
                    type="number" 
                    id="turnaround_time_input" 
                    name="turnaround_time_input" 
                    min="1"
                    placeholder="<?php echo ($result['speed_type'] === 'mach') ? '60' : '40'; ?>" 
                    value="<?php echo htmlspecialchars($vm_turnaround_time !== '' ? $vm_turnaround_time : (($result['speed_type'] === 'mach') ? $turnaround_time_mach : $turnaround_time_knots)); ?>"
                    style="font-size: 16px; padding: 15px 10px; height: 60px; box-sizing: border-box; width: auto; min-width: 100px;"
                >
                <button type="button" onclick="saveTurnaroundDefault()" style="margin-top: 8px; padding: 8px 12px; font-size: 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; width: auto; min-width: 120px;">💾 <?php echo $lang['save_default'] ?? ($lang['missing_translation'] ?? ''); ?></button>
            </div>
        </div>
            </div>
        </div>
        <?php elseif ($flight_mode === 'daily_schedule' && $result['new_day_triggered']): ?>
        <div style="margin-top: 30px; padding: 20px; background: #e8f4f8; border-left: 4px solid #0066cc; border-radius: 5px;">
            <div style="text-align: center; color: #0066cc; font-size: 14px;">
                <strong><?php echo $lang['note']; ?>:</strong> <?php echo $lang['new_day_text'] ?? ($lang['missing_translation'] ?? ''); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($ui_show_schedule_new_day) && $flight_mode === 'daily_schedule'): ?>
        <div style="margin-top: 30px; margin-bottom: 20px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; display: flex; justify-content: space-between; align-items: center;">
            <div style="flex: 1;">
                <div style="font-size: 14px; color: #856404; margin-bottom: 10px;">
                    <strong>⚠️ <?php echo $lang['note']; ?>:</strong> <?php echo sprintf($lang['new_day_exceeds_latest_arrival'], htmlspecialchars($latest_departure_time), htmlspecialchars($default_local_dep_time)); ?>
                </div>
            </div>
            <form method="POST" action="" style="margin: 0; flex-shrink: 0; margin-left: 20px;">
                <input type="hidden" name="schedule_new_day" value="1">
                <input type="hidden" name="icao_arr" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">
                <input type="hidden" name="icao_arr" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">

                <input type="hidden" name="cruise_range_corr_enabled" value="<?php echo htmlspecialchars((string)$cruise_range_corr_enabled); ?>">
                <input type="hidden" name="cruise_range_thr1_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr1_nm); ?>">
                <input type="hidden" name="cruise_range_thr2_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr2_nm); ?>">
                <input type="hidden" name="cruise_range_thr3_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr3_nm); ?>">
                <input type="hidden" name="cruise_range_pp_lt_thr1" value="<?php echo htmlspecialchars((string)$cruise_range_pp_lt_thr1); ?>">
                <input type="hidden" name="cruise_range_pp_thr1_thr2" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr1_thr2); ?>">
                <input type="hidden" name="cruise_range_pp_thr2_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr2_thr3); ?>">
                <input type="hidden" name="cruise_range_pp_ge_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_ge_thr3); ?>">

                <button type="submit" class="button-secondary" style="margin: 0; width: auto; padding: 12px 20px; white-space: nowrap;">📅 <?php echo $lang['schedule_new_day']; ?></button>
            </form>
        </div>
        <?php endif; ?>
        <?php if ($result['show_new_day_box'] && empty($ui_show_schedule_new_day)): ?>
        <div style="margin-top: 30px; margin-bottom: 20px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; display: flex; justify-content: space-between; align-items: center;">
            <div style="flex: 1;">
                <div style="font-size: 14px; color: #856404; margin-bottom: 10px;">
                    <strong>⚠️ <?php echo $lang['note']; ?>:</strong> <?php echo sprintf($lang['new_day_exceeds_latest_arrival'], htmlspecialchars($latest_departure_time), htmlspecialchars($default_local_dep_time)); ?>
                </div>
            </div>
            <form method="POST" action="" style="margin: 0; flex-shrink: 0; margin-left: 20px;">
                <input type="hidden" name="schedule_new_day" value="1">

                <input type="hidden" name="cruise_range_corr_enabled" value="<?php echo htmlspecialchars((string)$cruise_range_corr_enabled); ?>">
                <input type="hidden" name="cruise_range_thr1_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr1_nm); ?>">
                <input type="hidden" name="cruise_range_thr2_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr2_nm); ?>">
                <input type="hidden" name="cruise_range_thr3_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr3_nm); ?>">
                <input type="hidden" name="cruise_range_pp_lt_thr1" value="<?php echo htmlspecialchars((string)$cruise_range_pp_lt_thr1); ?>">
                <input type="hidden" name="cruise_range_pp_thr1_thr2" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr1_thr2); ?>">
                <input type="hidden" name="cruise_range_pp_thr2_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr2_thr3); ?>">
                <input type="hidden" name="cruise_range_pp_ge_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_ge_thr3); ?>">

                <button type="submit" class="button-secondary" style="margin: 0; width: auto; padding: 12px 20px; white-space: nowrap;">📅 <?php echo $lang['schedule_new_day']; ?></button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (!empty($result) && !empty($result['distance_exceeds_range'])): ?>
            <div class="error-box" style="background:#fff3cd; border-left:4px solid #ffc107; color:#856404; margin-top:15px; margin-bottom:15px;">
                <strong><?php
                    $excess = isset($result['distance_excess_nm']) ? (float)$result['distance_excess_nm'] : 0.0;
                    echo htmlspecialchars(sprintf($lang['warning_distance_exceeds_range'], number_format($excess, 1)));
                ?></strong>
            </div>
        <?php endif; ?>

        <div id="actionsSection"></div>
        <div class="button-group" style="margin-top: 30px; position: relative; z-index: 100;">
            <?php if ($flight_mode === 'daily_schedule' && $result['new_day_triggered']): ?>
            <!-- New day triggered: reset to first-day logic -->
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="next_leg" value="1">
                <input type="hidden" name="saved_default_dep_time" value="<?php echo htmlspecialchars($default_local_dep_time); ?>">
                <input type="hidden" name="new_day_flag" value="1">
                <input type="hidden" name="next_leg_dep" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">
                <input type="hidden" name="aircraft" value="<?php echo htmlspecialchars($result['aircraft']); ?>">
                <input type="hidden" name="minutes_before_departure" value="<?php echo htmlspecialchars($result['minutes_before_departure']); ?>">
                <input type="hidden" name="hours_after_departure" value="<?php echo htmlspecialchars($result['hours_after_departure']); ?>">
                <input type="hidden" name="flight_mode" value="<?php echo htmlspecialchars($flight_mode); ?>">
                <input type="hidden" name="latest_departure_time" value="<?php echo htmlspecialchars($latest_departure_time); ?>">
				<input type="hidden" name="baseline_latest_departure_time" value="<?php echo htmlspecialchars($latest_departure_time); ?>">
                <input type="hidden" name="buffer_time_knots" value="<?php echo htmlspecialchars((string)($result['buffer_time_knots'] ?? '0')); ?>">
                <input type="hidden" name="buffer_time_mach" value="<?php echo htmlspecialchars((string)($result['buffer_time_mach'] ?? '0')); ?>">

                <input type="hidden" name="short_haul" value="<?php echo htmlspecialchars($short_haul); ?>">
                <input type="hidden" name="medium_haul" value="<?php echo htmlspecialchars($medium_haul); ?>">
                <input type="hidden" name="long_haul" value="<?php echo htmlspecialchars($long_haul); ?>">
                <input type="hidden" name="ultra_long_haul" value="<?php echo htmlspecialchars($ultra_long_haul); ?>">

                <input type="hidden" name="cruise_range_corr_enabled" value="<?php echo htmlspecialchars((string)$cruise_range_corr_enabled); ?>">
                <input type="hidden" name="cruise_range_thr1_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr1_nm); ?>">
                <input type="hidden" name="cruise_range_thr2_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr2_nm); ?>">
                <input type="hidden" name="cruise_range_thr3_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr3_nm); ?>">
                <input type="hidden" name="cruise_range_pp_lt_thr1" value="<?php echo htmlspecialchars((string)$cruise_range_pp_lt_thr1); ?>">
                <input type="hidden" name="cruise_range_pp_thr1_thr2" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr1_thr2); ?>">
                <input type="hidden" name="cruise_range_pp_thr2_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr2_thr3); ?>">
                <input type="hidden" name="cruise_range_pp_ge_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_ge_thr3); ?>">

                <button type="submit" class="button-next-leg">✈️ <?php echo $lang['next_leg']; ?></button>
            </form>
            <?php else: ?>
            <!-- Normal next leg or charter flight -->
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="next_leg" value="1">
                <input type="hidden" name="saved_default_dep_time" value="<?php echo htmlspecialchars($default_local_dep_time); ?>">
                <input type="hidden" name="next_leg_dep" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">
                <input type="hidden" name="aircraft" value="<?php echo htmlspecialchars($result['aircraft']); ?>">
                <input type="hidden" name="minutes_before_departure" value="<?php echo htmlspecialchars($result['minutes_before_departure']); ?>">
                <input type="hidden" name="hours_after_departure" value="<?php echo htmlspecialchars($result['hours_after_departure']); ?>">
                <input type="hidden" name="flight_mode" value="<?php echo htmlspecialchars($flight_mode); ?>">
                <input type="hidden" name="latest_departure_time" value="<?php echo htmlspecialchars($latest_departure_time); ?>">
				<input type="hidden" name="baseline_latest_departure_time" value="<?php echo htmlspecialchars($latest_departure_time); ?>">
                <input type="hidden" name="next_leg_arrival_time_local" value="<?php echo htmlspecialchars($result['local_arrival_time']); ?>">
                <input type="hidden" name="buffer_time_knots" value="<?php echo htmlspecialchars((string)($result['buffer_time_knots'] ?? '0')); ?>">
                <input type="hidden" name="buffer_time_mach" value="<?php echo htmlspecialchars((string)($result['buffer_time_mach'] ?? '0')); ?>">

                <input type="hidden" name="short_haul" value="<?php echo htmlspecialchars($short_haul); ?>">
                <input type="hidden" name="medium_haul" value="<?php echo htmlspecialchars($medium_haul); ?>">
                <input type="hidden" name="long_haul" value="<?php echo htmlspecialchars($long_haul); ?>">
                <input type="hidden" name="ultra_long_haul" value="<?php echo htmlspecialchars($ultra_long_haul); ?>">

                <input type="hidden" name="cruise_range_corr_enabled" value="<?php echo htmlspecialchars((string)$cruise_range_corr_enabled); ?>">
                <input type="hidden" name="cruise_range_thr1_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr1_nm); ?>">
                <input type="hidden" name="cruise_range_thr2_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr2_nm); ?>">
                <input type="hidden" name="cruise_range_thr3_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr3_nm); ?>">
                <input type="hidden" name="cruise_range_pp_lt_thr1" value="<?php echo htmlspecialchars((string)$cruise_range_pp_lt_thr1); ?>">
                <input type="hidden" name="cruise_range_pp_thr1_thr2" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr1_thr2); ?>">
                <input type="hidden" name="cruise_range_pp_thr2_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr2_thr3); ?>">
                <input type="hidden" name="cruise_range_pp_ge_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_ge_thr3); ?>">

                <button type="submit" class="button-next-leg">✈️ <?php echo $lang['next_leg']; ?></button>
            </form>

            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="saved_default_dep_time" value="<?php echo htmlspecialchars($default_local_dep_time); ?>">
                <input type="hidden" name="local_departure_time" value="<?php echo htmlspecialchars($local_departure_time); ?>">
                <input type="hidden" name="icao_dep" value="<?php echo htmlspecialchars($result['dep_icao']); ?>">
                <input type="hidden" name="icao_arr" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">
                <input type="hidden" name="aircraft" value="<?php echo htmlspecialchars($result['aircraft']); ?>">
                <input type="hidden" name="minutes_before_departure" value="<?php echo htmlspecialchars($result['minutes_before_departure']); ?>">
                <input type="hidden" name="hours_after_departure" value="<?php echo htmlspecialchars($result['hours_after_departure']); ?>">
                <input type="hidden" name="flight_mode" value="<?php echo htmlspecialchars($result['flight_mode']); ?>">
                <input type="hidden" name="latest_departure_time" value="<?php echo htmlspecialchars($latest_departure_time); ?>">
				<input type="hidden" name="baseline_latest_departure_time" value="<?php echo htmlspecialchars($latest_departure_time); ?>">
                <input type="hidden" name="buffer_time_knots" value="<?php echo htmlspecialchars((string)($result['buffer_time_knots'] ?? '0')); ?>">
                <input type="hidden" name="buffer_time_mach" value="<?php echo htmlspecialchars((string)($result['buffer_time_mach'] ?? '0')); ?>">
                <input type="hidden" name="short_haul" value="<?php echo htmlspecialchars($short_haul); ?>">
                <input type="hidden" name="medium_haul" value="<?php echo htmlspecialchars($medium_haul); ?>">
                <input type="hidden" name="long_haul" value="<?php echo htmlspecialchars($long_haul); ?>">
                <input type="hidden" name="ultra_long_haul" value="<?php echo htmlspecialchars($ultra_long_haul); ?>">

                <input type="hidden" name="cruise_range_corr_enabled" value="<?php echo htmlspecialchars((string)$cruise_range_corr_enabled); ?>">
                <input type="hidden" name="cruise_range_thr1_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr1_nm); ?>">
                <input type="hidden" name="cruise_range_thr2_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr2_nm); ?>">
                <input type="hidden" name="cruise_range_thr3_nm" value="<?php echo htmlspecialchars((string)$cruise_range_thr3_nm); ?>">
                <input type="hidden" name="cruise_range_pp_lt_thr1" value="<?php echo htmlspecialchars((string)$cruise_range_pp_lt_thr1); ?>">
                <input type="hidden" name="cruise_range_pp_thr1_thr2" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr1_thr2); ?>">
                <input type="hidden" name="cruise_range_pp_thr2_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_thr2_thr3); ?>">
                <input type="hidden" name="cruise_range_pp_ge_thr3" value="<?php echo htmlspecialchars((string)$cruise_range_pp_ge_thr3); ?>">

                <button type="submit" class="button-secondary" style="pointer-events: auto;">🔄 <?php echo $lang['recalculate']; ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>
