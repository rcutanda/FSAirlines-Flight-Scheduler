<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/vhosts/simulaciondevuelo.com/logs/error_log');
// Include configuration, functions, data, and helpers
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/aircraft.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/preferences.php';

// Start session
session_start();

// Initialize variables
$icao_dep = '';
$icao_arr = '';

// Preferences directory (must be before language selection)
$prefs_dir = __DIR__ . '/user_preferences';
if (!is_dir($prefs_dir)) {
    mkdir($prefs_dir, 0755, true);
}

// Call language selection function (now in preferences.php)
$result = handleLanguageSelection($prefs_dir);
$lang = $result['lang'];
$current_language = $result['current_language'];

// Load preferences (function now in preferences.php)
$user_id = getOrGenerateUserId();
$prefs_file = __DIR__ . '/user_preferences' . '/' . $user_id . '.json';
$saved_prefs = loadPreferences($prefs_file);

// Handle save departure default button
if (isset($_POST['save_departure_default'])) {
    $saved_prefs['local_departure_time'] = $_POST['local_departure_time'] ?? '07:00';
    savePreferences($prefs_file, $saved_prefs);
    exit();
}

// Handle save arrival default button
if (isset($_POST['save_arrival_default'])) {
    $saved_prefs['latest_arrival_time'] = $_POST['latest_arrival_time'] ?? '23:55';
    savePreferences($prefs_file, $saved_prefs);
    exit();
}

// Handle reset button
if (isset($_POST['reset'])) {
    if (file_exists($prefs_file)) {
        unlink($prefs_file);
    }
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle schedule new day button - use saved defaults
if (isset($_POST['schedule_new_day'])) {
    // Use the previous arrival airport as the new departure airport
    $_POST['icao_dep'] = $_POST['icao_arr'] ?? '';
    // Clear arrival so form is empty for user to select new one
    $_POST['icao_arr'] = '';
    // Restore ONLY the saved defaults - clear all other form data
    $_POST['local_departure_time'] = '07:00';
    $_POST['latest_arrival_time'] = '23:55';
    $_POST['flight_mode'] = 'daily_schedule';
    $_POST['minutes_before_departure'] = '90';
    $_POST['minutes_after_departure'] = '30';
    // Clear turnaround time so defaults are used
    unset($_POST['turnaround_time_input']);
    // Set a flag so we know to expect only departure airport
    $_POST['schedule_new_day_mode'] = 1;
}

// Handle next leg button
$next_leg_dep = null;
$is_next_leg = isset($_POST['next_leg']);
$daily_schedule_warning = null;
if ($is_next_leg) {
    $next_leg_dep = $_POST['next_leg_dep'];
}

// Handle turnaround time from daily schedule
$next_leg_turnaround_time = null;
if (isset($_POST['turnaround_time_input'])) {
    $next_leg_turnaround_time = intval($_POST['turnaround_time_input']);
}

// Set defaults
$local_departure_time = $saved_prefs['local_departure_time'] ?? '07:00';
$latest_arrival_time = $saved_prefs['latest_arrival_time'] ?? '23:55';
$minutes_before = $saved_prefs['minutes_before_departure'] ?? '90';
$hours_after = $saved_prefs['hours_after_departure'] ?? '15';
$minutes_after = $saved_prefs['minutes_after_departure'] ?? '30';
$turnaround_time_mach = $saved_prefs['turnaround_time_mach'] ?? '60';
$turnaround_time_knots = $saved_prefs['turnaround_time_knots'] ?? '40';
$climb_speed_knots = $saved_prefs['climb_speed_knots'] ?? '250';
$buffer_time_vfr = $saved_prefs['buffer_time_vfr'] ?? '15';
$buffer_time_ifr = $saved_prefs['buffer_time_ifr'] ?? '30';
$climb_rate_vfr = $saved_prefs['climb_rate_vfr'] ?? '800';
$climb_rate_ifr = $saved_prefs['climb_rate_ifr'] ?? '1800';

// Calculate next departure time for next leg (consolidated)
$calculated_next_departure = $local_departure_time;
if ($is_next_leg && !isset($_POST['new_day_flag']) && !isset($_POST['schedule_new_day_mode'])) {
    if (isset($_POST['next_leg_departure_time']) && isset($_POST['next_leg_turnaround_time'])) {
        $prev_arrival_utc = $_POST['next_leg_departure_time'];
        $turnaround = intval($_POST['next_leg_turnaround_time']);
        try {
            if (isset($_POST['next_leg_dep'])) {
                $next_dep_result = getAirportData($_POST['next_leg_dep']);
                if ($next_dep_result && is_array($next_dep_result) && isset($next_dep_result['data']) && $next_dep_result['data']) {
                    $next_dep_data = $next_dep_result['data'];
                    $dep_tz_info = getTimezoneFromCoordinates($next_dep_data['lat'], $next_dep_data['lon']);
                    if (!$dep_tz_info) {
                        $error = 'The timezone API failed for the next departure airport (' . $_POST['next_leg_dep'] . '). Please press "Next Leg" again to retry.';
                    } else {
                        $prevDateTime = new DateTime($prev_arrival_utc, new DateTimeZone('UTC'));
                        $prevDateTime->setTimezone(new DateTimeZone($dep_tz_info['timezone']));
                        $prev_arrival_local = $prevDateTime->format('H:i');
                        $next_dep_local_raw = addMinutesToTime($prev_arrival_local, $turnaround);
                        $calculated_next_departure = roundToFiveMinutes($next_dep_local_raw);
                        $local_departure_time = $calculated_next_departure;
                    }
                }
            }
        } catch (Exception $e) {
            $calculated_next_departure = $local_departure_time;
        }
    }
}

// Initialize results
$result = null;
$error = null;

// Process only on POST (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract POST variables explicitly
    $icao_dep = strtoupper(trim($_POST['icao_dep'] ?? ''));
    $icao_arr = strtoupper(trim($_POST['icao_arr'] ?? ''));

    if ($is_next_leg) {
        $icao_dep = strtoupper(trim($_POST['next_leg_dep']));
    }

    // Use the form's local_departure_time input value (which may be auto-calculated from next_leg)
    // If it's a next_leg call and we have a calculated departure time, use that
    if ($is_next_leg && $calculated_next_departure) {
        $local_departure_time = $calculated_next_departure;
    } else {
        $local_departure_time = $_POST['local_departure_time'] ?? $local_departure_time;
    }


    // In schedule_new_day mode, only departure is required (arrival will be selected next)
    if (isset($_POST['schedule_new_day_mode'])) {
        // Don't validate or process - just restore the user's saved departure time and exit
        $local_departure_time = '07:00';
        $latest_arrival_time = '23:55';
        $flight_mode = 'daily_schedule';
        $calculated_next_departure = $local_departure_time;
        $result = null;
        $error = null;
    } elseif (!$is_next_leg && (empty($icao_dep) || empty($icao_arr))) {

    } else {
        $aircraft = $_POST['aircraft'] ?? 'custom';
        $cruise_altitude = intval($_POST['cruise_altitude'] ?? 35000);
        $flight_mode = $_POST['flight_mode'] ?? 'charter';
        $minutes_before = $_POST['minutes_before_departure'] ?? '90';
        $hours_after = $_POST['hours_after_departure'] ?? '15';
        $minutes_after = $_POST['minutes_after_departure'] ?? '30';
        $buffer_time_vfr = intval($_POST['buffer_time_vfr'] ?? 15);
        $buffer_time_ifr = intval($_POST['buffer_time_ifr'] ?? 30);
        $climb_rate_vfr = intval($_POST['climb_rate_vfr'] ?? 800);
        $climb_rate_ifr = intval($_POST['climb_rate_ifr'] ?? 1800);
        $climb_speed_knots = intval($_POST['climb_speed_knots'] ?? 250);

        list($result, $error) = processFormSubmission(
            $icao_dep, $icao_arr, $aircraft, $cruise_altitude, $local_departure_time, $flight_mode, $latest_arrival_time, $minutes_before, $hours_after, $minutes_after, $buffer_time_vfr, $buffer_time_ifr, $climb_rate_vfr, $climb_rate_ifr, $climb_speed_knots, $is_next_leg, $calculated_next_departure, $local_departure_time, $saved_prefs
        );
        if ($result) $result['is_next_leg_call'] = intval($is_next_leg);
    }

    // Save preferences after processing
    // Only save local_departure_time if it was manually entered (not from next_leg auto-calculation)
    if (!$is_next_leg || isset($_POST['new_day_flag'])) {
        $saved_prefs['local_departure_time'] = $local_departure_time;
    }
    
    $saved_prefs['latest_arrival_time'] = $latest_arrival_time;
    $saved_prefs['minutes_before_departure'] = $minutes_before;
    $saved_prefs['hours_after_departure'] = $hours_after;
    $saved_prefs['minutes_after_departure'] = $minutes_after;
    $saved_prefs['turnaround_time_mach'] = $turnaround_time_mach;
    $saved_prefs['turnaround_time_knots'] = $turnaround_time_knots;
    $saved_prefs['climb_speed_knots'] = $climb_speed_knots;
    $saved_prefs['buffer_time_vfr'] = $buffer_time_vfr;
    $saved_prefs['buffer_time_ifr'] = $buffer_time_ifr;
    $saved_prefs['climb_rate_vfr'] = $climb_rate_vfr;
    $saved_prefs['climb_rate_ifr'] = $climb_rate_ifr;
    savePreferences($prefs_file, $saved_prefs);

    // Save custom speed preferences if available
    $saved_prefs['custom_speed_mach'] = $_POST['custom_speed'] ?? $saved_prefs['custom_speed_mach'] ?? '0.8';
    $saved_prefs['custom_speed_ktas'] = $_POST['custom_speed'] ?? $saved_prefs['custom_speed_ktas'] ?? '250';
    $saved_prefs['custom_speed_type'] = $_POST['custom_speed_type'] ?? $saved_prefs['custom_speed_type'] ?? 'mach';
    savePreferences($prefs_file, $saved_prefs);
}

// Save defaults (non-POST case)
savePreferences($prefs_file, $saved_prefs);

// Make variables global for templates
global $lang, $saved_prefs, $calculated_next_departure, $next_leg_dep, $local_departure_time, $latest_arrival_time, $minutes_before, $hours_after, $minutes_after, $aircraft_list, $result, $error;

// Now include templates for HTML (after all PHP logic)
include 'templates/header.php';
include 'templates/form.php';
include 'templates/results.php';
include 'templates/footer.php';
?>
