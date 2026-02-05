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
$lang = handleLanguageSelection($prefs_dir);

// Load preferences (function now in preferences.php)
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

// SAVE PREFERENCES IMMEDIATELY on POST (consolidated)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prefs_to_save = $saved_prefs;
    
    if (isset($_POST['aircraft'])) {
        $prefs_to_save['aircraft'] = $_POST['aircraft'];
    }
    if (isset($_POST['custom_speed'])) {
        $prefs_to_save['custom_speed'] = $_POST['custom_speed'];
    }
    if (isset($_POST['custom_speed_type'])) {
        $prefs_to_save['custom_speed_type'] = $_POST['custom_speed_type'];
    }
    if (isset($_POST['custom_speed_type'])) {
        $type = $_POST['custom_speed_type'];
        if (isset($_POST['custom_speed'])) {
            if ($type === 'mach') {
                $prefs_to_save['custom_speed_mach'] = $_POST['custom_speed'];
            } elseif ($type === 'ktas') {
                $prefs_to_save['custom_speed_ktas'] = $_POST['custom_speed'];
            }
        }
    }
    if (isset($_POST['custom_speed_mach'])) {
        $prefs_to_save['custom_speed_mach'] = $_POST['custom_speed_mach'];
    }
    if (isset($_POST['custom_speed_ktas'])) {
        $prefs_to_save['custom_speed_ktas'] = $_POST['custom_speed_ktas'];
    }
    if (isset($_POST['cruise_altitude'])) {
        $prefs_to_save['cruise_altitude'] = $_POST['cruise_altitude'];
    }
    if (isset($_POST['local_departure_time'])) {
        $prefs_to_save['local_departure_time'] = $_POST['local_departure_time'];
    }
    if (isset($_POST['flight_mode'])) {
        $prefs_to_save['flight_mode'] = $_POST['flight_mode'];
    }
    if (isset($_POST['latest_arrival_time'])) {
        $prefs_to_save['latest_arrival_time'] = $_POST['latest_arrival_time'];
    }
    if (isset($_POST['minutes_before_departure'])) {
        $prefs_to_save['minutes_before_departure'] = $_POST['minutes_before_departure'];
    }
    if (isset($_POST['hours_after_departure'])) {
        $prefs_to_save['hours_after_departure'] = $_POST['hours_after_departure'];
    }
    if (isset($_POST['minutes_after_departure'])) {
        $prefs_to_save['minutes_after_departure'] = $_POST['minutes_after_departure'];
    }
    if (isset($_POST['turnaround_time_input'])) {
        $turnaround_input_value = intval($_POST['turnaround_time_input']);
        if (isset($_POST['aircraft']) && $_POST['aircraft'] !== 'custom') {
            global $aircraft_list;
            $aircraft_data = $aircraft_list[$_POST['aircraft']];
            $current_speed_type = $aircraft_data['type'];
        } elseif (isset($_POST['custom_speed_type'])) {
            $current_speed_type = $_POST['custom_speed_type'];
        } else {
            $current_speed_type = 'mach';
        }
        
        if ($current_speed_type === 'mach') {
            $prefs_to_save['turnaround_time_mach'] = $turnaround_input_value;
        } else {
            $prefs_to_save['turnaround_time_knots'] = $turnaround_input_value;
        }
    }
    if (isset($_POST['turnaround_time_mach'])) {
        $prefs_to_save['turnaround_time_mach'] = $_POST['turnaround_time_mach'];
    }
    if (isset($_POST['turnaround_time_knots'])) {
        $prefs_to_save['turnaround_time_knots'] = $_POST['turnaround_time_knots'];
    }
    if (isset($_POST['buffer_time_vfr'])) {
        $prefs_to_save['buffer_time_vfr'] = $_POST['buffer_time_vfr'];
    }
    if (isset($_POST['buffer_time_ifr'])) {
        $prefs_to_save['buffer_time_ifr'] = $_POST['buffer_time_ifr'];
    }
    if (isset($_POST['climb_rate_vfr'])) {
        $prefs_to_save['climb_rate_vfr'] = $_POST['climb_rate_vfr'];
    }
    if (isset($_POST['climb_rate_ifr'])) {
        $prefs_to_save['climb_rate_ifr'] = $_POST['climb_rate_ifr'];
    }
    if (isset($_POST['climb_speed_knots'])) {
        $prefs_to_save['climb_speed_knots'] = $_POST['climb_speed_knots'];
    }
    
    savePreferences($prefs_file, $prefs_to_save);
    $saved_prefs = $prefs_to_save;
    $local_departure_time = $saved_prefs['local_departure_time'] ?? '07:00';
}

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
    
    savePreferences($prefs_file, $saved_prefs);
    
    list($result, $error) = processFormSubmission(
        $icao_dep, $icao_arr, $aircraft, $cruise_altitude, $local_departure_time, $flight_mode, $latest_arrival_time,
        $minutes_before, $hours_after, $minutes_after, $buffer_time_vfr, $buffer_time_ifr, $climb_rate_vfr, $climb_rate_ifr, $climb_speed_knots,
        $is_next_leg, $calculated_next_departure, $local_departure_time
    );
    if ($result) $result['is_next_leg_call'] = intval($is_next_leg);
}

// Now include templates for HTML (after all PHP logic)
include 'templates/header.php';
include 'templates/form.php';
include 'templates/results.php';
include 'templates/footer.php';
?>
