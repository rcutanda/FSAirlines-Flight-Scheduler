<?php
declare(strict_types=1);

function round_to_rvsm_fl(int $fl, bool $eastbound): int {
    // Clamp to RVSM-ish band
    if ($fl < 290) $fl = 290;
    if ($fl > 450) $fl = 450;

    $candidates = rvsm_candidates($eastbound);

    // Nearest candidate (ties -> round up)
    $best = $candidates[0];
    $bestDiff = PHP_INT_MAX;
    foreach ($candidates as $c) {
        $d = abs($c - $fl);
        if ($d < $bestDiff || ($d === $bestDiff && $c > $best)) {
            $best = $c;
            $bestDiff = $d;
        }
    }
    return $best;
}

function rvsm_candidates(bool $eastbound): array {
    // RVSM-style levels (extend to FL450 since your dataset includes it)
    return $eastbound
        ? [290, 310, 330, 350, 370, 390, 410, 430, 450]
        : [300, 320, 340, 360, 380, 400, 420, 440];
}

function pick_next_lower_rvsm_fl(int $fl, bool $eastbound): int {
    $cands = rvsm_candidates($eastbound);
    sort($cands, SORT_NUMERIC);

    $lower = $cands[0];
    foreach ($cands as $c) {
        if ($c < $fl) $lower = $c;
        else break;
    }
    return $lower;
}

// Calculate magnetic heading and apply semi-circle rule with aircraft ceiling constraint
function getOptimizedFlightLevel($distance, $lat1, $lon1, $lat2, $lon2, $aircraft_ceiling_fl) {
    // Bearing (deg TO)
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $lon1Rad = deg2rad($lon1);
    $lon2Rad = deg2rad($lon2);

    $deltaLon = $lon2Rad - $lon1Rad;

    $y = sin($deltaLon) * cos($lat2Rad);
    $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($deltaLon);
    $bearingDeg = rad2deg(atan2($y, $x));
    if ($bearingDeg < 0) $bearingDeg += 360.0;

    // Eastbound: 000–179; Westbound: 180–359
    $isEastbound = ($bearingDeg >= 0.0 && $bearingDeg < 180.0);

    // === Step 1: Ceiling adjustment for ISA+ conditions (−1500 ft = −15 FL) ===
    // Only apply this correction for realistic jet-level ceilings; for low ceilings it can drive values to 0.
    $ceiling_fl = (int)$aircraft_ceiling_fl;
    if ($ceiling_fl >= 150) {
        $ceiling_fl -= 15;
    }
    if ($ceiling_fl < 0) $ceiling_fl = 0;

    // Aim for (adjusted) ceiling and floor to nearest 10
    $max_fl = (int)(floor($ceiling_fl / 10) * 10);
    if ($max_fl < 0) $max_fl = 0;

    // If the aircraft ceiling is below RVSM band, use semicircle rule below FL290
    if ($max_fl < 290) {
        // Build candidates up to max_fl, using FL stepping of 20 (odd/even semicircle style)
        // Eastbound: 50, 70, 90, ... ; Westbound: 60, 80, 100, ...
        $start = $isEastbound ? 50 : 60;

        // If ceiling is very low, just return the ceiling itself (in feet) to avoid exceeding it
        if ($max_fl < $start) {
            return $max_fl * 100;
        }

        $best = $start;
        for ($fl = $start; $fl <= $max_fl; $fl += 20) {
            $best = $fl; // always keep the highest <= ceiling
        }

        return $best * 100;
    }

    // === RVSM band (FL290+) ===
    // Clamp target to RVSM-ish band, but NEVER above aircraft ceiling
    $optimal_fl = $max_fl;
    if ($optimal_fl > 450) $optimal_fl = 450;

    $candidates = $isEastbound
        ? [290, 310, 330, 350, 370, 390, 410, 430, 450]
        : [300, 320, 340, 360, 380, 400, 420, 440];

    // Nearest candidate (ties: round up)
    $best = $candidates[0];
    $bestDiff = PHP_INT_MAX;
    foreach ($candidates as $c) {
        $d = abs($c - $optimal_fl);
        if ($d < $bestDiff || ($d === $bestDiff && $c > $best)) {
            $best = $c;
            $bestDiff = $d;
        }
    }

    // Ensure we do not exceed ceiling after rounding
    while ($best > $max_fl) {
        $best = pick_next_lower_rvsm_fl($best, $isEastbound);
        if ($best <= 0) break;
    }

    return $best * 100; // return feet
}
