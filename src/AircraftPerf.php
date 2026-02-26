<?php
declare(strict_types=1);

function machToTAS($mach, $altitude) {
    $T0 = 288.15; // K (sea level ISA)
    $lapse = 0.0065; // K/m (troposphere lapse rate)

    // Convert geometric altitude (m) to geopotential altitude (m) for ISA layer calculations.
    $altitude_m = $altitude * 0.3048;
    $Re = 6356766.0; // m (ISA Earth radius used for geopotential altitude)
    $altitude_h = ($Re * $altitude_m) / ($Re + $altitude_m);

    // ISA model: temperature decreases with geopotential altitude up to the tropopause (11,000 m),
    // then stays constant (216.65 K) above.
    if ($altitude_h <= 11000.0) {
        $T = $T0 - ($lapse * $altitude_h);
    } else {
        $T = 216.65; // K (ISA tropopause temperature)
    }

    $a0 = 661.47; // knots (speed of sound at sea level ISA)
    $a = $a0 * sqrt($T / $T0);

    $tas = $mach * $a;
    return $tas;
}

function estimateClimbDescentMinutes(float $cruiseAltitudeFt, ?array $aircraftData, float $airportElevationFt = 0.0, float $departureElevationFt = 0.0): array {
    // Returns [climbMin, descentMin]
    if (!$aircraftData || !isset($aircraftData['initialClimbROC'])) {
        $climb = max(0.0, $cruiseAltitudeFt - $departureElevationFt) / 1500.0;
        $descent = max(0.0, $cruiseAltitudeFt - $airportElevationFt) / 1500.0;
        return [$climb, $descent];
    }

    // --- CLIMB ---
    $phase1_start_alt = min(5000.0, $departureElevationFt);
    $phase1_end_alt = min(5000.0, $cruiseAltitudeFt);
    $phase1_alt = max(0.0, $phase1_end_alt - $phase1_start_alt);
    $phase1_time = ($phase1_alt > 0 && $aircraftData['initialClimbROC'] > 0) ? ($phase1_alt / $aircraftData['initialClimbROC']) : 0.0;

    $phase2_start_alt = max(5000.0, $departureElevationFt);
    $phase2_end_alt = min(15000.0, $cruiseAltitudeFt);
    $phase2_alt = max(0.0, $phase2_end_alt - $phase2_start_alt);
    $phase2_time = ($phase2_alt > 0 && $aircraftData['climb150ROC'] > 0) ? ($phase2_alt / $aircraftData['climb150ROC']) : 0.0;

    $phase3_start_alt = max(15000.0, $departureElevationFt);
    $phase3_end_alt = min(24000.0, $cruiseAltitudeFt);
    $phase3_alt = max(0.0, $phase3_end_alt - $phase3_start_alt);
    $phase3_time = ($phase3_alt > 0 && $aircraftData['climb240ROC'] > 0) ? ($phase3_alt / $aircraftData['climb240ROC']) : 0.0;

    $phase4_start_alt = max(24000.0, $departureElevationFt);
    $phase4_alt = max(0.0, $cruiseAltitudeFt - $phase4_start_alt);
    $phase4_time = ($phase4_alt > 0 && isset($aircraftData['machClimbROC']) && $aircraftData['machClimbROC'] > 0) ? ($phase4_alt / $aircraftData['machClimbROC']) : 0.0;

    $climbMin = $phase1_time + $phase2_time + $phase3_time + $phase4_time;

    // --- DESCENT ---
    $desc1_alt = max(0.0, $cruiseAltitudeFt - 24000.0);
    $desc1_time = (isset($aircraftData['initialDescentROD']) && $aircraftData['initialDescentROD'] > 0) ? ($desc1_alt / $aircraftData['initialDescentROD']) : 0.0;

    // Phase 2: min(24,000, cruise) -> max(10,000, airport elevation)
    $desc2_end_alt = max(10000.0, $airportElevationFt);
    $desc2_alt = max(0.0, min(24000.0, $cruiseAltitudeFt) - $desc2_end_alt);
    $desc2_time = (isset($aircraftData['descentROD']) && $aircraftData['descentROD'] > 0) ? ($desc2_alt / $aircraftData['descentROD']) : 0.0;

    // Phase 3: min(max(10,000, airport elevation), cruise) -> airport elevation
    $desc3_start_alt = min($desc2_end_alt, $cruiseAltitudeFt);
    $desc3_alt = max(0.0, $desc3_start_alt - $airportElevationFt);
    $desc3_time = (isset($aircraftData['approachROD']) && $aircraftData['approachROD'] > 0) ? ($desc3_alt / $aircraftData['approachROD']) : 0.0;

    $descentMin = $desc1_time + $desc2_time + $desc3_time;

    return [$climbMin, $descentMin];
}

// Function to calculate flight time with detailed climb profile optimization
function calculateFlightTime($distance, $cruiseSpeedTAS, $cruiseMACH, $cruiseAltitude, $climbSpeedKnots, $aircraftData = null, $airportElevationFt = 0.0, $departureElevationFt = 0.0) {
    $optimal_altitude = $cruiseAltitude;

    // If cruise TAS is not provided, derive it from Mach at cruise altitude using ISA.
    if ((!is_numeric($cruiseSpeedTAS) || $cruiseSpeedTAS <= 0) && is_numeric($cruiseMACH) && $cruiseMACH > 0) {
        $cruiseSpeedTAS = machToTAS($cruiseMACH, $optimal_altitude);
    }

    $climbTime = 0;
    $descentTime = 0;

    if ($aircraftData && isset($aircraftData['initialClimbROC'])) {

        // Phase 1: departure elevation -> 5,000
        $phase1_altitude = max(0.0, 5000.0 - (float)$departureElevationFt);
        $ias_phase1 = $aircraftData['initialClimbIAS'];
        $roc_phase1 = $aircraftData['initialClimbROC'];
        $phase1_time = ($roc_phase1 > 0) ? ($phase1_altitude / $roc_phase1) : 0;

        // Phase 2: max(5,000, departure elevation) -> 15,000
        $phase2_start_alt = max(5000.0, (float)$departureElevationFt);
        $phase2_altitude = max(0.0, 15000.0 - $phase2_start_alt);
        $ias_phase2 = $aircraftData['climb150IAS'];
        $roc_phase2 = $aircraftData['climb150ROC'];
        $phase2_time = ($roc_phase2 > 0) ? ($phase2_altitude / $roc_phase2) : 0;

        // Phase 3: max(15,000, departure elevation) -> min(24,000, cruise altitude)
        $phase3_start_alt = max(15000.0, (float)$departureElevationFt);
        $phase3_end_alt = min(24000.0, (float)$optimal_altitude);
        $phase3_altitude = max(0.0, $phase3_end_alt - $phase3_start_alt);
        $ias_phase3 = $aircraftData['climb240IAS'];
        $roc_phase3 = $aircraftData['climb240ROC'];
        $phase3_time = ($roc_phase3 > 0) ? ($phase3_altitude / $roc_phase3) : 0;

        // Phase 4: max(24,000, departure elevation) -> cruise
        $phase4_start_alt = max(24000.0, (float)$departureElevationFt);
        $phase4_altitude = max(0.0, (float)$optimal_altitude - $phase4_start_alt);
        if ($phase4_altitude > 0 && isset($aircraftData['machClimbROC']) && $aircraftData['machClimbROC'] > 0) {
            $roc_phase4 = $aircraftData['machClimbROC'];
            $phase4_time = $phase4_altitude / $roc_phase4;
        } else {
            $phase4_time = 0;
        }

        $climbTime = $phase1_time + $phase2_time + $phase3_time + $phase4_time;

        // Descent Phase 1: cruise -> 24,000
        $desc_phase1_altitude = max(0.0, (float)$optimal_altitude - 24000.0);
        if ($desc_phase1_altitude > 0 && isset($aircraftData['initialDescentROD']) && $aircraftData['initialDescentROD'] > 0) {
            $rod_phase1 = $aircraftData['initialDescentROD'];
            $desc_phase1_time = $desc_phase1_altitude / $rod_phase1;
        } else {
            $desc_phase1_time = 0;
        }

        // Descent Phase 2: min(24,000, cruise) -> max(10,000, airport elevation)
        $desc_phase2_start_alt = min(24000.0, (float)$optimal_altitude);
        $desc_phase2_end_alt = max(10000.0, (float)$airportElevationFt);
        $desc_phase2_altitude = max(0.0, $desc_phase2_start_alt - $desc_phase2_end_alt);
        if ($desc_phase2_altitude > 0 && isset($aircraftData['descentROD']) && $aircraftData['descentROD'] > 0) {
            $rod_phase2 = $aircraftData['descentROD'];
            $desc_phase2_time = $desc_phase2_altitude / $rod_phase2;
        } else {
            $desc_phase2_time = 0;
        }

        // Descent Phase 3: min(max(10,000, airport elevation), cruise) -> airport elevation
        $desc_phase3_start_alt = min(max(10000.0, (float)$airportElevationFt), (float)$optimal_altitude);
        $desc_phase3_altitude = max(0.0, $desc_phase3_start_alt - (float)$airportElevationFt);
        if ($desc_phase3_altitude > 0 && isset($aircraftData['approachROD']) && $aircraftData['approachROD'] > 0) {
            $rod_phase3 = $aircraftData['approachROD'];
            $desc_phase3_time = $desc_phase3_altitude / $rod_phase3;
        } else {
            $desc_phase3_time = 0;
        }

        $descentTime = $desc_phase1_time + $desc_phase2_time + $desc_phase3_time;

        // Distances for each phase (times already adjusted for departure elevation)
        // Use an ISA-based IAS->TAS approximation for distance accounting.
        $isaDensityRatio = function (float $altitudeFt): float {
            $h = $altitudeFt * 0.3048; // meters
            $T0 = 288.15;
            $P0 = 101325.0;
            $L = 0.0065;
            $g = 9.80665;
            $R = 287.05;
            $h11 = 11000.0;
            $T11 = $T0 - $L * $h11;

            if ($h <= $h11) {
                $T = $T0 - $L * $h;
                $P = $P0 * pow($T / $T0, $g / ($R * $L));
            } else {
                $P11 = $P0 * pow($T11 / $T0, $g / ($R * $L));
                $P = $P11 * exp(-$g * ($h - $h11) / ($R * $T11));
                $T = $T11;
            }

            $sigma = ($P / $P0) / ($T / $T0);
            return max(0.01, (float)$sigma);
        };

        $phase1_start_alt_ft = (float)$departureElevationFt;
        $phase1_end_alt_ft = min(5000.0, (float)$optimal_altitude);
        $phase1_mid_alt_ft = ($phase1_start_alt_ft + $phase1_end_alt_ft) / 2.0;

        $phase2_start_alt_ft = max(5000.0, (float)$departureElevationFt);
        $phase2_end_alt_ft = min(15000.0, (float)$optimal_altitude);
        $phase2_mid_alt_ft = ($phase2_start_alt_ft + $phase2_end_alt_ft) / 2.0;

        $phase3_start_alt_ft = max(15000.0, (float)$departureElevationFt);
        $phase3_end_alt_ft = min(24000.0, (float)$optimal_altitude);
        $phase3_mid_alt_ft = ($phase3_start_alt_ft + $phase3_end_alt_ft) / 2.0;

        $desc_phase2_start_alt_ft = min(24000.0, (float)$optimal_altitude);
        $desc_phase2_end_alt_ft = max(10000.0, (float)$airportElevationFt);
        $desc_phase2_mid_alt_ft = ($desc_phase2_start_alt_ft + $desc_phase2_end_alt_ft) / 2.0;

        $desc_phase3_start_alt_ft = min($desc_phase2_end_alt_ft, (float)$optimal_altitude);
        $desc_phase3_end_alt_ft = (float)$airportElevationFt;
        $desc_phase3_mid_alt_ft = ($desc_phase3_start_alt_ft + $desc_phase3_end_alt_ft) / 2.0;

        $tas_phase1 = $ias_phase1 / sqrt($isaDensityRatio($phase1_mid_alt_ft));
        $tas_phase2 = $ias_phase2 / sqrt($isaDensityRatio($phase2_mid_alt_ft));
        $tas_phase3 = $ias_phase3 / sqrt($isaDensityRatio($phase3_mid_alt_ft));

        $tas_desc_phase2 = $aircraftData['descentIAS'] / sqrt($isaDensityRatio($desc_phase2_mid_alt_ft));
        $tas_desc_phase3 = $aircraftData['approachIAS'] / sqrt($isaDensityRatio($desc_phase3_mid_alt_ft));

        $phase1_distance = ($tas_phase1 / 60) * $phase1_time;
        $phase2_distance = ($tas_phase2 / 60) * $phase2_time;
        $phase3_distance = ($tas_phase3 / 60) * $phase3_time;
        $phase4_distance = ($cruiseSpeedTAS / 60) * $phase4_time;
        $climbDistance = $phase1_distance + $phase2_distance + $phase3_distance + $phase4_distance;

        $desc_phase1_distance = ($cruiseSpeedTAS / 60) * $desc_phase1_time;
        $desc_phase2_distance = ($tas_desc_phase2 / 60) * $desc_phase2_time;
        $desc_phase3_distance = ($tas_desc_phase3 / 60) * $desc_phase3_time;
        $descentDistance = $desc_phase1_distance + $desc_phase2_distance + $desc_phase3_distance;

    } else {
        // Default calculation if no detailed aircraft data
        // Use the same fallback model as estimateClimbDescentMinutes(): 1500 ft/min.
        [$climbTime, $descentTime] = estimateClimbDescentMinutes((float)$optimal_altitude, null, (float)$airportElevationFt);

        // Distances (NM) = (knots / 60) * minutes
        $climbDistance = ($climbSpeedKnots / 60) * $climbTime;
        $descentDistance = ($climbSpeedKnots / 60) * $descentTime;
    }

    $cruiseDistance = $distance - $climbDistance - $descentDistance;

    if ($cruiseDistance < 0) {
        $nonCruiseDistance = $climbDistance + $descentDistance;
        if ($nonCruiseDistance > 0) {
            $scale = $distance / $nonCruiseDistance;
            $totalTime = ($climbTime + $descentTime) * $scale;
        } else {
            $totalTime = ($distance / $cruiseSpeedTAS) * 60;
        }
    } else {
        $cruiseTime = ($cruiseDistance / $cruiseSpeedTAS) * 60;
        $totalTime = $climbTime + $cruiseTime + $descentTime;
    }

    return $totalTime;
}
