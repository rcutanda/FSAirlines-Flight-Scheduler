<?php
declare(strict_types=1);

function machToTAS($mach, $altitude) {
    $T0 = 288.15; // K (sea level ISA)
    $lapse = 0.0065; // K/m (troposphere lapse rate)

    $altitude_m = $altitude * 0.3048;

    // ISA model: temperature decreases with altitude up to the tropopause (~11,000 m),
    // then stays constant (216.65 K) above.
    if ($altitude_m <= 11000.0) {
        $T = $T0 - ($lapse * $altitude_m);
    } else {
        $T = 216.65; // K (ISA tropopause temperature)
    }

    $a0 = 661.47; // knots (speed of sound at sea level ISA)
    $a = $a0 * sqrt($T / $T0);

    $tas = $mach * $a;
    return $tas;
}

function estimateClimbDescentMinutes(float $cruiseAltitudeFt, ?array $aircraftData): array {
    // Returns [climbMin, descentMin]
    if (!$aircraftData || !isset($aircraftData['initialClimbROC'])) {
        $climb = $cruiseAltitudeFt / 1500.0;
        $descent = $cruiseAltitudeFt / 1500.0;
        return [$climb, $descent];
    }

    // --- CLIMB ---
    $phase1_time = ($aircraftData['initialClimbROC'] > 0) ? (5000.0 / $aircraftData['initialClimbROC']) : 0.0;
    $phase2_time = ($aircraftData['climb150ROC'] > 0) ? (10000.0 / $aircraftData['climb150ROC']) : 0.0;
    $phase3_time = ($aircraftData['climb240ROC'] > 0) ? (9000.0 / $aircraftData['climb240ROC']) : 0.0;
    $phase4_alt = max(0.0, $cruiseAltitudeFt - 24000.0);
    $phase4_time = (isset($aircraftData['machClimbROC']) && $aircraftData['machClimbROC'] > 0) ? ($phase4_alt / $aircraftData['machClimbROC']) : 0.0;
    $climbMin = $phase1_time + $phase2_time + $phase3_time + $phase4_time;

    // --- DESCENT ---
    $desc1_alt = max(0.0, $cruiseAltitudeFt - 24000.0);
    $desc1_time = (isset($aircraftData['initialDescentROD']) && $aircraftData['initialDescentROD'] > 0) ? ($desc1_alt / $aircraftData['initialDescentROD']) : 0.0;
    $desc2_time = (isset($aircraftData['descentROD']) && $aircraftData['descentROD'] > 0) ? (14000.0 / $aircraftData['descentROD']) : 0.0;
    $desc3_time = (isset($aircraftData['approachROD']) && $aircraftData['approachROD'] > 0) ? (10000.0 / $aircraftData['approachROD']) : 0.0;
    $descentMin = $desc1_time + $desc2_time + $desc3_time;

    return [$climbMin, $descentMin];
}

// Function to calculate flight time with detailed climb profile optimization
function calculateFlightTime($distance, $cruiseSpeedTAS, $cruiseMACH, $cruiseAltitude, $climbSpeedKnots, $aircraftData = null) {
    $optimal_altitude = $cruiseAltitude;

    $climbTime = 0;
    $descentTime = 0;

    if ($aircraftData && isset($aircraftData['initialClimbROC'])) {

        // Phase 1: 0 -> 5,000
        $phase1_altitude = 5000;
        $ias_phase1 = $aircraftData['initialClimbIAS'];
        $roc_phase1 = $aircraftData['initialClimbROC'];
        $phase1_time = ($roc_phase1 > 0) ? ($phase1_altitude / $roc_phase1) : 0;

        // Phase 2: 5,000 -> 15,000
        $phase2_altitude = 10000;
        $ias_phase2 = $aircraftData['climb150IAS'];
        $roc_phase2 = $aircraftData['climb150ROC'];
        $phase2_time = ($roc_phase2 > 0) ? ($phase2_altitude / $roc_phase2) : 0;

        // Phase 3: 15,000 -> 24,000
        $phase3_altitude = 9000;
        $ias_phase3 = $aircraftData['climb240IAS'];
        $roc_phase3 = $aircraftData['climb240ROC'];
        $phase3_time = ($roc_phase3 > 0) ? ($phase3_altitude / $roc_phase3) : 0;

        // Phase 4: 24,000 -> cruise
        $phase4_altitude = $optimal_altitude - 24000;
        if ($phase4_altitude > 0 && isset($aircraftData['machClimbROC']) && $aircraftData['machClimbROC'] > 0) {
            $roc_phase4 = $aircraftData['machClimbROC'];
            $phase4_time = $phase4_altitude / $roc_phase4;
        } else {
            $phase4_time = 0;
        }

        $climbTime = $phase1_time + $phase2_time + $phase3_time + $phase4_time;

        // Descent Phase 1: cruise -> 24,000
        $desc_phase1_altitude = $optimal_altitude - 24000;
        if ($desc_phase1_altitude > 0 && isset($aircraftData['initialDescentROD']) && $aircraftData['initialDescentROD'] > 0) {
            $rod_phase1 = $aircraftData['initialDescentROD'];
            $desc_phase1_time = $desc_phase1_altitude / $rod_phase1;
        } else {
            $desc_phase1_time = 0;
        }

        // Descent Phase 2: 24,000 -> 10,000
        $desc_phase2_altitude = 14000;
        if (isset($aircraftData['descentROD']) && $aircraftData['descentROD'] > 0) {
            $rod_phase2 = $aircraftData['descentROD'];
            $desc_phase2_time = $desc_phase2_altitude / $rod_phase2;
        } else {
            $desc_phase2_time = 0;
        }

        // Descent Phase 3: 10,000 -> 0
        $desc_phase3_altitude = 10000;
        if (isset($aircraftData['approachROD']) && $aircraftData['approachROD'] > 0) {
            $rod_phase3 = $aircraftData['approachROD'];
            $desc_phase3_time = $desc_phase3_altitude / $rod_phase3;
        } else {
            $desc_phase3_time = 0;
        }

        $descentTime = $desc_phase1_time + $desc_phase2_time + $desc_phase3_time;

        // Distances for each phase
        $phase1_distance = ($ias_phase1 / 60) * $phase1_time;
        $phase2_distance = ($ias_phase2 / 60) * $phase2_time;
        $phase3_distance = ($ias_phase3 / 60) * $phase3_time;
        $phase4_distance = ($cruiseSpeedTAS / 60) * $phase4_time;
        $climbDistance = $phase1_distance + $phase2_distance + $phase3_distance + $phase4_distance;

        $desc_phase1_distance = ($cruiseSpeedTAS / 60) * $desc_phase1_time;
        $desc_phase2_distance = ($aircraftData['descentIAS'] / 60) * $desc_phase2_time;
        $desc_phase3_distance = ($aircraftData['approachIAS'] / 60) * $desc_phase3_time;
        $descentDistance = $desc_phase1_distance + $desc_phase2_distance + $desc_phase3_distance;

    } else {
        // Default calculation if no detailed aircraft data
        $climbTime = $optimal_altitude / $climbSpeedKnots;
        $descentTime = $optimal_altitude / $climbSpeedKnots;
        $climbDistance = $optimal_altitude / 60;
        $descentDistance = $optimal_altitude / 60;
    }

    $cruiseDistance = $distance - $climbDistance - $descentDistance;

    if ($cruiseDistance < 0) {
        $totalTime = ($distance / $cruiseSpeedTAS) * 60;
    } else {
        $cruiseTime = ($cruiseDistance / $cruiseSpeedTAS) * 60;
        $totalTime = $climbTime + $cruiseTime + $descentTime;
    }

    return $totalTime;
}
