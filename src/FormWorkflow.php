<?php
declare(strict_types=1);

// Function to process form submission
function processFormSubmission($icao_dep, $icao_arr, $aircraft, $local_departure_time, $flight_mode, $latest_departure_time, $minutes_before, $hours_after, $buffer_time_knots, $buffer_time_mach, $is_next_leg, $next_leg_departure_time, $lang, $aircraft_list, $windClimo = null, $short_haul, $medium_haul, $long_haul, $ultra_long_haul, $cruise_range_corr = null) {

    if (!$is_next_leg && (empty($icao_dep) || empty($icao_arr))) {
        return [null, $lang['error_icao_required']];
    }

    $error = null;

    $result = validateInputs($aircraft, $aircraft_list, $buffer_time_knots, $buffer_time_mach, $lang);

    if ($result['error']) {
        return [null, $result['error']];
    }
    $aircraft_data = $result['aircraft_data'];
    $cruise_speed = $result['cruise_speed'];
    $speed_type = $result['speed_type'];
    $cruise_altitude = $result['cruise_altitude_ft'];
    $dep_data = getAirportData($icao_dep);
    $arr_data = !empty($icao_arr) ? getAirportData($icao_arr) : null;

    if (($dep_data === null || ($dep_data && isset($dep_data['status']) && $dep_data['status'] === 'connection_error')) &&
        ($arr_data === null || ($arr_data && isset($arr_data['status']) && $arr_data['status'] === 'connection_error'))) {
        return [null, null];
    }

    $combined_error = buildAirportErrors($dep_data, $arr_data, $icao_dep, $icao_arr, $lang);
    if ($combined_error) {
        return [null, $combined_error];
    }

    $dep_airport = is_array($dep_data) && isset($dep_data['data']) ? $dep_data['data'] : null;
    $arr_airport = is_array($arr_data) && isset($arr_data['data']) ? $arr_data['data'] : null;

    if (!$dep_airport || !$arr_airport) {
        return [null, null];
    }

    $dep_altitude_ft = (isset($dep_airport['altitude']) && $dep_airport['altitude'] !== null) ? (float)$dep_airport['altitude'] : 0.0;
    $arr_altitude_ft = (isset($arr_airport['altitude']) && $arr_airport['altitude'] !== null) ? (float)$arr_airport['altitude'] : 0.0;

    $distance = calculateDistance($dep_airport['lat'], $dep_airport['lon'], $arr_airport['lat'], $arr_airport['lon']);

    if ($windClimo === null && $dep_airport && $arr_airport && $aircraft_data && !isset($_POST['wind_ignore'])) {

        $windFile = __DIR__ . '/../wind/WindClimo_AVG_1958-2008_H15.php';

        $tmp = null;
        if (is_readable($windFile)) {
            $tmp = @require $windFile;
        }

        $isValid = (is_array($tmp) && isset($tmp['levels_hpa'], $tmp['lats'], $tmp['lons'], $tmp['u'], $tmp['v']));
        if ($isValid) {
            $lev0 = $tmp['levels_hpa'][0] ?? null;
            if ($lev0 === null || !isset($tmp['u'][$lev0], $tmp['v'][$lev0])) $isValid = false;
        }

        if ($isValid) {
            $windClimo = $tmp;
        } else {

			$msg = (string)($lang['wind_db_missing_continue_no_wind'] ?? ($lang['missing_translation'] ?? ''));

            $tAccept = (string)($lang['accept'] ?? ($lang['missing_translation'] ?? ''));
            $tCancel = (string)($lang['cancel'] ?? ($lang['missing_translation'] ?? ''));

            // Re-post the same request with wind_ignore=1
            $form = "<form id='windIgnoreForm' method='post' action='" . htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? '')) . "'>"
                  . "<input type='hidden' name='wind_ignore' value='1'>";

            foreach ((array)$_POST as $k => $v) {
                if ($k === 'wind_ignore') continue;
                if (is_array($v)) continue;
                $form .= "<input type='hidden' name='" . htmlspecialchars((string)$k) . "' value='" . htmlspecialchars((string)$v) . "'>";
            }

            $form .= "</form>";

            echo $form;

            echo "<div id='windMissingModalBackdrop' style='position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:99999; display:flex; align-items:center; justify-content:center; padding:20px;'>
                    <div style='background:#fff; width:100%; max-width:520px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.35); overflow:hidden;'>
                        <div style='padding:16px 18px; background:#fff3cd; border-bottom:1px solid rgba(0,0,0,0.08);'>
                            <div style='font-weight:700; color:#856404; font-size:16px;'>⚠️ " . htmlspecialchars($lang['note'] ?? 'NOTE') . "</div>
                        </div>
                        <div style='padding:18px; color:#333; font-size:14px; line-height:1.5;'>
                            " . nl2br(htmlspecialchars($msg)) . "
                        </div>
                        <div style='display:flex; gap:12px; justify-content:flex-end; padding:0 18px 18px 18px;'>
                            <button type='button' id='windMissingCancelBtn' style='width:auto; margin-top:0; padding:10px 16px; background:#e53e3e; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer;'>
                                " . htmlspecialchars($tCancel) . "
                            </button>
                            <button type='button' id='windMissingAcceptBtn' style='width:auto; margin-top:0; padding:10px 16px; background:#48bb78; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer;'>
                                " . htmlspecialchars($tAccept) . "
                            </button>
                        </div>
                    </div>
                  </div>";

            echo "<script>
                (function () {
                    var acceptBtn = document.getElementById('windMissingAcceptBtn');
                    var cancelBtn = document.getElementById('windMissingCancelBtn');
                    var backdrop  = document.getElementById('windMissingModalBackdrop');
                    var form      = document.getElementById('windIgnoreForm');

                    function closeModal() {
                        if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                    }

                    if (acceptBtn) {
                        acceptBtn.addEventListener('click', function () {
                            if (form) form.submit();
                        });
                    }

                    if (cancelBtn) {
                        cancelBtn.addEventListener('click', function () {
                            closeModal();
                            window.location.href = '?jump=top';
                        });
                    }

                    // Click outside (backdrop) closes = cancel
                    if (backdrop) {
                        backdrop.addEventListener('click', function (e) {
                            if (e && e.target === backdrop) {
                                closeModal();
                                window.location.href = '?jump=top';
                            }
                        });
                    }

                    // ESC closes = cancel
                    document.addEventListener('keydown', function (e) {
                        if (e && e.key === 'Escape') {
                            closeModal();
                            window.location.href = '?jump=top';
                        }
                    });
                })();
            </script>";
            exit;

        }
    }

    $calc_result = calculateFlightDetails(
        $distance,
        $cruise_speed,
        $speed_type,
        $cruise_altitude,
        $buffer_time_knots,
        $buffer_time_mach,
        $dep_airport['lat'], $dep_airport['lon'],
        $arr_airport['lat'], $arr_airport['lon'],
        $aircraft_data,
        $windClimo,
        $short_haul,
        $medium_haul,
        $long_haul,
        $ultra_long_haul,
        $cruise_range_corr,
        $arr_altitude_ft,
        $dep_altitude_ft
    );

    $optimized_altitude = $calc_result['optimized_altitude'] ?? $cruise_altitude;
    $cruise_speed_tas = $calc_result['cruise_speed_tas'];
    $flight_time = $calc_result['flight_time'];
    $buffer_time = $calc_result['buffer_time'];
    $mean_tailwind_kt = (float)($calc_result['mean_tailwind_kt'] ?? 0.0);
    $distance_percentage = $calc_result['distance_percentage'] ?? 0;
    $adjusted_distance = $calc_result['adjusted_distance'] ?? $distance;
    $extra_distance = $calc_result['extra_distance'] ?? 0;

    $cruise_range_pp_applied = (float)($calc_result['cruise_range_pp_applied'] ?? 0.0);
    $cruise_range_nm_used = $calc_result['cruise_range_nm_used'] ?? null;

    $tz_result = handleTimezoneAndTimes(
        $dep_airport['lat'], $dep_airport['lon'],
        $local_departure_time,
        $is_next_leg,
        $next_leg_departure_time,
        $minutes_before,
        $hours_after,
        $flight_mode,
        $latest_departure_time,
        $flight_time,
        $buffer_time,
        $arr_airport['lat'], $arr_airport['lon'],
        $lang
    );

    if ($tz_result['error']) {
        return [null, $tz_result['error']];
    }

    $show_new_day_box = false;
    if ($flight_mode === 'daily_schedule') {
        $arr_parts = explode(':', $tz_result['local_arrival_time']);
        $arrival_minutes = (int)$arr_parts[0] * 60 + (int)$arr_parts[1];

        $latest_parts = explode(':', $latest_departure_time);
        $latest_minutes = (int)$latest_parts[0] * 60 + (int)$latest_parts[1];

        if ($arrival_minutes > $latest_minutes) {
            $show_new_day_box = true;
        }
    }

    $cruise_range_nm = (isset($aircraft_data['cruiseRange']) && is_numeric($aircraft_data['cruiseRange']) && (float)$aircraft_data['cruiseRange'] > 0)
        ? (float)$aircraft_data['cruiseRange']
        : null;

    $distance_excess_nm = ($cruise_range_nm !== null)
        ? max(0.0, (float)$adjusted_distance - $cruise_range_nm)
        : 0.0;

    $result = [
        'timezone_warning' => $tz_result['timezone_warning'],
        'new_day_warning' => $tz_result['new_day_warning'],
        'dep_icao' => $icao_dep,
        'dep_name' => $dep_airport['name'],
        'dep_lat' => $dep_airport['lat'],
        'dep_lon' => $dep_airport['lon'],
        'arr_icao' => $icao_arr,
        'arr_name' => $arr_airport['name'],
        'arr_lat' => $arr_airport['lat'],
        'arr_lon' => $arr_airport['lon'],
        'distance' => $adjusted_distance,
        'distance_exceeds_range' => (isset($aircraft_data['cruiseRange']) && is_numeric($aircraft_data['cruiseRange']) && (float)$aircraft_data['cruiseRange'] > 0)
            ? ((float)$adjusted_distance > (float)$aircraft_data['cruiseRange'])
            : false,
        'distance_excess_nm' => $distance_excess_nm,
        'original_distance' => $distance,
        'distance_percentage' => $distance_percentage,
        'extra_distance' => $extra_distance,

        'cruise_range_pp_applied' => $cruise_range_pp_applied,
        'cruise_range_nm_used' => $cruise_range_nm_used,
        'aircraft' => $aircraft,
        'cruise_speed' => $cruise_speed,
        'cruise_speed_tas' => $cruise_speed_tas,
        'mean_tailwind_kt' => $mean_tailwind_kt,
        'cruise_altitude' => $optimized_altitude,
        'speed_type' => $speed_type,
        'local_departure_time' => $local_departure_time,
        'utc_departure_time' => $tz_result['utc_departure_time'] ?? null,
        'minutes_before_departure' => $minutes_before,
        'hours_after_departure' => $hours_after,
        'departure_time' => $tz_result['departure_time'],
        'arrival_time' => $tz_result['arrival_time'],
        'flight_time' => $flight_time,
        'buffer_time' => $buffer_time,
        'buffer_time_knots' => (int)$buffer_time_knots,
        'buffer_time_mach' => (int)$buffer_time_mach,
        'local_departure_time_randomized' => $tz_result['local_departure_time_randomized'],
        'local_arrival_time' => $tz_result['local_arrival_time'],
        'flight_mode' => $flight_mode,
        'latest_departure_time' => $latest_departure_time,
        'new_day_triggered' => $tz_result['new_day_triggered'],
        'is_next_leg_call' => $is_next_leg,
        'show_new_day_box' => $show_new_day_box,
    ];

    return [$result, $error];
}

function validateInputs($aircraft, $aircraft_list, $buffer_time_knots, $buffer_time_mach, $lang) {
    $error = null;
    $aircraft_data = null;
    $cruise_speed = null;
    $speed_type = 'mach';
    $aircraft_data = $aircraft_list[$aircraft] ?? null;
    if (!$aircraft_data) {
        $error = $lang['error_invalid_aircraft_selected'] ?? 'Invalid aircraft selected.';
    } else {
        $cruise_speed = $aircraft_data['cruiseMACH'];
        $speed_type = $aircraft_data['type'];

        if (!isset($aircraft_data['cruiseCeiling']) || !is_numeric($aircraft_data['cruiseCeiling']) || (float)$aircraft_data['cruiseCeiling'] <= 0) {
            $error = $lang['error_aircraft_data_invalid'] ?? ($lang['missing_translation'] ?? '');
        }

        if ($speed_type === 'knots') {
            $cruise_speed = $aircraft_data['cruiseTAS'];
        }
    }

    if ($cruise_speed <= 0) {
        $error = $lang['error_cruise_speed'];
    }

    if ($buffer_time_knots < 0 || $buffer_time_mach < 0) {
        $error = $lang['error_buffer_time'];
    }

    return [
        'error' => $error,
        'aircraft_data' => $aircraft_data,
        'cruise_speed' => $cruise_speed,
        'speed_type' => $speed_type,
        'cruise_altitude_ft' => (int)$aircraft_data['cruiseCeiling'] * 100,
    ];

}

function buildAirportErrors($dep_data, $arr_data, $icao_dep, $icao_arr, $lang) {
    $errors = [];

    if ($dep_data && is_array($dep_data) && $dep_data['status'] === 'not_found') {
        $dep_link = '<a href="https://www.fsairlines.net/crewcenter/index.php?icao=' . urlencode($icao_dep) . '&status=db_apts&status2=logged&submit=Submit" target="_blank">' . htmlspecialchars($icao_dep) . '</a>';
        $errors[] = sprintf($lang['error_departure_airport'], $dep_link, $icao_dep);
    }

    if ($arr_data && is_array($arr_data) && $arr_data['status'] === 'not_found' && !empty($icao_arr)) {
        $arr_link = '<a href="https://www.fsairlines.net/crewcenter/index.php?icao=' . urlencode($icao_arr) . '&status=db_apts&status2=logged&submit=Submit" target="_blank">' . htmlspecialchars($icao_arr) . '</a>';
        $errors[] = sprintf($lang['error_arrival_airport'], $arr_link, $icao_arr);
    }

    if (empty($errors)) {
        return null;
    } elseif (count($errors) === 1) {
        return $errors[0];
    } else {
        return implode('<br><br>', $errors);
    }
}

function handleTimezoneAndTimes($dep_lat, $dep_lon, $local_departure_time, $is_next_leg, $next_leg_departure_time, $minutes_before, $hours_after, $flight_mode, $latest_departure_time, $flight_time, $buffer_time, $arr_lat, $arr_lon, $lang) {
    $tz_info = getTimezoneFromCoordinates($dep_lat, $dep_lon);
    $error = null;

    if (!$tz_info || !isset($tz_info['timezone']) || $tz_info['timezone'] === '' || $tz_info['timezone'] === 'UTC') {
        $error = $lang['error_time_conversion'];
        return ['error' => $error];
    }

    $tz_info_arr = getTimezoneFromCoordinates($arr_lat, $arr_lon);
    if (!$tz_info_arr || !isset($tz_info_arr['timezone']) || $tz_info_arr['timezone'] === '' || $tz_info_arr['timezone'] === 'UTC') {
        $error = $lang['error_time_conversion'];
        return ['error' => $error];
    }

    $timezone_warning = false;

    $user_local_time = ($is_next_leg && !isset($_POST['new_day_flag'])) ? $next_leg_departure_time : $local_departure_time;
    $utc_departure_time = convertLocalTimeToUTC($user_local_time, $tz_info['timezone']);

    if (!$utc_departure_time) {
        $error = $lang['error_time_conversion'];
        return ['error' => $error];
    }

    $total_time = $flight_time + $buffer_time;
    if ($is_next_leg) {
        $departure_time = roundToFiveMinutes($utc_departure_time);
    } elseif (isset($_POST['new_day_flag'])) {
        $departure_time = roundToFiveMinutes($utc_departure_time);
    } else {
        $time_after_minutes = intval($hours_after * 60);
        $random_dep_time = generateRandomTime($utc_departure_time, $minutes_before, $time_after_minutes / 60);
        $departure_time = roundToFiveMinutes($random_dep_time);
    }

    if (!$departure_time || !preg_match('/^(\d{1,2}):(\d{2})$/', $departure_time)) {
        $error = $lang['error_time_conversion'];
        return ['error' => $error];
    }

    $arrival_time_raw = addMinutesToTime($departure_time, $total_time);
    $arrival_time = roundToFiveMinutes($arrival_time_raw);

    // Convert UTC departure time to local time for display
    $local_departure_time_randomized = $departure_time;
    try {
        $tz = new DateTimeZone($tz_info['timezone']);
        $dateTime = DateTime::createFromFormat('H:i', (string)$departure_time, new DateTimeZone('UTC'));
        if (!$dateTime) {
            $dateTime = new DateTime((string)$departure_time, new DateTimeZone('UTC'));
        }
        $dateTime->setTimezone($tz);
        $local_departure_time_randomized = $dateTime->format('H:i');

        if ($local_departure_time_randomized === false || strpos($local_departure_time_randomized, '-') !== false) {
            $error = $lang['error_time_conversion'];
            return ['error' => $error];
        }
    } catch (Exception $e) {
        $error = $lang['error_time_conversion'];
        return ['error' => $error];
    }

    // Convert UTC arrival time to local time for display
    $local_arrival_time = $arrival_time;
    try {
        $tz_arr = new DateTimeZone($tz_info_arr['timezone']);
        $arrDateTime = DateTime::createFromFormat('H:i', (string)$arrival_time, new DateTimeZone('UTC'));
        if (!$arrDateTime) {
            $arrDateTime = new DateTime((string)$arrival_time, new DateTimeZone('UTC'));
        }
        $arrDateTime->setTimezone($tz_arr);
        $local_arrival_time = $arrDateTime->format('H:i');

        if ($local_arrival_time === false || strpos($local_arrival_time, '-') !== false) {
            $error = $lang['error_time_conversion'];
            return ['error' => $error];
        }
    } catch (Exception $e) {
        $error = $lang['error_time_conversion'];
        return ['error' => $error];
    }

    $new_day_triggered = false;
    $new_day_warning = null;
    if ($is_next_leg && $flight_mode === 'daily_schedule') {
        $new_day_triggered = false;
    }

    return [
        'error' => $error,
        'departure_time' => $departure_time,
        'arrival_time' => $arrival_time,
        'local_departure_time_randomized' => $local_departure_time_randomized,
        'local_arrival_time' => $local_arrival_time,
        'timezone_warning' => $timezone_warning,
        'new_day_triggered' => $new_day_triggered,
        'new_day_warning' => $new_day_warning,
        'utc_departure_time' => $utc_departure_time
    ];
}
