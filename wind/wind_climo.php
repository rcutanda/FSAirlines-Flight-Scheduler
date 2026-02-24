<?php
declare(strict_types=1);

/**
 * wind_climo.php
 *
 * For WindClimo_AVG_1958-2008_H15.php (and similar) files with:
 *  - lats: 90, 87.5, ..., -90   (edge-based)
 *  - lons: 0, 2.5, ..., 357.5   (edge-based, 0–360 convention)
 *  - u[level_hpa] and v[level_hpa] as flattened row-major arrays
 *  - grid order: row-major (lat desc, lon asc)
 *
 * Policy:
 *  - Bilinear interpolation ignores null corners and renormalizes.
 *  - Vertical interpolation returns null if outside available pressure-level range (Policy B).
 */

/**
 * Convert Flight Level (e.g. 340) to feet (e.g. 34,000 ft).
 */
function fl_to_feet(float $fl): float {
    return $fl * 100.0;
}

/**
 * ISA-based approximation: altitude (feet) -> pressure (hPa).
 */
function pressure_hpa_from_feet_isa(float $feet): float {
    $h = $feet * 0.3048; // meters

    // ISA constants
    $T0 = 288.15;     // K
    $p0 = 1013.25;    // hPa
    $g  = 9.80665;    // m/s^2
    $R  = 287.05;     // J/(kg*K)
    $L  = 0.0065;     // K/m
    $ht = 11000.0;    // m (tropopause)

    $Tt = $T0 - $L * $ht; // 216.65 K
    $pt = $p0 * pow(1.0 - ($L * $ht / $T0), $g / ($R * $L)); // ~226.32 hPa

    if ($h <= $ht) {
        return $p0 * pow(1.0 - ($L * $h / $T0), $g / ($R * $L));
    }
    return $pt * exp(-$g * ($h - $ht) / ($R * $Tt));
}

/**
 * Normalize longitude to [0, 360).
 */
function lon_norm_0_360(float $lon): float {
    $lon = fmod($lon, 360.0);
    if ($lon < 0) $lon += 360.0;
    if ($lon >= 360.0) $lon -= 360.0;
    return $lon;
}

/**
 * Clamp latitude to [-90, 90].
 */
function lat_clamp(float $lat): float {
    if ($lat > 90.0) return 90.0;
    if ($lat < -90.0) return -90.0;
    return $lat;
}

/**
 * Flattened index: row-major (lat desc, lon asc).
 */
function grid_idx(int $ilat, int $ilon, int $nlon): int {
    return $ilat * $nlon + $ilon;
}

/**
 * Bracket latitude index for edge-based axis:
 *   lats[i] = 90 - i*d
 */
function bracket_lat(float $lat, float $dlat, int $nlat): array {
    $t = (90.0 - $lat) / $dlat;
    $i0 = (int)floor($t);
    if ($i0 < 0) $i0 = 0;
    if ($i0 > $nlat - 2) $i0 = $nlat - 2;
    return [$i0, $i0 + 1];
}

/**
 * Bracket longitude index for edge-based axis:
 *   lons[j] = j*d    for j=0..(nlon-1), last is 357.5 when d=2.5
 */
function bracket_lon(float $lon, float $dlon, int $nlon): array {
    $t = $lon / $dlon;
    $j0 = (int)floor($t);
    if ($j0 < 0) $j0 = 0;
    if ($j0 > $nlon - 2) $j0 = $nlon - 2;
    return [$j0, $j0 + 1];
}

function lat_axis(int $i, float $dlat): float {
    return 90.0 - $i * $dlat;
}

function lon_axis(int $j, float $dlon): float {
    return $j * $dlon;
}

/**
 * Bilinear interpolation for one field ('u' or 'v') at a given pressure level.
 * Ignores null corners and renormalizes weights; returns null if all corners null.
 */
function bilinear_field_at(array $wind, int $level_hpa, float $lat, float $lon, string $field): ?float {
    if (!isset($wind['lats'], $wind['lons'], $wind[$field])) return null;

    $lats = $wind['lats'];
    $lons = $wind['lons'];

    if (!is_array($lats) || count($lats) < 2 || !is_array($lons) || count($lons) < 2) return null;

    // derive grid step from axis (expected 2.5)
    $dlat = abs((float)$lats[1] - (float)$lats[0]);
    $dlon = abs((float)$lons[1] - (float)$lons[0]);
    if ($dlat <= 0.0 || $dlon <= 0.0) return null;

    $gridMap = $wind[$field];
    $grid = $gridMap[$level_hpa] ?? null;
    if (!is_array($grid)) return null;

    $nlat = count($lats);
    $nlon = count($lons);

    $lat = lat_clamp($lat);
    $lon = lon_norm_0_360($lon);

    [$i0, $i1] = bracket_lat($lat, $dlat, $nlat);
    [$j0, $j1] = bracket_lon($lon, $dlon, $nlon);

    $lat0 = lat_axis($i0, $dlat);
    $lat1 = lat_axis($i1, $dlat);  // lat1 < lat0
    $lon0 = lon_axis($j0, $dlon);
    $lon1 = lon_axis($j1, $dlon);

    $dx = $lon1 - $lon0;
    $dy = $lat1 - $lat0;           // negative
    if ($dx == 0.0 || $dy == 0.0) return null;

    $tx = ($lon - $lon0) / $dx;    // 0..1
    $ty = ($lat - $lat0) / $dy;    // 0..1 (since dy < 0)

    // clamp fractions
    if ($tx < 0.0) $tx = 0.0; elseif ($tx > 1.0) $tx = 1.0;
    if ($ty < 0.0) $ty = 0.0; elseif ($ty > 1.0) $ty = 1.0;

    $Q11 = $grid[grid_idx($i0, $j0, $nlon)] ?? null;
    $Q21 = $grid[grid_idx($i0, $j1, $nlon)] ?? null;
    $Q12 = $grid[grid_idx($i1, $j0, $nlon)] ?? null;
    $Q22 = $grid[grid_idx($i1, $j1, $nlon)] ?? null;

    $w11 = (1.0 - $tx) * (1.0 - $ty);
    $w21 = ($tx) * (1.0 - $ty);
    $w12 = (1.0 - $tx) * ($ty);
    $w22 = ($tx) * ($ty);

    // Ignore nulls and renormalize
    $sum = 0.0;
    $wsum = 0.0;

    if ($Q11 !== null) { $sum += $w11 * (float)$Q11; $wsum += $w11; }
    if ($Q21 !== null) { $sum += $w21 * (float)$Q21; $wsum += $w21; }
    if ($Q12 !== null) { $sum += $w12 * (float)$Q12; $wsum += $w12; }
    if ($Q22 !== null) { $sum += $w22 * (float)$Q22; $wsum += $w22; }

    if ($wsum == 0.0) return null;
    return $sum / $wsum;
}

/**
 * Vertical interpolation between available pressure levels.
 * Policy B: return null if outside available level range.
 *
 * Returns [u_ms, v_ms] or null.
 */
function wind_uv_at_fl(array $wind, float $lat, float $lon, float $fl): ?array {
    if (!isset($wind['levels_hpa']) || !is_array($wind['levels_hpa']) || count($wind['levels_hpa']) < 2) {
        return null;
    }

    $feet = fl_to_feet($fl);
    $p = pressure_hpa_from_feet_isa($feet);

    // Ensure numeric and sorted descending (high pressure to low pressure)
    $levs = array_map('floatval', $wind['levels_hpa']);
    rsort($levs, SORT_NUMERIC); // e.g. 400,300,250,200,150

    $p_max = $levs[0];
    $p_min = $levs[count($levs) - 1];

    // Policy B
    if ($p > $p_max || $p < $p_min) {
        return null;
    }

    // Find bracket: p_low >= p >= p_high
    $p_low = null;
    $p_high = null;
    for ($k = 0; $k < count($levs) - 1; $k++) {
        $a = $levs[$k];
        $b = $levs[$k + 1];
        if ($a >= $p && $p >= $b) {
            $p_low = $a;
            $p_high = $b;
            break;
        }
    }
    if ($p_low === null || $p_high === null) return null;

    $u_low  = bilinear_field_at($wind, (int)$p_low,  $lat, $lon, 'u');
    $v_low  = bilinear_field_at($wind, (int)$p_low,  $lat, $lon, 'v');
    $u_high = bilinear_field_at($wind, (int)$p_high, $lat, $lon, 'u');
    $v_high = bilinear_field_at($wind, (int)$p_high, $lat, $lon, 'v');

    if ($u_low === null || $v_low === null || $u_high === null || $v_high === null) {
        return null;
    }

    // Interpolate in pressure
    $alpha = ($p - $p_low) / ($p_high - $p_low); // 0 at p_low, 1 at p_high
    if ($alpha < 0.0) $alpha = 0.0; elseif ($alpha > 1.0) $alpha = 1.0;

    $u_int = (1.0 - $alpha) * $u_low + $alpha * $u_high;
    $v_int = (1.0 - $alpha) * $v_low + $alpha * $v_high;

    return [$u_int, $v_int];
}

/**
 * Tailwind component for a segment with track/heading "to" (deg true).
 * u is +east, v is +north. Returns m/s (positive = tailwind).
 */
function tailwind_ms(float $u_ms, float $v_ms, float $track_deg): float {
    $rad = deg2rad($track_deg);
    $east = sin($rad);
    $north = cos($rad);
    return $u_ms * $east + $v_ms * $north;
}

/**
 * Convert m/s to knots.
 */
function ms_to_kt(float $ms): float {
    return $ms * 1.94384449244;
}

/**
 * Optional convenience: speed from u/v (m/s).
 */
function wind_speed_ms(float $u_ms, float $v_ms): float {
    return sqrt($u_ms * $u_ms + $v_ms * $v_ms);
}

/**
 * Optional convenience: meteorological direction (degrees FROM which the wind blows),
 * based on u (+east) and v (+north).
 */
function wind_dir_from_deg(float $u_ms, float $v_ms): float {
    $deg = rad2deg(atan2(-$u_ms, -$v_ms));
    $deg = fmod($deg + 360.0, 360.0);
    return $deg;
}
