<?php
// FSAirlines API configuration
define('FSA_API_URL', 'http://www.fsairlines.net/va_interface2.php');
define('FSA_VA_ID', 'ADD_HERE_YOUR_AIRLINE_ID');
define('FSA_API_KEY', 'ADD_HERE_YOUR_API_KEY');
define('VERSION', 'v2.0.1');

/**
 * Single source of truth for ALL defaults used by PHP + templates + JS.
 */
function fsa_defaults(): array {
    return [
        // UI / form defaults
        'local_departure_time' => '07:00',
        'latest_departure_time' => '23:00',
        'minutes_before_departure' => '30',
        'flight_mode' => 'charter',

        // Mode-dependent defaults
        'hours_after_departure_charter' => '16',
        'hours_after_departure_daily_schedule' => '1',

        // Buffers (minutes)
        'buffer_time_knots' => '0',
        'buffer_time_mach' => '0',

        // Turnaround (minutes)
        'turnaround_time_mach' => '60',
        'turnaround_time_knots' => '40',
        // JS uses a single "turnaround_time" default (kept for compatibility)
        'turnaround_time' => '60',

        // Detour / route uplift anchors (percent)
        'short_haul' => '22',
        'medium_haul' => '14',
        'long_haul' => '3.5',
        'ultra_long_haul' => '2.2',

        // Breakpoints for interpolation (NM)
        'haul_bp1_nm' => 540.0,
        'haul_bp2_nm' => 3000.0,
        'haul_bp3_nm' => 6000.0,

        // Storage key (JS)
        'pref_storage_key' => 'fsa_scheduler_prefs',

        // CruiseRange-based detour correction (percentage points applied AFTER interpolation)
        // Enable: '1' = enabled, '0' = disabled
        'cruise_range_corr_enabled' => '1',

        // Thresholds (NM) for cruiseRange bucketing (must be strictly increasing)
        'cruise_range_thr1_nm' => '3000',
        'cruise_range_thr2_nm' => '6000',
        'cruise_range_thr3_nm' => '8000',

        // Percentage-point adjustments by cruiseRange bucket
        // Example: +1.5 means add 1.5 percentage points to the interpolated detour %
        'cruise_range_pp_lt_thr1' => '-0.10',
        'cruise_range_pp_thr1_thr2' => '0.90',
        'cruise_range_pp_thr2_thr3' => '1.10',
        'cruise_range_pp_ge_thr3' => '1.30',
    ];
}