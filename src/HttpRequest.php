<?php
declare(strict_types=1);

function handleHttpRequest(array $lang, array $aircraft_list, ?array $windClimo = null): array {

    $DEF = fsa_defaults();

    $icao_dep = '';
    $icao_arr = '';

    // Normalized request (do not mutate the superglobal)
    $req = $_POST;

    // Hard reset: ignore any request state and fall back to fixed defaults
    if (isset($_GET['reset_all']) && $_GET['reset_all'] === '1') {
        $req = [];
    }

    // Handle schedule new day button (do not mutate the superglobal). Keep aircraft/buffers/hauls.
    if (isset($req['schedule_new_day'])) {
        $req['icao_dep'] = $req['icao_arr'] ?? '';
        $req['icao_arr'] = '';
        $req['local_departure_time'] = $DEF['local_departure_time'];
        $req['latest_departure_time'] = $DEF['latest_departure_time'];
        $req['flight_mode'] = 'daily_schedule';
        $req['minutes_before_departure'] = $DEF['minutes_before_departure'];
        unset($req['hours_after_departure']);
        unset($req['turnaround_time_input']);
        $req['schedule_new_day_mode'] = 1;
    }

    // Handle next leg button
    $next_leg_dep = null;
    $is_next_leg = isset($req['next_leg']);
    if ($is_next_leg) {
        $next_leg_dep = $req['next_leg_dep'];
    }

    // Hours after departure - default depends on mode
    $hours_after = $DEF['hours_after_departure_charter']; // default for charter
    if (($req['flight_mode'] ?? $DEF['flight_mode']) === 'daily_schedule') {
        $hours_after = $DEF['hours_after_departure_daily_schedule'];
    }

    // No cookie-based defaults; use request or fixed defaults
    $minutes_before = $req['minutes_before_departure'] ?? $DEF['minutes_before_departure'];
    $hours_after = (string)$hours_after;

    $hours_after = $req['hours_after_departure'] ?? $hours_after;

    // Priority 2: Check POST data for direct field or saved hidden field
    if (isset($req['local_departure_time_saved']) && $req['local_departure_time_saved'] !== '') {
        $local_departure_time = $req['local_departure_time_saved'];
    } elseif (isset($req['local_departure_time'])) {
        $local_departure_time = $req['local_departure_time'];
    } else {
        $local_departure_time = $DEF['local_departure_time'];
    }

    // Baseline earliest local departure time (for warnings and for resetting when switching modes):
    // - Use saved_default_dep_time when coming from results buttons (most reliable during Next-leg chains)
    // - Else use local_departure_time_saved if user clicked "Save as default"
    // - Else default to 07:00
    $baseline_local_departure_time = (isset($req['saved_default_dep_time']) && $req['saved_default_dep_time'] !== '')
        ? (string)$req['saved_default_dep_time']
        : ((isset($req['local_departure_time_saved']) && $req['local_departure_time_saved'] !== '')
            ? (string)$req['local_departure_time_saved']
            : $DEF['local_departure_time']);

    $latest_departure_time = $req['latest_departure_time'] ?? $DEF['latest_departure_time'];

    //Baseline latest time must be the saved/default latest, not a computed/next-leg value.
    $baseline_latest_departure_time = (isset($req['latest_departure_time']) && $req['latest_departure_time'] !== '')
        ? (string)$req['latest_departure_time']
        : $DEF['latest_departure_time'];

    $turnaround_time_mach = $DEF['turnaround_time_mach'];
    $turnaround_time_knots = $DEF['turnaround_time_knots'];

    $buffer_time_knots = intval($req['buffer_time_knots'] ?? $DEF['buffer_time_knots']);
    $buffer_time_mach = intval($req['buffer_time_mach'] ?? $DEF['buffer_time_mach']);

    $short_haul = floatval($req['short_haul'] ?? $DEF['short_haul']);
    $medium_haul = floatval($req['medium_haul'] ?? $DEF['medium_haul']);
    $long_haul = floatval($req['long_haul'] ?? $DEF['long_haul']);
    $ultra_long_haul = floatval($req['ultra_long_haul'] ?? $DEF['ultra_long_haul']);

    // CruiseRange correction controls (all strings in config/JS; cast as needed later)
    $cruise_range_corr_enabled = (string)($req['cruise_range_corr_enabled'] ?? $DEF['cruise_range_corr_enabled']);

    $cruise_range_thr1_nm = (string)($req['cruise_range_thr1_nm'] ?? $DEF['cruise_range_thr1_nm']);
    $cruise_range_thr2_nm = (string)($req['cruise_range_thr2_nm'] ?? $DEF['cruise_range_thr2_nm']);
    $cruise_range_thr3_nm = (string)($req['cruise_range_thr3_nm'] ?? $DEF['cruise_range_thr3_nm']);

    $cruise_range_pp_lt_thr1 = (string)($req['cruise_range_pp_lt_thr1'] ?? $DEF['cruise_range_pp_lt_thr1']);
    $cruise_range_pp_thr1_thr2 = (string)($req['cruise_range_pp_thr1_thr2'] ?? $DEF['cruise_range_pp_thr1_thr2']);
    $cruise_range_pp_thr2_thr3 = (string)($req['cruise_range_pp_thr2_thr3'] ?? $DEF['cruise_range_pp_thr2_thr3']);
    $cruise_range_pp_ge_thr3 = (string)($req['cruise_range_pp_ge_thr3'] ?? $DEF['cruise_range_pp_ge_thr3']);

    // --- Sanitize CruiseRange correction inputs (server-side, reliable) ---
    $cruise_range_corr_enabled = ($cruise_range_corr_enabled === '1') ? '1' : '0';

    $toFloatOrDefault = function($v, $def) use (&$cruise_range_corr_sanitized) {
        if ($v === null) {
            $cruise_range_corr_sanitized = true;
            return (float)$def;
        }
        $s = trim((string)$v);
        if ($s === '') {
            $cruise_range_corr_sanitized = true;
            return (float)$def;
        }
        if (!is_numeric($s)) {
            $cruise_range_corr_sanitized = true;
            return (float)$def;
        }
        return (float)$s;
    };

    $toIntOrDefault = function($v, $def) use (&$cruise_range_corr_sanitized) {
        if ($v === null) {
            $cruise_range_corr_sanitized = true;
            return (int)$def;
        }
        $s = trim((string)$v);
        if ($s === '') {
            $cruise_range_corr_sanitized = true;
            return (int)$def;
        }
        if (!is_numeric($s)) {
            $cruise_range_corr_sanitized = true;
            return (int)$def;
        }
        return (int)round((float)$s);
    };

    $cruise_range_corr_sanitized = false;
    $cruise_range_corr_sanitized_msg = '';

    $thr1 = $toIntOrDefault($cruise_range_thr1_nm, $DEF['cruise_range_thr1_nm']);
    $thr2 = $toIntOrDefault($cruise_range_thr2_nm, $DEF['cruise_range_thr2_nm']);
    $thr3 = $toIntOrDefault($cruise_range_thr3_nm, $DEF['cruise_range_thr3_nm']);

    // enforce strictly increasing thresholds; if invalid, reset to defaults
    if (!($thr1 > 0 && $thr2 > $thr1 && $thr3 > $thr2)) {
        $thr1 = $toIntOrDefault($DEF['cruise_range_thr1_nm'], 2000);
        $thr2 = $toIntOrDefault($DEF['cruise_range_thr2_nm'], 6000);
        $thr3 = $toIntOrDefault($DEF['cruise_range_thr3_nm'], 8000);
        $cruise_range_corr_sanitized = true;
    }

    $pp_lt = $toFloatOrDefault($cruise_range_pp_lt_thr1, $DEF['cruise_range_pp_lt_thr1']);
    $pp_12 = $toFloatOrDefault($cruise_range_pp_thr1_thr2, $DEF['cruise_range_pp_thr1_thr2']);
    $pp_23 = $toFloatOrDefault($cruise_range_pp_thr2_thr3, $DEF['cruise_range_pp_thr2_thr3']);
    $pp_ge = $toFloatOrDefault($cruise_range_pp_ge_thr3, $DEF['cruise_range_pp_ge_thr3']);

    // clamp percentage-point offsets to a safe range
    $clamp = function($x, $min, $max) {
        if ($x < $min) return $min;
        if ($x > $max) return $max;
        return $x;
    };

    $pp_lt_before = $pp_lt;
    $pp_12_before = $pp_12;
    $pp_23_before = $pp_23;
    $pp_ge_before = $pp_ge;

    $pp_lt = $clamp($pp_lt, -50.0, 50.0);
    $pp_12 = $clamp($pp_12, -50.0, 50.0);
    $pp_23 = $clamp($pp_23, -50.0, 50.0);
    $pp_ge = $clamp($pp_ge, -50.0, 50.0);

    if ($pp_lt !== $pp_lt_before || $pp_12 !== $pp_12_before || $pp_23 !== $pp_23_before || $pp_ge !== $pp_ge_before) {
        $cruise_range_corr_sanitized = true;
    }

    // write back sanitized strings (so the form shows the sanitized values)
    if ($cruise_range_corr_sanitized) {
        $cruise_range_corr_sanitized_msg = (string)($lang['cruise_range_correction_invalid_reset'] ?? ($lang['missing_translation'] ?? ''));
    }

    $cruise_range_thr1_nm = (string)$thr1;
    $cruise_range_thr2_nm = (string)$thr2;
    $cruise_range_thr3_nm = (string)$thr3;

    $cruise_range_pp_lt_thr1 = (string)$pp_lt;
    $cruise_range_pp_thr1_thr2 = (string)$pp_12;
    $cruise_range_pp_thr2_thr3 = (string)$pp_23;
    $cruise_range_pp_ge_thr3 = (string)$pp_ge;

    $cruise_range_corr = [
        'enabled' => $cruise_range_corr_enabled,
        'thr1_nm' => $cruise_range_thr1_nm,
        'thr2_nm' => $cruise_range_thr2_nm,
        'thr3_nm' => $cruise_range_thr3_nm,
        'pp_lt_thr1' => $cruise_range_pp_lt_thr1,
        'pp_thr1_thr2' => $cruise_range_pp_thr1_thr2,
        'pp_thr2_thr3' => $cruise_range_pp_thr2_thr3,
        'pp_ge_thr3' => $cruise_range_pp_ge_thr3,
    ];

    if ($is_next_leg && !isset($req['new_day_flag']) && !isset($req['schedule_new_day_mode'])) {
        if (isset($req['next_leg_arrival_time_local'])) {
            $prev_arrival_local = $req['next_leg_arrival_time_local'];

            if (isset($req['turnaround_time_input']) && $req['turnaround_time_input'] !== '') {
                $turnaround = intval($req['turnaround_time_input']);
            } else {
                // NOTE: original code referenced $result['speed_type'] here (not defined yet).
                // To preserve behavior without guessing, default to 60 (mach-ish).
                $turnaround = 60;
            }

            // Next leg: local departure = local arrival + turnaround (no timezone APIs needed here)
            $next_dep_local_raw = addMinutesToTime($prev_arrival_local, $turnaround);
            $local_departure_time = roundToFiveMinutes($next_dep_local_raw);
        }
    }

    $result = null;
    $error = null;
	
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $icao_dep = strtoupper(trim($req['icao_dep'] ?? ''));
        $icao_arr = strtoupper(trim($req['icao_arr'] ?? ''));

        if ($is_next_leg) {
            // Next leg: previous ARR becomes new DEP, and ARR is cleared (both modes)
            $icao_dep = strtoupper(trim((string)($req['next_leg_dep'] ?? '')));
            $icao_arr = '';
        }

        if ($is_next_leg) {

            $posted_mode = $req['flight_mode'] ?? 'charter';

            if ($posted_mode !== 'daily_schedule') {
                // Charter: reset to baseline
                $local_departure_time = $baseline_local_departure_time;
            }

        } else {
            $local_departure_time = $req['local_departure_time'] ?? $local_departure_time;
        }

        if (isset($req['hours_after_departure'])) {
            $hours_after = $req['hours_after_departure'];
        }

        if (isset($req['schedule_new_day_mode'])) {
            $local_departure_time = $DEF['local_departure_time'];
            $latest_departure_time = $DEF['latest_departure_time'];
            $flight_mode = 'daily_schedule';

        } elseif (
            !$is_next_leg &&
            (empty($icao_dep) || empty($icao_arr)) &&
            !(isset($_GET['add_aircraft']) && $_GET['add_aircraft'] === '1')
        ) {
            $error = $lang['error_icao_required'];

        } else {
            $aircraft = $req['aircraft'] ?? '';
            if ($aircraft === '' || $aircraft === 'custom') {
                $aircraft = array_key_first($aircraft_list);
            }

            $flight_mode = $req['flight_mode'] ?? (string)$DEF['flight_mode'];

            // Do NOT override sanitized VM values with hard-coded defaults here.
            // Buffers, hauls, and cruise-range-correction values were computed (and sanitized) above.
            $minutes_before = (string)($req['minutes_before_departure'] ?? $minutes_before);

            if (isset($req['hours_after_departure'])) {
                $hours_after = (string)$req['hours_after_departure'];
            } else {
                $hours_after = (string)$hours_after;
            }

            [$result, $error] = processFormSubmission(
                $icao_dep, $icao_arr,
                $aircraft,
                $local_departure_time,
                $flight_mode,
                $latest_departure_time,
                $minutes_before,
                $hours_after,
                $buffer_time_knots,
                $buffer_time_mach,
                $is_next_leg,
                $local_departure_time,
                $lang,
                $aircraft_list,
                $windClimo,
                $short_haul,
                $medium_haul,
                $long_haul,
                $ultra_long_haul,
                $cruise_range_corr
            );

            if ($result) $result['is_next_leg_call'] = intval($is_next_leg);
        }
    }

    // Decide if UI should show the "Schedule a new day" box (move logic out of results.php)
    $ui_show_schedule_new_day = false;
    $ui_default_local_dep_time = $baseline_local_departure_time;

    if ($result && $flight_mode === 'daily_schedule' && empty($result['new_day_triggered'])) {
        $arr_local_mins = time_hm_to_minutes((string)($result['local_arrival_time'] ?? ''));
        $default_dep_mins = time_hm_to_minutes($ui_default_local_dep_time);
        $latest_mins = time_hm_to_minutes((string)$latest_departure_time);

        // Turnaround minutes (use POST value if present, else use cookie defaults)
        $turnaround_mins = null;
        if (isset($req['turnaround_time_input']) && $req['turnaround_time_input'] !== '') {
            $turnaround_mins = (int)$req['turnaround_time_input'];
        } else {
            $speed_type = (string)($result['speed_type'] ?? 'mach');
            $turnaround_mins = ($speed_type === 'mach') ? (int)$turnaround_time_mach : (int)$turnaround_time_knots;
        }

        if ($arr_local_mins !== null && $default_dep_mins !== null && $latest_mins !== null && $turnaround_mins !== null) {
            $next_dep_mins = $arr_local_mins + $turnaround_mins;
            $next_dep_mins = $next_dep_mins % 1440;

            $is_within = time_in_range_wrap($next_dep_mins, $default_dep_mins, $latest_mins);
            $ui_show_schedule_new_day = !$is_within;
        }
    }

    // Values used by results.php forms (avoid using superglobals in templates)
    $vm_turnaround_time = (isset($req['turnaround_time_input']) && $req['turnaround_time_input'] !== '')
        ? (string)$req['turnaround_time_input']
        : '';

    // Use baseline latest time for UI display/warnings
    $latest_departure_time = (string)$baseline_latest_departure_time;

    return [
        'icao_dep' => $icao_dep,
        'flight_mode' => $flight_mode,
        'local_departure_time' => $local_departure_time,
        'latest_departure_time' => $latest_departure_time,
        'minutes_before' => $minutes_before,
        'hours_after' => $hours_after,
        'vm_turnaround_time' => (string)$vm_turnaround_time,
        'turnaround_time_mach' => $turnaround_time_mach,
        'turnaround_time_knots' => $turnaround_time_knots,
        'buffer_time_knots' => (int)$buffer_time_knots,
        'buffer_time_mach' => (int)$buffer_time_mach,
        'short_haul' => (float)$short_haul,
        'medium_haul' => (float)$medium_haul,
        'long_haul' => (float)$long_haul,
        'ultra_long_haul' => (float)$ultra_long_haul,

        'cruise_range_corr_enabled' => $cruise_range_corr_enabled,
        'cruise_range_thr1_nm' => $cruise_range_thr1_nm,
        'cruise_range_thr2_nm' => $cruise_range_thr2_nm,
        'cruise_range_thr3_nm' => $cruise_range_thr3_nm,
        'cruise_range_pp_lt_thr1' => $cruise_range_pp_lt_thr1,
        'cruise_range_pp_thr1_thr2' => $cruise_range_pp_thr1_thr2,
        'cruise_range_pp_thr2_thr3' => $cruise_range_pp_thr2_thr3,
        'cruise_range_pp_ge_thr3' => $cruise_range_pp_ge_thr3,

        'cruise_range_corr_sanitized' => ($cruise_range_corr_sanitized ? '1' : '0'),
        'cruise_range_corr_sanitized_msg' => $cruise_range_corr_sanitized_msg,

        'result' => $result,
        'error' => $error,
        'next_leg_dep' => $next_leg_dep,
        'is_next_leg' => (bool)$is_next_leg,
        'is_new_day' => (!empty($req['new_day_flag']) || !empty($req['schedule_new_day']) || (!empty($result['new_day_triggered']))),

        // VM-only fields for templates (avoid reading superglobals in templates)
        'form_icao_arr_value' => ($is_next_leg ? '' : $icao_arr),
        'form_flight_mode' => $flight_mode,
        'form_aircraft' => (string)($req['aircraft'] ?? ''),

        // UI flags/values (computed in VM, not in template)
        'ui_show_schedule_new_day' => (bool)$ui_show_schedule_new_day,
        'ui_default_local_dep_time' => (string)$ui_default_local_dep_time,
    ];
}

function time_hm_to_minutes(string $hm): ?int {
    if (!preg_match('/^(\d{1,2}):(\d{2})$/', $hm, $m)) return null;
    $h = (int)$m[1];
    $min = (int)$m[2];
    if ($h < 0 || $h > 23) return null;
    if ($min < 0 || $min > 59) return null;
    return $h * 60 + $min;
}

/**
 * Returns true if value is within [start..end] on a 24h clock, supporting wraparound.
 * Example wraparound: start=23:00 end=06:00.
 */
function time_in_range_wrap(int $value, int $start, int $end): bool {
    if ($start <= $end) {
        return ($value >= $start && $value <= $end);
    }
    return ($value >= $start || $value <= $end);
}
