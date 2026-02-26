<?php
declare(strict_types=1);

/**
 * Compute mean along-track tailwind (knots) along the route using the annual wind climatology.
 * Samples N interior points along a simple linear lat/lon interpolation (fast, good first-order).
 *
 * Returns float tailwind in knots (positive = tailwind). If no samples usable, returns 0.
 */
function meanTailwindKtAlongRoute(array $windClimo, float $depLat, float $depLon, float $arrLat, float $arrLon, float $cruiseAltitudeFeet, int $samples = 12): float {
    $fl = $cruiseAltitudeFeet / 100.0;

    $lat1Rad = deg2rad($depLat);
    $lat2Rad = deg2rad($arrLat);
    $lon1Rad = deg2rad($depLon);
    $lon2Rad = deg2rad($arrLon);

    $deltaLon = $lon2Rad - $lon1Rad;
    if ($deltaLon > M_PI)  $deltaLon -= 2 * M_PI;
    if ($deltaLon < -M_PI) $deltaLon += 2 * M_PI;
    $y = sin($deltaLon) * cos($lat2Rad);
    $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($deltaLon);
    $trackDeg = rad2deg(atan2($y, $x));
    if ($trackDeg < 0) $trackDeg += 360.0;

    $sum = 0.0;
    $cnt = 0;

    // Great-circle interpolation (slerp) between endpoints
    $sinLat1 = sin($lat1Rad);
    $cosLat1 = cos($lat1Rad);
    $sinLat2 = sin($lat2Rad);
    $cosLat2 = cos($lat2Rad);

    $x1 = $cosLat1 * cos($lon1Rad);
    $y1 = $cosLat1 * sin($lon1Rad);
    $z1 = $sinLat1;

    $x2 = $cosLat2 * cos($lon2Rad);
    $y2 = $cosLat2 * sin($lon2Rad);
    $z2 = $sinLat2;

    $dot = $x1 * $x2 + $y1 * $y2 + $z1 * $z2;
    if ($dot > 1.0) $dot = 1.0;
    if ($dot < -1.0) $dot = -1.0;

    $d = acos($dot);
    $sinD = sin($d);

    for ($i = 1; $i <= $samples; $i++) {
        $t = $i / ($samples + 1);

        if ($sinD < 1e-12) {
            $lat = $depLat;
            $lon = $depLon;
        } else {
            $A = sin((1.0 - $t) * $d) / $sinD;
            $B = sin($t * $d) / $sinD;

            $x = $A * $x1 + $B * $x2;
            $y = $A * $y1 + $B * $y2;
            $z = $A * $z1 + $B * $z2;

            $lat = rad2deg(atan2($z, sqrt($x * $x + $y * $y)));
            $lon = rad2deg(atan2($y, $x));
        }

        while ($lon < 0) $lon += 360.0;
        while ($lon >= 360.0) $lon -= 360.0;

        $uv = wind_uv_at_fl($windClimo, $lat, $lon, $fl);
        if ($uv === null) continue;

        [$u_ms, $v_ms] = $uv;
        $tail_ms = tailwind_ms($u_ms, $v_ms, $trackDeg);
        $sum += ms_to_kt($tail_ms);
        $cnt += 1;
    }

    return ($cnt > 0) ? ($sum / $cnt) : 0.0;
}

function calculateFlightDetails(
    $distance,
    $cruise_speed,
    $speed_type,
    $cruise_altitude,
    $buffer_time_knots,
    $buffer_time_mach,
    $dep_lat = null,
    $dep_lon = null,
    $arr_lat = null,
    $arr_lon = null,
    $aircraft_data = null,
    $windClimo = null,
    $short_haul = 14.3,
    $medium_haul = 7.3,
    $long_haul = 4.8,
    $ultra_long_haul = 2.0,
    $cruise_range_corr = null,
    $arr_elevation_ft = 0.0,
    $dep_elevation_ft = 0.0
) {

    $cruise_speed_tas = ($speed_type === 'mach') ? machToTAS($cruise_speed, $cruise_altitude) : $cruise_speed;
    $buffer_time = ($speed_type === 'mach') ? $buffer_time_mach : $buffer_time_knots;

    // === Distance interpolation ===
    $DEF = fsa_defaults();
    $bp1 = (float)$DEF['haul_bp1_nm']; // 540
    $bp2 = (float)$DEF['haul_bp2_nm']; // 3000
    $bp3 = (float)$DEF['haul_bp3_nm']; // 6000

    if ($distance < $bp1) {
        // 0–bp1: short -> medium
        $distance_percentage = $short_haul - ($short_haul - $medium_haul) * ($distance / $bp1);

    } elseif ($distance <= $bp2) {
        // bp1–bp2: medium -> long
        $distance_percentage = $medium_haul - ($medium_haul - $long_haul) * ($distance - $bp1) / ($bp2 - $bp1);

    } elseif ($distance <= $bp3) {
        // bp2–bp3: long -> ultra
        $t = ($distance - $bp2) / ($bp3 - $bp2);
        if ($t < 0) $t = 0;
        if ($t > 1) $t = 1;

        $distance_percentage = $long_haul - ($long_haul - $ultra_long_haul) * $t;

    } else {
        // > bp3
        $distance_percentage = $ultra_long_haul;
    }

    // --- CruiseRange-based correction of detour % (percentage points) ---
    $cruise_range_pp_applied = 0.0;
    $cruise_range_nm_used = null;
    $cruise_range_bucket = null;

    if (is_array($cruise_range_corr) && ($cruise_range_corr['enabled'] ?? '0') === '1') {
        if ($aircraft_data && isset($aircraft_data['cruiseRange']) && is_numeric($aircraft_data['cruiseRange'])) {
            $r = (float)$aircraft_data['cruiseRange'];
            $cruise_range_nm_used = $r;

            $thr1 = (float)($cruise_range_corr['thr1_nm'] ?? 2000);
            $thr2 = (float)($cruise_range_corr['thr2_nm'] ?? 6000);
            $thr3 = (float)($cruise_range_corr['thr3_nm'] ?? 8000);

            $pp_lt = (float)($cruise_range_corr['pp_lt_thr1'] ?? -0.2);
            $pp_12 = (float)($cruise_range_corr['pp_thr1_thr2'] ?? 0.0);
            $pp_23 = (float)($cruise_range_corr['pp_thr2_thr3'] ?? 0.6);
            $pp_ge = (float)($cruise_range_corr['pp_ge_thr3'] ?? 1.5);

            $pp = 0.0;
            if ($r < $thr1) {
                $pp = $pp_lt;
                $cruise_range_bucket = 'lt_thr1';
            } elseif ($r < $thr2) {
                $pp = $pp_12;
                $cruise_range_bucket = 'thr1_thr2';
            } elseif ($r < $thr3) {
                $pp = $pp_23;
                $cruise_range_bucket = 'thr2_thr3';
            } else {
                $pp = $pp_ge;
                $cruise_range_bucket = 'ge_thr3';
            }

            $cruise_range_pp_applied = (float)$pp;

            $distance_percentage = (float)$distance_percentage + $pp;
            if ($distance_percentage < 0.0) $distance_percentage = 0.0;
        }
    }

    $extra_distance = $distance * ($distance_percentage / 100);
    $adjusted_distance = $distance + $extra_distance;

    // === Compute cruise altitude ===
    if ($dep_lat !== null && $dep_lon !== null && $arr_lat !== null && $arr_lon !== null) {
        $aircraft_ceiling_fl = ($aircraft_data && isset($aircraft_data['cruiseCeiling']))
            ? (int)$aircraft_data['cruiseCeiling']
            : 390;

        $display_altitude = getOptimizedFlightLevel(
            $adjusted_distance,
            $dep_lat, $dep_lon,
            $arr_lat, $arr_lon,
            $aircraft_ceiling_fl
        );
    } else {
        $display_altitude = $cruise_altitude;
    }

    // === Enforce minimum cruise time at final cruise level ===
    $min_final_cruise_min = 5;

    if (!is_array($aircraft_data)) {
        return ['error_key' => 'error_aircraft_profile_incomplete'];
    }

    $isValidProfileForAltitude = function (float $altitudeFt) use ($aircraft_data, $dep_elevation_ft, $arr_elevation_ft): bool {
        $requiredKeys = [
            'initialClimbIAS',
            'initialClimbROC',
            'approachROD',
            'approachIAS',
        ];

        // Climb phases depend on how high we climb above departure elevation
        if ((float)$altitudeFt > max(5000.0, (float)$dep_elevation_ft)) {
            $requiredKeys[] = 'climb150IAS';
            $requiredKeys[] = 'climb150ROC';
        }

        if ((float)$altitudeFt > max(15000.0, (float)$dep_elevation_ft)) {
            $requiredKeys[] = 'climb240IAS';
            $requiredKeys[] = 'climb240ROC';
        }

        if ((float)$altitudeFt > max(24000.0, (float)$dep_elevation_ft)) {
            $requiredKeys[] = 'machClimbROC';
        }

        // Descent phases depend on how high we descend from
        if ((float)$altitudeFt > 24000.0) {
            $requiredKeys[] = 'initialDescentROD';
        }

        if (min(24000.0, (float)$altitudeFt) > max(10000.0, (float)$arr_elevation_ft)) {
            $requiredKeys[] = 'descentROD';
            $requiredKeys[] = 'descentIAS';
        }

        foreach ($requiredKeys as $k) {
            if (!isset($aircraft_data[$k]) || !is_numeric($aircraft_data[$k]) || (float)$aircraft_data[$k] <= 0) {
                return false;
            }
        }

        return true;
    };

    // Kept for backwards compatibility in return payload; not used when aircraft profile is present.
    $climb_rate = 0.0;

    $guard = 0;
    while ($guard < 20) {
        if (!$isValidProfileForAltitude((float)$display_altitude)) {
            return ['error_key' => 'error_aircraft_profile_incomplete'];
        }

        $flight_time_candidate = calculateFlightTime(
            $adjusted_distance,
            $cruise_speed_tas,
            $cruise_speed,
            $display_altitude,
            $climb_rate,
            $aircraft_data,
            $arr_elevation_ft,
            $dep_elevation_ft
        );

        [$climbMin, $descentMin] = estimateClimbDescentMinutes((float)$display_altitude, $aircraft_data, (float)$arr_elevation_ft, (float)$dep_elevation_ft);
        $final_cruise_min = $flight_time_candidate - $climbMin - $descentMin;

        if ($final_cruise_min >= $min_final_cruise_min) {
            $flight_time = $flight_time_candidate;
            break;
        }

        $isEastbound_tmp = true;
        if ($dep_lat !== null && $dep_lon !== null && $arr_lat !== null && $arr_lon !== null) {
            $lat1Rad = deg2rad($dep_lat);
            $lat2Rad = deg2rad($arr_lat);
            $lon1Rad = deg2rad($dep_lon);
            $lon2Rad = deg2rad($arr_lon);
            $dLon = $lon2Rad - $lon1Rad;
            if ($dLon > M_PI)  $dLon -= 2 * M_PI;
            if ($dLon < -M_PI) $dLon += 2 * M_PI;
            $yy = sin($dLon) * cos($lat2Rad);
            $xx = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($dLon);
            $brg = rad2deg(atan2($yy, $xx));
            if ($brg < 0) $brg += 360.0;
            $isEastbound_tmp = ($brg >= 0.0 && $brg < 180.0);
        }

        $fl = (int)round(((float)$display_altitude) / 100.0);

        if ($fl > 180) {
            $nextFl = pick_next_lower_rvsm_fl($fl, $isEastbound_tmp);
            if ($nextFl >= $fl) {
                $flight_time = $flight_time_candidate;
                break;
            }

            $display_altitude = $nextFl * 100;
            $guard++;
            continue;
        }

        // Below (and including) FL180: use the semi-circle rule altitudes in feet.
        // Eastbound: odd thousands + 500 (e.g., 3,500; 5,500; ... 17,500)
        // Westbound: even thousands + 500 (e.g., 4,500; 6,500; ... 16,500)
        $semiCircleAlts = $isEastbound_tmp
            ? [17500, 15500, 13500, 11500, 9500, 7500, 5500, 3500]
            : [16500, 14500, 12500, 10500, 8500, 6500, 4500];

        $nextAltFt = 0.0;
        foreach ($semiCircleAlts as $altFt) {
            if ((float)$altFt < (float)$display_altitude) {
                $nextAltFt = (float)$altFt;
                break;
            }
        }

        if ($nextAltFt <= 0.0) {
            $flight_time = $flight_time_candidate;
            break;
        }

        $display_altitude = $nextAltFt;
        $guard++;
    }

    if (!isset($flight_time)) {
        $flight_time = calculateFlightTime($adjusted_distance, $cruise_speed_tas, $cruise_speed, $display_altitude, $climb_rate, $aircraft_data, $arr_elevation_ft, $dep_elevation_ft);
    }

    $mean_tailwind_kt = 0.0;

    // --- Wind correction applied only to cruise portion ---

    if ($windClimo && $dep_lat !== null && $dep_lon !== null && $arr_lat !== null && $arr_lon !== null && $aircraft_data !== null) {
        [$climbMin, $descentMin] = estimateClimbDescentMinutes((float)$display_altitude, $aircraft_data, (float)$arr_elevation_ft, (float)$dep_elevation_ft);

        $cruiseSpeedTAS = $cruise_speed_tas;

        $climbSpeed = $cruiseSpeedTAS * 0.70;
        $descentSpeed = $cruiseSpeedTAS * 0.80;

        $climbDistance = ($climbMin / 60) * $climbSpeed;
        $descentDistance = ($descentMin / 60) * $descentSpeed;

        $cruiseDistance = max(0, $adjusted_distance - $climbDistance - $descentDistance);

        if ($cruiseDistance > 0) {
            $samples = (int)round($adjusted_distance / 150.0);
            if ($samples < 12) $samples = 12;
            if ($samples > 60) $samples = 60;

            $meanTailKt = meanTailwindKtAlongRoute($windClimo, $dep_lat, $dep_lon, $arr_lat, $arr_lon, $display_altitude, $samples);

            $mean_tailwind_kt = (float)$meanTailKt;

            $gsWithWind = $cruiseSpeedTAS + $meanTailKt;
            if ($gsWithWind < 50) $gsWithWind = 50;

            $cruiseTimeNoWind = ($cruiseDistance / $cruiseSpeedTAS) * 60.0;
            $cruiseTimeWithWind = ($cruiseDistance / $gsWithWind) * 60.0;

            $windTimeDelta = $cruiseTimeWithWind - $cruiseTimeNoWind;
            $flight_time = $flight_time + $windTimeDelta;
        }
    }

    return [
        'cruise_speed_tas' => $cruise_speed_tas,
        'flight_time' => $flight_time,
        'buffer_time' => $buffer_time,
        'climb_rate' => $climb_rate,
        'distance_percentage' => $distance_percentage,
        'adjusted_distance' => $adjusted_distance,
        'extra_distance' => $extra_distance,
        'optimized_altitude' => $display_altitude,
        'mean_tailwind_kt' => $mean_tailwind_kt,

        // CruiseRange correction transparency (so results.php can display it)
        'cruise_range_pp_applied' => $cruise_range_pp_applied,
        'cruise_range_nm_used' => $cruise_range_nm_used,
        'cruise_range_bucket' => $cruise_range_bucket,
    ];
}
