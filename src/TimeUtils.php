<?php
declare(strict_types=1);

// Function to round time to nearest 5 minutes
function roundToFiveMinutes($time) {
    $parts = explode(':', $time);
    if (count($parts) != 2) {
        return null;
    }

    $hours = intval($parts[0]);
    $minutes = intval($parts[1]);

    $roundedMinutes = round($minutes / 5) * 5;

    if ($roundedMinutes == 60) {
        $hours += 1;
        $roundedMinutes = 0;
    }

    if ($hours >= 24) {
        $hours -= 24;
    }

    return sprintf('%02d:%02d', $hours, $roundedMinutes);
}

// Function to generate random times
function generateRandomTime($reference_time, $minutes_before, $hours_after) {
    try {
        list($hours, $minutes) = explode(':', $reference_time);
        $total_minutes = (intval($hours) * 60) + intval($minutes);

        $start_minutes = $total_minutes - intval($minutes_before);
        $end_minutes = $total_minutes + intval($hours_after * 60);

        $random_minutes = rand($start_minutes, $end_minutes);

        $random_minutes = $random_minutes % 1440;
        if ($random_minutes < 0) $random_minutes += 1440;

        $result_hours = (int)floor($random_minutes / 60);
        $result_mins = (int)($random_minutes % 60);

        return sprintf('%02d:%02d', $result_hours, $result_mins);
    } catch (Exception $e) {
        return null;
    }
}

// Function to add minutes to time
function addMinutesToTime($time, $minutes) {
    $parts = explode(':', $time);
    if (count($parts) != 2) {
        return null;
    }

    $hours = intval($parts[0]);
    $mins = intval($parts[1]);

    $totalMinutes = ($hours * 60) + $mins + intval($minutes);

    $newHours = floor($totalMinutes / 60) % 24;
    $newMinutes = $totalMinutes % 60;

    return sprintf('%02d:%02d', $newHours, $newMinutes);
}
