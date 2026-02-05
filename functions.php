<?php
// Function to get airport coordinates and name from FSAirlines API
function getAirportData($icao) {
    try {
        $url = FSA_API_URL . '?function=getAirportData&va_id=' . FSA_VA_ID . '&icao=' . urlencode($icao) . '&apikey=' . FSA_API_KEY;
        $response = file_get_contents($url);
        
        if ($response === false) {
            return null;
        }
        
        $xml = simplexml_load_string($response);
        
        if ($xml === false) {
            return null;
        }
        
        if ((string)$xml['success'] !== 'SUCCESS') {
            return null;
        }
        
        $data = $xml->data;
        
        if (isset($data['lat']) && isset($data['lon']) && isset($data['name'])) {
            return [
                'lat' => floatval($data['lat']),
                'lon' => floatval($data['lon']),
                'name' => (string)$data['name']
            ];
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// Function to calculate distance between two coordinates
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 3440.065;
    
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $lon1Rad = deg2rad($lon1);
    $lon2Rad = deg2rad($lon2);
    
    $deltaLat = $lat2Rad - $lat1Rad;
    $deltaLon = $lon2Rad - $lon1Rad;
    
    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    $distance = $earthRadius * $c;
    
    return $distance;
}

// Function to convert Mach to TAS
function machToTAS($mach, $altitude) {
    $T0 = 288.15;
    $lapse = 0.0019812;
    $a0 = 661.47;
    
    $T = $T0 - ($lapse * $altitude);
    $a = $a0 * sqrt($T / $T0);
    $tas = $mach * $a;
    
    return $tas;
}

// Function to calculate flight time
function calculateFlightTime($distance, $cruiseSpeed, $cruiseAltitude, $climbRate, $climbSpeedKnots) {
    $climbSpeed = $climbSpeedKnots;
    $climbTime = $cruiseAltitude / $climbRate;
    $descentTime = $cruiseAltitude / $climbRate;
    
    $climbDistance = ($climbSpeed / 60) * $climbTime;
    $descentDistance = ($climbSpeed / 60) * $descentTime;
    $cruiseDistance = $distance - $climbDistance - $descentDistance;
    
    if ($cruiseDistance < 0) {
        $totalTime = ($distance / $climbSpeed) * 60;
    } else {
        $cruiseTime = ($cruiseDistance / $cruiseSpeed) * 60;
        $totalTime = $climbTime + $cruiseTime + $descentTime;
    }
    
    return $totalTime;
}

// Function to round time to nearest 5 minutes
function roundToFiveMinutes($time) {
    $parts = explode(':', $time);
    if (count($parts) != 2) {
        return $time;
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

function generateRandomTime($reference_time, $minutes_before, $hours_after) {
    try {
        list($hours, $minutes) = explode(':', $reference_time);
        $total_minutes = (intval($hours) * 60) + intval($minutes);
        
        $start_minutes = $total_minutes - intval($minutes_before);
        $end_minutes = $total_minutes + intval($hours_after * 60);
        
        $random_minutes = rand($start_minutes, $end_minutes);
        
        $result_hours = floor($random_minutes / 60) % 24;
        $result_mins = $random_minutes % 60;
        
        return sprintf('%02d:%02d', $result_hours, $result_mins);
    } catch (Exception $e) {
        return null;
    }
}

// Function to get timezone information using multiple API fallbacks
function getTimezoneFromCoordinates($lat, $lon) {
    try {
        // Try multiple timezone API endpoints for reliability
        $apis = [
            // Primary: Open-Meteo (free, no auth required, very reliable)
            "https://api.open-meteo.com/v1/timezone?latitude={$lat}&longitude={$lon}&format=json",
            // Secondary: TimeAPI
            "https://timeapi.io/api/v1/timezone/coordinate?latitude={$lat}&longitude={$lon}"
        ];
        
        $last_error = null;
        
        foreach ($apis as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_USERAGENT, 'FSAirlines-Flight-Scheduler/1.1');
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($error) {
                $last_error = "CURL Error: " . $error . " (URL: " . substr($url, 0, 50) . "...)";
                error_log("Timezone API CURL Error: " . $error . " for URL: " . $url);
                continue;
            }
            
            if ($response === false) {
                $last_error = "No response from timezone API";
                error_log("Timezone API: No response from " . $url);
                continue;
            }
            
            if ($httpCode !== 200) {
                $last_error = "HTTP Error " . $httpCode . " from timezone API";
                error_log("Timezone API: HTTP " . $httpCode . " from " . $url);
                continue;
            }
            
            $data = json_decode($response, true);
            
            if ($data === null) {
                $last_error = "Invalid JSON response from timezone API";
                error_log("Timezone API: Invalid JSON from " . $url . " - Response: " . substr($response, 0, 100));
                continue;
            }
            
            // Check if it's Open-Meteo response
            if (isset($data['timezone'])) {
                error_log("Timezone API: Success with Open-Meteo for lat={$lat}, lon={$lon}");
                return [
                    'timezone' => $data['timezone'],
                    'utcOffset' => $data['utc_offset_seconds'] ?? null
                ];
            }
            // Check if it's TimeAPI response
            elseif (isset($data['timezone'])) {
                error_log("Timezone API: Success with TimeAPI for lat={$lat}, lon={$lon}");
                return [
                    'timezone' => $data['timezone'],
                    'utcOffset' => $data['utcOffset'] ?? null
                ];
            }
        }
        
        error_log("Timezone API: All endpoints failed for lat={$lat}, lon={$lon}. Last error: " . $last_error);
        return null;
    } catch (Exception $e) {
        error_log("Timezone API Exception: " . $e->getMessage() . " for lat={$lat}, lon={$lon}");
        return null;
    }
}

// Function to convert local time to UTC time
function convertLocalTimeToUTC($localTime, $timezone) {
    try {
        // Create a DateTime object with today's date in the local timezone
        $tz = new DateTimeZone($timezone);
        $dateTime = new DateTime($localTime, $tz);
        
        // Convert to UTC
        $utcTz = new DateTimeZone('UTC');
        $dateTime->setTimezone($utcTz);
        
        return $dateTime->format('H:i');
    } catch (Exception $e) {
        return null;
    }
}

// Function to add minutes to time
function addMinutesToTime($time, $minutes) {
    $parts = explode(':', $time);
    if (count($parts) != 2) {
        return $time;
    }
    
    $hours = intval($parts[0]);
    $mins = intval($parts[1]);
    
    $totalMinutes = ($hours * 60) + $mins + intval($minutes);
    
    $newHours = floor($totalMinutes / 60) % 24;
    $newMinutes = $totalMinutes % 60;
    
    return sprintf('%02d:%02d', $newHours, $newMinutes);
}

// Function to process form submission (extracted and adapted from index.php form submission logic)
function processFormSubmission($icao_dep, $icao_arr, $aircraft, $cruise_altitude, $local_departure_time, $flight_mode, $latest_arrival_time, $minutes_before, $hours_after, $minutes_after, $buffer_time_vfr, $buffer_time_ifr, $climb_rate_vfr, $climb_rate_ifr, $climb_speed_knots, $is_next_leg, $calculated_next_departure, $local_departure_time_var) {
    global $lang, $aircraft_list; // Access language array and aircraft list
    $error = null;

    // Determine cruise speed and type
    if ($aircraft === 'custom') {
        $custom_speed = floatval($_POST['custom_speed']);
        $custom_speed_type = $_POST['custom_speed_type'];
        
        if ($custom_speed_type === 'mach') {
            $cruise_speed = $custom_speed;
            $speed_type = 'mach';
        } else {
            $cruise_speed = $custom_speed;
            $speed_type = 'ktas';
        }
    } else {
        $aircraft_data = $aircraft_list[$aircraft];
        $cruise_speed = $aircraft_data['speed'];
        $speed_type = $aircraft_data['type'];
        $cruise_altitude = $aircraft_data['altitude'];
    }
    
    // Validate inputs
    if ($cruise_speed <= 0) {
        $error = $lang['error_cruise_speed'];
    } elseif ($cruise_altitude <= 0) {
        $error = $lang['error_cruise_altitude'];
    } elseif ($buffer_time_vfr < 0 || $buffer_time_ifr < 0) {
        $error = $lang['error_buffer_time'];
    } elseif ($climb_rate_vfr <= 0 || $climb_rate_ifr <= 0) {
        $error = $lang['error_climb_rate'];
    } elseif ($climb_speed_knots <= 0) {
        $error = $lang['error_climb_speed'];
    } else {
        // Get departure airport data
        $dep_data = getAirportData($icao_dep);
        
        if (!$dep_data && !getAirportData($icao_arr)) {
            $error = sprintf($lang['error_both_airports'], $icao_dep, '<a href="https://www.fsairlines.net/crewcenter/index.php?icao=' . urlencode($icao_dep) . '&status=db_apts&status2=logged&submit=Submit" target="_blank">' . $icao_dep . '</a>', $icao_arr, '<a href="https://www.fsairlines.net/crewcenter/index.php?icao=' . urlencode($icao_arr) . '&status=db_apts&status2=logged&submit=Submit" target="_blank">' . $icao_arr . '</a>');
        } elseif (!$dep_data) {
            $error = sprintf($lang['error_departure_airport'], '<a href="https://www.fsairlines.net/crewcenter/index.php?icao=' . urlencode($icao_dep) . '&status=db_apts&status2=logged&submit=Submit" target="_blank">' . $icao_dep . '</a>', $icao_dep);
        } else {
            // Get arrival airport data
            $arr_data = getAirportData($icao_arr);
            
            if (!$arr_data) {
                $error = sprintf($lang['error_arrival_airport'], '<a href="https://www.fsairlines.net/crewcenter/index.php?icao=' . urlencode($icao_arr) . '&status=db_apts&status2=logged&submit=Submit" target="_blank">' . $icao_arr . '</a>', $icao_arr);
            } else {
                // Calculate distance
                $distance = calculateDistance(
                    $dep_data['lat'], $dep_data['lon'],
                    $arr_data['lat'], $arr_data['lon']
                );
                
                // Determine flight type, buffer time, and climb rate based on speed type
                if ($speed_type === 'mach') {
                    $cruise_speed_tas = machToTAS($cruise_speed, $cruise_altitude);
                    $buffer_time = $buffer_time_ifr;
                    $climb_rate = $climb_rate_ifr;
                    $flight_type = 'IFR';
                } else {
                    $cruise_speed_tas = $cruise_speed;
                    $buffer_time = $buffer_time_vfr;
                    $climb_rate = $climb_rate_vfr;
                    $flight_type = 'VFR';
                }
                
                // STEP 1: Get timezone information for departure airport
                $tz_info = getTimezoneFromCoordinates($dep_data['lat'], $dep_data['lon']);
                
                // If timezone API fails, assume user entered UTC time directly
                $timezone_warning = false;
                if (!$tz_info) {
                    // FALLBACK: Use UTC timezone (user should enter UTC time)
                    $tz_info = ['timezone' => 'UTC', 'utcOffset' => '00:00'];
                    $timezone_warning = true;
                }
                
                // STEP 2: Get user's local departure time from form (or use override from daily schedule)
                if ($is_next_leg && !isset($_POST['new_day_flag'])) {
                    $user_local_time = $calculated_next_departure;
                } else {
                    $user_local_time = trim($_POST['local_departure_time']);
                }
                $utc_departure_time = convertLocalTimeToUTC($user_local_time, $tz_info['timezone']);
                
                if (!$utc_departure_time) {
                    $error = $lang['error_time_conversion'];
                } else {
                    // STEP 4: Generate departure time
                    if ($is_next_leg) {
                        // For next leg, use exact time without randomization
                        $departure_time = roundToFiveMinutes($utc_departure_time);
                    } elseif (isset($_POST['new_day_flag'])) {
                        // Use User's selected time without randomization
                        $departure_time = roundToFiveMinutes($utc_departure_time);
                    } else {
                        // Randomization for new flights
                        if ($flight_mode === 'daily_schedule') {
                            $time_after_minutes = intval($minutes_after);
                            $random_dep_time = generateRandomTime($utc_departure_time, $minutes_before, $time_after_minutes / 60);
                        } else {
                            $random_dep_time = generateRandomTime($utc_departure_time, $minutes_before, $hours_after);
                        }
                        $departure_time = roundToFiveMinutes($random_dep_time);
                    }
                    
                    // Calculate flight time
                    $flight_time = calculateFlightTime($distance, $cruise_speed_tas, $cruise_altitude, $climb_rate, $climb_speed_knots);
                    
                    $total_time = $flight_time + $buffer_time;
                    
                    // Calculate arrival time
                    $arrival_time_raw = addMinutesToTime($departure_time, $total_time);
                    
                    // Round arrival time to 5 minutes
                    $arrival_time = roundToFiveMinutes($arrival_time_raw);
                    
                    // Convert randomized UTC time back to local time for display
                    try {
                        $tz = new DateTimeZone($tz_info['timezone']);
                        $dateTime = new DateTime($departure_time, new DateTimeZone('UTC'));
                        $dateTime->setTimezone($tz);
                        $local_departure_time_randomized = $dateTime->format('H:i');
                    } catch (Exception $e) {
                        $local_departure_time_randomized = $departure_time;
                    }
                    
                    // Check if this is a "next leg" call
                    $is_next_leg_call = intval($is_next_leg);
                    
                    // For daily schedule mode: check if arrival exceeds latest allowed time
                    $new_day_triggered = false;
                    if ($is_next_leg) {
                        $new_day_triggered = false; // Default to false for non-daily modes
                        if ($flight_mode === 'daily_schedule') {
                            // Convert arrival time to local time for comparison with latest_arrival_time
                            try {
                                $tz = new DateTimeZone($tz_info['timezone']);
                                $arrivalDateTime = new DateTime($arrival_time, new DateTimeZone('UTC'));
                                $arrivalDateTime->setTimezone($tz);
                                $arrival_time_local = $arrivalDateTime->format('H:i');
                                
                                // Compare times
                                $arrival_minutes = (int)explode(':', $arrival_time_local)[0] * 60 + (int)explode(':', $arrival_time_local)[1];
                                $latest_minutes = (int)explode(':', $latest_arrival_time)[0] * 60 + (int)explode(':', $latest_arrival_time)[1];
                                
                                if ($arrival_minutes > $latest_minutes || ($arrival_minutes < $latest_minutes && $latest_minutes > 18 * 60)) {
                                    $new_day_triggered = true;
                                    $daily_schedule_warning = sprintf($lang['new_day_warning'], htmlspecialchars($latest_arrival_time));
                                }
                            } catch (Exception $e) {
                                // If conversion fails, proceed normally
                            }
                        }
                    }
                    
                    $result = [
                        'timezone_warning' => $timezone_warning,
                        'new_day_warning' => $daily_schedule_warning ?? null,
                        'dep_icao' => $icao_dep,
                        'dep_name' => $dep_data['name'],
                        'dep_lat' => $dep_data['lat'],
                        'dep_lon' => $dep_data['lon'],
                        'arr_icao' => $icao_arr,
                        'arr_name' => $arr_data['name'],
                        'arr_lat' => $arr_data['lat'],
                        'arr_lon' => $arr_data['lon'],
                        'distance' => $distance,
                        'aircraft' => $aircraft,
                        'cruise_speed' => $cruise_speed,
                        'cruise_speed_tas' => $cruise_speed_tas,
                        'cruise_altitude' => $cruise_altitude,
                        'speed_type' => $speed_type,
                        'local_departure_time' => $local_departure_time,
                        'utc_departure_time' => $utc_departure_time,
                        'minutes_before_departure' => $minutes_before,
                        'hours_after_departure' => $hours_after,
                        'minutes_after_departure' => $minutes_after,
                        'departure_time' => $departure_time,
                        'arrival_time' => $arrival_time,
                        'flight_time' => $flight_time,
                        'buffer_time' => $buffer_time,
                        'climb_rate' => $climb_rate,
                        'climb_speed_knots' => $climb_speed_knots,
                        'flight_type' => $flight_type,
                        'buffer_time_vfr' => $buffer_time_vfr,
                        'buffer_time_ifr' => $buffer_time_ifr,
                        'climb_rate_vfr' => $climb_rate_vfr,
                        'climb_rate_ifr' => $climb_rate_ifr,
                        'custom_speed' => ($aircraft === 'custom') ? $custom_speed : null,
                        'custom_speed_type' => ($aircraft === 'custom') ? $custom_speed_type : null,
                        'local_departure_time_randomized' => $local_departure_time_randomized,
                        'flight_mode' => $flight_mode,
                        'latest_arrival_time' => $latest_arrival_time,
                        'new_day_triggered' => $new_day_triggered,
                        'is_next_leg_call' => $is_next_leg_call
                    ];
                }
            }
        }
    }
    
    return [$result ?? null, $error];
}
