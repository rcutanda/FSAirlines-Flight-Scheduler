<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/aircrafts/aircraft.php';
require_once __DIR__ . '/preferences.php';
require_once __DIR__ . '/wind/wind_climo.php';
require_once __DIR__ . '/src/bootstrap.php';

/* --------------------------------------------------------------
   Load wind climatology
   -------------------------------------------------------------- */
$WIND_CLIMO = null;

/* --------------------------------------------------------------
   Language
   -------------------------------------------------------------- */
$langResult = handleLanguageSelection();
$lang = $langResult['lang'];
$current_language = $langResult['current_language'];

/* --------------------------------------------------------------
   Request handling (build ViewModel)
   -------------------------------------------------------------- */
$vm = handleHttpRequest($lang, $aircraft_list, $WIND_CLIMO);

/* --------------------------------------------------------------
   Unpack ViewModel (keeps templates unchanged)
   -------------------------------------------------------------- */
$icao_dep = $vm['icao_dep'];

$flight_mode = $vm['flight_mode'];
$is_next_leg = $vm['is_next_leg'];
$next_leg_dep = $vm['next_leg_dep'];

$local_departure_time = $vm['local_departure_time'];
$latest_departure_time = $vm['latest_departure_time'];
$minutes_before = $vm['minutes_before'];
$hours_after = $vm['hours_after'];

$turnaround_time_mach = $vm['turnaround_time_mach'];
$turnaround_time_knots = $vm['turnaround_time_knots'];

$buffer_time_knots = $vm['buffer_time_knots'];
$buffer_time_mach = $vm['buffer_time_mach'];

$short_haul = $vm['short_haul'];
$medium_haul = $vm['medium_haul'];
$long_haul = $vm['long_haul'];
$ultra_long_haul = $vm['ultra_long_haul'];

$cruise_range_corr_enabled = $vm['cruise_range_corr_enabled'];
$cruise_range_thr1_nm = $vm['cruise_range_thr1_nm'];
$cruise_range_thr2_nm = $vm['cruise_range_thr2_nm'];
$cruise_range_thr3_nm = $vm['cruise_range_thr3_nm'];
$cruise_range_pp_lt_thr1 = $vm['cruise_range_pp_lt_thr1'];
$cruise_range_pp_thr1_thr2 = $vm['cruise_range_pp_thr1_thr2'];
$cruise_range_pp_thr2_thr3 = $vm['cruise_range_pp_thr2_thr3'];
$cruise_range_pp_ge_thr3 = $vm['cruise_range_pp_ge_thr3'];

$cruise_range_corr_sanitized = $vm['cruise_range_corr_sanitized'];
$cruise_range_corr_sanitized_msg = $vm['cruise_range_corr_sanitized_msg'];

$is_new_day = $vm['is_new_day'];

$form_icao_arr_value = $vm['form_icao_arr_value'];
$form_flight_mode = $vm['form_flight_mode'];
$form_aircraft = $vm['form_aircraft'];

$ui_show_schedule_new_day = $vm['ui_show_schedule_new_day'];
$ui_default_local_dep_time = $vm['ui_default_local_dep_time'];

$vm_turnaround_time = $vm['vm_turnaround_time'];

$result = $vm['result'];
$error = $vm['error'];

/* --------------------------------------------------------------
   Routing flags
   -------------------------------------------------------------- */
$show_add_aircraft = (isset($_GET['add_aircraft']) && $_GET['add_aircraft'] === '1');
$show_edit_aircraft = (isset($_GET['edit_aircraft']) && $_GET['edit_aircraft'] === '1');

/* --------------------------------------------------------------
   Render
   -------------------------------------------------------------- */
include 'templates/header.php';

if ($show_add_aircraft) {
	include __DIR__ . '/aircrafts/add_aircraft.php';
} elseif ($show_edit_aircraft) {
	include __DIR__ . '/aircrafts/edit_aircraft.php';
} else {
	include 'templates/form.php';
	include 'templates/results.php';
}

include 'templates/footer.php';
