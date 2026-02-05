<?php
// Include configuration, functions, data, and helpers
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/aircraft.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/preferences.php';

// Start session
session_start();

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

// Handle reset button
if (isset($_POST['reset'])) {
    if (file_exists($prefs_file)) {
        unlink($prefs_file);
    }
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
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
if (isset($_POST['next_leg_turnaround_time'])) {
    $next_leg_turnaround_time = intval($_POST['next_leg_turnaround_time']);
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
if ($is_next_leg && !isset($_POST['new_day_flag'])) {
    if (isset($_POST['next_leg_departure_time']) && isset($_POST['next_leg_turnaround_time'])) {
        $prev_arrival_utc = $_POST['next_leg_departure_time'];
        $turnaround = intval($_POST['next_leg_turnaround_time']);
        try {
            if (isset($_POST['next_leg_dep'])) {
                $next_dep_data = getAirportData($_POST['next_leg_dep']);
                if ($next_dep_data) {
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

// Use consolidated preference saving directly (adjusted after removing duplication)
$saved_prefs = savePreferences($prefs_file, $saved_prefs);
$local_departure_time = $saved_prefs['local_departure_time'] ?? '07:00';

// Process form submission (consolidated)
$result = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['icao_dep']) && !empty($_POST['icao_arr']) && !isset($_POST['next_leg'])) {
    $icao_dep = strtoupper(trim($_POST['icao_dep']));
    $icao_arr = strtoupper(trim($_POST['icao_arr']));
    $aircraft = $_POST['aircraft'];
    $cruise_altitude = intval($_POST['cruise_altitude']);
    $local_departure_time = trim($_POST['local_departure_time']);
    $flight_mode = isset($_POST['flight_mode']) ? $_POST['flight_mode'] : 'charter';
    $latest_arrival_time = isset($_POST['latest_arrival_time']) ? trim($_POST['latest_arrival_time']) : '23:55';
    $minutes_before = intval($_POST['minutes_before_departure']);
    $hours_after = floatval($_POST['hours_after_departure']);
    $minutes_after = isset($_POST['minutes_after_departure']) ? intval($_POST['minutes_after_departure']) : 30;
    $buffer_time_vfr = intval($_POST['buffer_time_vfr']);
    $buffer_time_ifr = intval($_POST['buffer_time_ifr']);
    $climb_rate_vfr = intval($_POST['climb_rate_vfr']);
    $climb_rate_ifr = intval($_POST['climb_rate_ifr']);
    $climb_speed_knots = intval($_POST['climb_speed_knots']);
    
    // Preferences auto-saved via functions
    list($result, $error) = processFormSubmission(
    $icao_dep, $icao_arr, $aircraft, $cruise_altitude, $local_departure_time, $flight_mode, $latest_arrival_time, $minutes_before, $hours_after, $minutes_after, $buffer_time_vfr, $buffer_time_ifr, $climb_rate_vfr, $climb_rate_ifr, $climb_speed_knots, $is_next_leg, $calculated_next_departure, $local_departure_time, $saved_prefs
    );
    if ($result) $result['is_next_leg_call'] = intval($is_next_leg);
}

// Make variables global for templates
global $lang, $saved_prefs, $calculated_next_departure, $next_leg_dep, $local_departure_time, $latest_arrival_time, $minutes_before, $hours_after, $minutes_after, $aircraft_list, $result, $error;

// Now include templates for HTML (after all PHP logic)
include 'templates/header.php';
include 'templates/form.php';
include 'templates/results.php';
include 'templates/footer.php';
?>
