<?php
// FSAirlines API configuration
define('FSA_API_URL', 'http://www.fsairlines.net/va_interface2.php');
define('FSA_VA_ID', 'ADD HERE YOUR AIRLINE ID');
define('FSA_API_KEY', 'ADD HERE YOUR API KEY');
define('VERSION', 'v1.0');

// Start session
session_start();

// Language selection with browser detection
$available_languages = ['es', 'en'];
$current_language = 'es'; // Default fallback

// Check URL parameter first
if (isset($_GET['lang'])) {
    if (in_array($_GET['lang'], $available_languages)) {
        $current_language = $_GET['lang'];
        $_SESSION['language'] = $current_language;
    }
}
// Check session
elseif (isset($_SESSION['language'])) {
    if (in_array($_SESSION['language'], $available_languages)) {
        $current_language = $_SESSION['language'];
    }
}
// Detect browser language
else {
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang_header = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        if (preg_match('/^([a-z]{2})/', strtolower($lang_header), $matches)) {
            $primary_lang = $matches[1];
            if (in_array($primary_lang, $available_languages)) {
                $current_language = $primary_lang;
            }
        }
    }
    $_SESSION['language'] = $current_language;
}

// Verify language file exists
$lang_file = __DIR__ . "/languages/{$current_language}.php";
if (!file_exists($lang_file)) {
    $current_language = 'es';
    $lang_file = __DIR__ . "/languages/{$current_language}.php";
}

// Load language file
$lang = require($lang_file);


// Aircraft database with cruise speeds
$aircraft_list = [
    'Airbus A220/A310/A318/A319/A320/A321' => ['speed' => 0.79, 'type' => 'mach', 'altitude' => 35000],
    'Airbus A330/A340' => ['speed' => 0.82, 'type' => 'mach', 'altitude' => 35000],
    'Airbus A350/A380' => ['speed' => 0.85, 'type' => 'mach', 'altitude' => 35000],
    'ATR 42' => ['speed' => 260, 'type' => 'knots', 'altitude' => 24000],
    'ATR 72' => ['speed' => 275, 'type' => 'knots', 'altitude' => 24000],
    'Avro RJ-70/85/100' => ['speed' => 0.68, 'type' => 'mach', 'altitude' => 35000],
    'Boeing 717' => ['speed' => 0.76, 'type' => 'mach', 'altitude' => 35000],
    'Boeing 727' => ['speed' => 0.80, 'type' => 'mach', 'altitude' => 35000],
    'Boeing 737-800' => ['speed' => 0.79, 'type' => 'mach', 'altitude' => 35000],
    'Boeing 747-100/200' => ['speed' => 0.83, 'type' => 'mach', 'altitude' => 35000],
    'Boeing 747-300/400/800' => ['speed' => 0.85, 'type' => 'mach', 'altitude' => 35000],
    'Boeing 757/767' => ['speed' => 0.80, 'type' => 'mach', 'altitude' => 35000],
    'Boeing 777' => ['speed' => 0.84, 'type' => 'mach', 'altitude' => 35000],
    'Boeing 787' => ['speed' => 0.86, 'type' => 'mach', 'altitude' => 35000],
    'Bombardier CRJ-100/200' => ['speed' => 0.75, 'type' => 'mach', 'altitude' => 35000],
    'Bombardier CRJ-700/705/1000' => ['speed' => 0.78, 'type' => 'mach', 'altitude' => 35000],
    'British Aerospace 146-100/200/300' => ['speed' => 0.65, 'type' => 'mach', 'altitude' => 35000],
    'De Havilland Canada Dash 7' => ['speed' => 220, 'type' => 'knots', 'altitude' => 24000],
    'De Havilland Canada Dash 8 100/Q300' => ['speed' => 250, 'type' => 'knots', 'altitude' => 24000],
    'De Havilland Canada Dash 8 Q400' => ['speed' => 360, 'type' => 'knots', 'altitude' => 24000],
    'Embraer E135/E145/E170/E175/E190/E195' => ['speed' => 0.78, 'type' => 'mach', 'altitude' => 35000],
    'Fokker 70/100' => ['speed' => 0.70, 'type' => 'mach', 'altitude' => 35000],
    'McDonnell Douglas DC-9' => ['speed' => 0.74, 'type' => 'mach', 'altitude' => 35000],
    'McDonnell Douglas MD-11' => ['speed' => 0.83, 'type' => 'mach', 'altitude' => 35000],
    'McDonnell Douglas MD-81/82/83/87/88' => ['speed' => 0.76, 'type' => 'mach', 'altitude' => 35000]
];

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

// Function to get sunrise time from API
function getSunriseTime($lat, $lon, $date) {
    try {
        $url = "https://api.sunrise-sunset.org/json?lat={$lat}&lng={$lon}&formatted=0&date={$date}";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] == 'OK') {
            $sunrise_full = $data['results']['sunrise'];
            preg_match('/T(\d{2}:\d{2}:\d{2})\+/', $sunrise_full, $matches);
            if (isset($matches[1])) {
                $time = DateTime::createFromFormat('H:i:s', $matches[1]);
                return $time->format('H:i');
            }
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// Function to generate random time with custom range
function generateRandomTime($sunrise_time, $minutes_before, $hours_after) {
    try {
        $sunrise = DateTime::createFromFormat('H:i', $sunrise_time);
        if (!$sunrise) {
            return null;
        }
        
        $start = clone $sunrise;
        $start->modify("-{$minutes_before} minutes");
        
        $end = clone $sunrise;
        $end->modify("+{$hours_after} hours");
        
        $start_timestamp = $start->getTimestamp();
        $end_timestamp = $end->getTimestamp();
        $random_timestamp = rand($start_timestamp, $end_timestamp);
        
        $random_time = new DateTime();
        $random_time->setTimestamp($random_timestamp);
        
        return $random_time->format('H:i');
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

// Handle reset button
if (isset($_POST['reset'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle next leg button
$next_leg_dep = null;
if (isset($_POST['next_leg'])) {
    $next_leg_dep = $_POST['next_leg_dep'];
}

// Save preferences to session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aircraft'])) {
        $_SESSION['aircraft'] = $_POST['aircraft'];
    }
    if (isset($_POST['custom_speed'])) {
        $_SESSION['custom_speed'] = $_POST['custom_speed'];
    }
    if (isset($_POST['custom_speed_type'])) {
        $_SESSION['custom_speed_type'] = $_POST['custom_speed_type'];
    }
    if (isset($_POST['cruise_altitude'])) {
        $_SESSION['cruise_altitude'] = $_POST['cruise_altitude'];
    }
    if (isset($_POST['sunrise_date'])) {
        $_SESSION['sunrise_date'] = $_POST['sunrise_date'];
    }
    if (isset($_POST['minutes_before_sunrise'])) {
        $_SESSION['minutes_before_sunrise'] = $_POST['minutes_before_sunrise'];
    }
    if (isset($_POST['hours_after_sunrise'])) {
        $_SESSION['hours_after_sunrise'] = $_POST['hours_after_sunrise'];
    }
    if (isset($_POST['buffer_time_vfr'])) {
        $_SESSION['buffer_time_vfr'] = $_POST['buffer_time_vfr'];
    }
    if (isset($_POST['buffer_time_ifr'])) {
        $_SESSION['buffer_time_ifr'] = $_POST['buffer_time_ifr'];
    }
    if (isset($_POST['climb_rate_vfr'])) {
        $_SESSION['climb_rate_vfr'] = $_POST['climb_rate_vfr'];
    }
    if (isset($_POST['climb_rate_ifr'])) {
        $_SESSION['climb_rate_ifr'] = $_POST['climb_rate_ifr'];
    }
    if (isset($_POST['climb_speed_knots'])) {
        $_SESSION['climb_speed_knots'] = $_POST['climb_speed_knots'];
    }
}

// Get preferences from session or set defaults
$aircraft = $_SESSION['aircraft'] ?? 'custom';
$custom_speed = $_SESSION['custom_speed'] ?? '0.8';
$custom_speed_type = $_SESSION['custom_speed_type'] ?? 'mach';
$cruise_altitude = $_SESSION['cruise_altitude'] ?? '35000';
$sunrise_date = $_SESSION['sunrise_date'] ?? '03/20';
$minutes_before_sunrise = $_SESSION['minutes_before_sunrise'] ?? '120';
$hours_after_sunrise = $_SESSION['hours_after_sunrise'] ?? '15';
$climb_speed_knots = $_SESSION['climb_speed_knots'] ?? '250';
$buffer_time_vfr = $_SESSION['buffer_time_vfr'] ?? '10';
$buffer_time_ifr = $_SESSION['buffer_time_ifr'] ?? '30';
$climb_rate_vfr = $_SESSION['climb_rate_vfr'] ?? '800';
$climb_rate_ifr = $_SESSION['climb_rate_ifr'] ?? '1800';

// Process form submission
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['icao_dep']) && !empty($_POST['icao_arr'])) {
    $icao_dep = strtoupper(trim($_POST['icao_dep']));
    $icao_arr = strtoupper(trim($_POST['icao_arr']));
    $aircraft = $_POST['aircraft'];
    $cruise_altitude = intval($_POST['cruise_altitude']);
    $sunrise_date = trim($_POST['sunrise_date']);
    $minutes_before_sunrise = intval($_POST['minutes_before_sunrise']);
    $hours_after_sunrise = floatval($_POST['hours_after_sunrise']);
    $buffer_time_vfr = intval($_POST['buffer_time_vfr']);
    $buffer_time_ifr = intval($_POST['buffer_time_ifr']);
    $climb_rate_vfr = intval($_POST['climb_rate_vfr']);
    $climb_rate_ifr = intval($_POST['climb_rate_ifr']);
    $climb_speed_knots = intval($_POST['climb_speed_knots']);
    
    // Determine cruise speed and type
    global $aircraft_list;
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
    } elseif ($minutes_before_sunrise < 0) {
        $error = $lang['error_minutes_before'];
    } elseif ($hours_after_sunrise <= 0) {
        $error = $lang['error_hours_after'];
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

        } else if (!$dep_data) {
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
                
                // Get sunrise time for departure airport
                $sunrise_time = getSunriseTime($dep_data['lat'], $dep_data['lon'], $sunrise_date);
                
                if ($sunrise_time) {
                    // Generate random departure time with custom range
                    $random_dep_time = generateRandomTime($sunrise_time, $minutes_before_sunrise, $hours_after_sunrise);
                    
                    // Round departure time to 5 minutes
                    $departure_time = roundToFiveMinutes($random_dep_time);
                    
                    // Calculate flight time
                    $flight_time = calculateFlightTime($distance, $cruise_speed_tas, $cruise_altitude, $climb_rate, $climb_speed_knots);
                    
                    $total_time = $flight_time + $buffer_time;
                    
                    // Calculate arrival time
                    $arrival_time_raw = addMinutesToTime($departure_time, $total_time);
                    
                    // Round arrival time to 5 minutes
                    $arrival_time = roundToFiveMinutes($arrival_time_raw);
                    
                    $result = [
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
                        'sunrise_date' => $sunrise_date,
                        'sunrise' => $sunrise_time,
                        'minutes_before_sunrise' => $minutes_before_sunrise,
                        'departure_time' => $departure_time,
                        'arrival_time' => $arrival_time,
                        'flight_time' => $flight_time,
                        'buffer_time' => $buffer_time,
                        'climb_rate' => $climb_rate,
                        'climb_speed_knots' => $climb_speed_knots,
                        'flight_type' => $flight_type,
                        'hours_after_sunrise' => $hours_after_sunrise,
                        'buffer_time_vfr' => $buffer_time_vfr,
                        'buffer_time_ifr' => $buffer_time_ifr,
                        'climb_rate_vfr' => $climb_rate_vfr,
                        'climb_rate_ifr' => $climb_rate_ifr,
                        'custom_speed' => ($aircraft === 'custom') ? $custom_speed : null,
                        'custom_speed_type' => ($aircraft === 'custom') ? $custom_speed_type : null
                    ];
                } else {
                    $error = $lang['error_sunrise_api'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <link rel="icon" type="image/png" href="favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['page_title']; ?> v<?php echo VERSION; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }
        
		.container{
			background: white;
			border-radius: 15px;
			box-shadow: 0 20px 60px rgba(0,0,0,0.3);

			/* Less padding, especially on top */
			padding: 22px 28px 28px;

			max-width: 600px;
			width: 100%;
			position: relative;
		}

		.language-selector{
			position: fixed;
			top: 15px;
			right: 15px;
			display: flex;
			gap: 12px;

			/* Always above any transformed/stacked elements */
			z-index: 2147483647;

			/* Defensive: ensure it receives pointer events */
			pointer-events: auto;
		}


		.language-selector a{
			display: inline-flex;
			align-items: center;
			justify-content: center;

			width: 60px;
			height: 45px;

			border: 3px solid #667eea;
			border-radius: 5px;
			background: #667eea;
			transition: all 0.3s;

			/* Make sure link box is the click target */
			cursor: pointer;
			pointer-events: auto;
		}

		.language-selector a.active{
			background: white;
			border-color: #ffffff;

			/* Highlighter ring */
			box-shadow:
				0 0 0 4px rgba(255,255,255,0.95),      /* inner light ring */
				0 0 0 7px rgba(102,126,234,0.95),       /* outer colored ring */
				0 10px 25px rgba(0,0,0,0.25);           /* depth */
		}

		.language-selector a:hover {
			transform: translateY(-2px);
			box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
		}

		.language-selector img{
			width: 60px;
			height: 45px;
			display: block;

			/* Click goes to the <a>, not the <img> */
			pointer-events: none;
		}
      
        .language-btn {
            width: 50px;
            height: 50px;
            padding: 0;
            margin: 0;
            background: #764ba2;
            border: 2px solid #764ba2;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            line-height: 1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            pointer-events: auto !important;
        }
        
        .language-btn:hover {
            transform: translateY(-4px) scale(1.12);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .language-btn:active {
            transform: translateY(-2px) scale(1.08);
        }
        
        .language-btn.active {
            background: white;
            border-color: white;
            box-shadow: 0 5px 20px rgba(255,255,255,0.6);
        }
        
        .language-btn.active:hover {
            box-shadow: 0 8px 30px rgba(255,255,255,0.8);
            transform: translateY(-4px) scale(1.12);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"] {
            text-transform: uppercase;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        select {
            cursor: pointer;
        }
        
        .custom-speed-group {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        #customSpeedFields {
            display: none;
        }
        
        #customSpeedFields.show {
            display: block;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .button-secondary {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }
        
        .button-secondary:hover {
            box-shadow: 0 5px 20px rgba(72, 187, 120, 0.4);
        }
        
        .button-next-leg {
            background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);
        }
        
        .button-next-leg:hover {
            box-shadow: 0 5px 20px rgba(246, 173, 85, 0.4);
        }
        
        .button-reset {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        }
        
        .button-reset:hover {
            box-shadow: 0 5px 20px rgba(229, 62, 62, 0.4);
        }
        
        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .result-box {
            margin-top: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            scroll-margin-top: 20px;
        }
        
        .error-box {
            margin-top: 30px;
            padding: 20px;
            background: #fee;
            border-radius: 10px;
            border-left: 4px solid #e53e3e;
            color: #c53030;
            scroll-margin-top: 20px;
        }
        
        .airport-section {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .airport-title {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-line {
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
        }
        
        .flight-info {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .time-display {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .time-label {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .time-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            font-family: 'Courier New', monospace;
            user-select: all;
            cursor: pointer;
            padding: 10px;
            background: #fafafa;
            border-radius: 5px;
            text-align: center;
            transition: background 0.2s;
        }
        
        .time-value:hover {
            background: #e8f4f8;
        }
        
        .time-value:active {
            background: #d0e8f0;
        }
        
        .times-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .copied-notification {
            position: fixed;
            bottom: 20px;
            right: 40%;
            background: #48bb78;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            font-weight: 600;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s, transform 0.3s;
            z-index: 1000;
        }
        
        .copied-notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .version-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-top: 3px solid #667eea;
        }
        
        .version-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .credits {
            font-size: 13px;
            color: #888;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .credits a {
            color: #667eea;
            text-decoration: none;
        }
        
        .credits a:hover {
            text-decoration: underline;
        }
        
        .help-text {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
            font-style: italic;
        }
        
        .advanced-options {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
        }
        
        .advanced-title {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 15px;
            font-size: 16px;
            cursor: pointer;
            user-select: none;
        }
        
        .advanced-title:hover {
            color: #5568d3;
        }
        
        .advanced-content {
            display: none;
        }
        
        .advanced-content.show {
            display: block;
        }
        
        @media (max-width: 600px) {
            .form-row,
            .times-grid,
            .button-group {
                grid-template-columns: 1fr;
            }
            
            .custom-speed-group {
                grid-template-columns: 1fr;
            }
            
            .language-selector {
                position: fixed;
                top: 10px;
                right: 10px;
                justify-content: center;
                flex-wrap: wrap;
                gap: 10px;
                z-index: 1000;
            }
            
            .language-btn {
                width: 56px;
                height: 56px;
                padding: 0;
                margin: 0;
                font-size: 32px;
                pointer-events: auto !important;
            }
        }

    </style>
</head>
<body>
	<div class="language-selector">
		<a href="?lang=es" class="<?php echo $current_language === 'es' ? 'active' : ''; ?>">
			<img src="languages/es.svg" alt="Español">
		</a>
		<a href="?lang=en" class="<?php echo $current_language === 'en' ? 'active' : ''; ?>">
			<img src="languages/gb.svg" alt="English">
		</a>
	</div>
    
    <div class="container">
        <h1><?php echo $lang['title']; ?></h1>
        <p class="subtitle"><?php echo $lang['subtitle']; ?></p>
        
        <form method="POST" action="" id="mainForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="icao_dep"><?php echo $lang['departure_icao']; ?>:</label>
                    <input 
                        type="text" 
                        id="icao_dep" 
                        name="icao_dep" 
                        maxlength="4" 
                        placeholder="<?php echo $lang['placeholder_departure']; ?>" 
                        value="<?php echo $next_leg_dep ? htmlspecialchars($next_leg_dep) : (isset($_POST['icao_dep']) ? htmlspecialchars($_POST['icao_dep']) : ''); ?>"
                        required 
                        <?php echo $next_leg_dep ? '' : 'autofocus'; ?>
                    >
                </div>
                
                <div class="form-group">
                    <label for="icao_arr"><?php echo $lang['arrival_icao']; ?>:</label>
                    <input 
                        type="text" 
                        id="icao_arr" 
                        name="icao_arr" 
                        maxlength="4" 
                        placeholder="<?php echo $lang['placeholder_arrival']; ?>" 
                        value="<?php echo (isset($_POST['icao_arr']) && !$next_leg_dep) ? htmlspecialchars($_POST['icao_arr']) : ''; ?>"
                        required
                        <?php echo $next_leg_dep ? 'autofocus' : ''; ?>
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label for="aircraft"><?php echo $lang['aircraft']; ?>:</label>
                <select name="aircraft" id="aircraft" onchange="toggleCustomSpeed(); updateAltitudeForAircraft()" required>
                    <option value="custom" <?php echo (isset($_POST['aircraft']) && $_POST['aircraft'] === 'custom') || (!isset($_POST['aircraft']) && $aircraft === 'custom') ? 'selected' : ''; ?>><?php echo $lang['custom_speed']; ?></option>
                    <?php foreach ($aircraft_list as $aircraft_name => $aircraft_data): ?>
                        <option value="<?php echo htmlspecialchars($aircraft_name); ?>" <?php echo (isset($_POST['aircraft']) && $_POST['aircraft'] === $aircraft_name) || (!isset($_POST['aircraft']) && $aircraft_name === $aircraft) ? 'selected' : ''; ?>>
                            <?php 
                            echo htmlspecialchars($aircraft_name) . ' (';
                            if ($aircraft_data['type'] === 'mach') {
                                echo 'Mach ' . number_format($aircraft_data['speed'], 2);
                            } else {
                                echo $aircraft_data['speed'] . ' kt';
                            }
                            echo ')';
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div id="customSpeedFields" class="<?php echo (isset($_POST['aircraft']) && $_POST['aircraft'] === 'custom') ? 'show' : ''; ?>">
                    <div class="custom-speed-group">
                        <input 
                            type="number" 
                            id="custom_speed" 
                            name="custom_speed" 
                            step="0.01"
                            placeholder="<?php echo $lang['placeholder_custom_speed']; ?>" 
                            value="<?php echo isset($_POST['custom_speed']) ? htmlspecialchars($_POST['custom_speed']) : htmlspecialchars($custom_speed); ?>"
                            onchange="updateClimbSpeedForCustom()"
                        >
                        <select name="custom_speed_type" id="custom_speed_type" onchange="updateAltitudeForAircraft(); updateClimbSpeedForCustom()">
                            <option value="mach" <?php echo $custom_speed_type === 'mach' ? 'selected' : ''; ?>>Mach</option>
                            <option value="ktas" <?php echo $custom_speed_type === 'ktas' ? 'selected' : ''; ?>>KTAS</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="minutes_before_sunrise"><?php echo $lang['minutes_before_sunrise']; ?>:</label>
                    <input 
                        type="number" 
                        id="minutes_before_sunrise" 
                        name="minutes_before_sunrise" 
                        min="0"
                        placeholder="120" 
                        value="<?php echo htmlspecialchars($minutes_before_sunrise); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="hours_after_sunrise"><?php echo $lang['hours_after_sunrise']; ?>:</label>
                    <input 
                        type="number" 
                        id="hours_after_sunrise" 
                        name="hours_after_sunrise" 
                        step="0.5"
                        min="0.5"
                        placeholder="15" 
                        value="<?php echo htmlspecialchars($hours_after_sunrise); ?>"
                        required
                    >
                </div>
            </div>
            <div class="help-text"><?php echo $lang['departure_randomized']; ?></div>
            
            <div class="advanced-options">
                <div class="advanced-title" onclick="toggleAdvanced()">
                    ⚙️ <?php echo $lang['advanced_options']; ?> <span id="advancedToggle">▼</span>
                </div>
                <div class="advanced-content" id="advancedContent">
                    <div class="form-group">
                        <label for="cruise_altitude"><?php echo $lang['cruise_altitude']; ?> (<?php echo $lang['feet']; ?>):</label>
                        <input 
                            type="number" 
                            id="cruise_altitude" 
                            name="cruise_altitude" 
                            step="100"
                            placeholder="<?php echo $lang['placeholder_cruise_altitude']; ?>" 
                            value="<?php echo htmlspecialchars($cruise_altitude); ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="sunrise_date"><?php echo $lang['sunrise_date']; ?> (MM/DD):</label>
                        <input 
                            type="text" 
                            id="sunrise_date" 
                            name="sunrise_date" 
                            maxlength="5"
                            placeholder="<?php echo $lang['placeholder_sunrise_date']; ?>" 
                            value="<?php echo htmlspecialchars($sunrise_date); ?>"
                            required
                        >
                        <div class="help-text"><?php echo $lang['sunrise_date_format']; ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="climb_speed_knots"><?php echo $lang['climb_descent_speed']; ?> (<?php echo $lang['knots']; ?>):</label>
                        <input 
                            type="number" 
                            id="climb_speed_knots" 
                            name="climb_speed_knots" 
                            min="1"
                            placeholder="250" 
                            value="<?php echo htmlspecialchars($climb_speed_knots); ?>"
                            required
                        >
                        <div class="help-text"><?php echo $lang['climb_speed_help']; ?></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="buffer_time_vfr"><?php echo $lang['buffer_time_knots']; ?> (<?php echo $lang['minutes']; ?>):</label>
                            <input 
                                type="number" 
                                id="buffer_time_vfr" 
                                name="buffer_time_vfr" 
                                min="0"
                                placeholder="10" 
                                value="<?php echo htmlspecialchars($buffer_time_vfr); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="buffer_time_ifr"><?php echo $lang['buffer_time_mach']; ?> (<?php echo $lang['minutes']; ?>):</label>
                            <input 
                                type="number" 
                                id="buffer_time_ifr" 
                                name="buffer_time_ifr" 
                                min="0"
                                placeholder="30" 
                                value="<?php echo htmlspecialchars($buffer_time_ifr); ?>"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="climb_rate_vfr"><?php echo $lang['climb_rate_knots']; ?> (<?php echo $lang['feet_per_minute']; ?>):</label>
                            <input 
                                type="number" 
                                id="climb_rate_vfr" 
                                name="climb_rate_vfr" 
                                min="1"
                                placeholder="800" 
                                value="<?php echo htmlspecialchars($climb_rate_vfr); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="climb_rate_ifr"><?php echo $lang['climb_rate_mach']; ?> (<?php echo $lang['feet_per_minute']; ?>):</label>
                            <input 
                                type="number" 
                                id="climb_rate_ifr" 
                                name="climb_rate_ifr" 
                                min="1"
                                placeholder="1800" 
                                value="<?php echo htmlspecialchars($climb_rate_ifr); ?>"
                                required
                            >
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit"><?php echo $lang['calculate_times']; ?></button>
        </form>
        
        <?php if ($error): ?>
            <div class="error-box" id="resultsSection">
                <strong>⚠️ <?php echo $lang['error']; ?>:</strong> <?php echo $error; ?>
                <div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; color: #856404; font-size: 13px;">
                    <strong><?php echo $lang['note']; ?>:</strong> <?php echo $lang['fsa_login_note']; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($result): ?>
            <div class="result-box" id="resultsSection">
                <div class="airport-section">
                    <div class="airport-title">✈️ <?php echo $lang['departure']; ?></div>
                    <div class="info-line">
                        <strong><?php echo $lang['icao']; ?>:</strong> <?php echo htmlspecialchars($result['dep_icao']); ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['name']; ?>:</strong> <?php echo htmlspecialchars($result['dep_name']); ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['coordinates']; ?>:</strong> <?php echo number_format($result['dep_lat'], 6); ?>, <?php echo number_format($result['dep_lon'], 6); ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['sunrise']; ?> (<?php echo htmlspecialchars($result['sunrise_date']); ?>):</strong> <?php echo $result['sunrise']; ?> UTC
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['departure_range']; ?>:</strong> <?php echo $result['minutes_before_sunrise']; ?> <?php echo $lang['minutes_before']; ?> <?php echo $lang['to']; ?> <?php echo number_format($result['hours_after_sunrise'], 1); ?> <?php echo $lang['hours_after']; ?> <?php echo $lang['sunrise_text']; ?>
                    </div>
                </div>
                
                <div class="airport-section">
                    <div class="airport-title">🛬 <?php echo $lang['arrival']; ?></div>
                    <div class="info-line">
                        <strong><?php echo $lang['icao']; ?>:</strong> <?php echo htmlspecialchars($result['arr_icao']); ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['name']; ?>:</strong> <?php echo htmlspecialchars($result['arr_name']); ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['coordinates']; ?>:</strong> <?php echo number_format($result['arr_lat'], 6); ?>, <?php echo number_format($result['arr_lon'], 6); ?>
                    </div>
                </div>
                
                <div class="flight-info">
                    <div class="airport-title">📊 <?php echo $lang['flight_data']; ?> (<?php echo $result['flight_type']; ?>)</div>
                    <div class="info-line">
                        <strong><?php echo $lang['aircraft']; ?>:</strong> <?php echo $result['aircraft'] === 'custom' ? $lang['custom'] : htmlspecialchars($result['aircraft']); ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['distance']; ?>:</strong> <?php echo number_format($result['distance'], 1); ?> NM
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['cruise_speed']; ?>:</strong> 
                        <?php 
                        if ($result['speed_type'] === 'mach') {
                            echo "Mach " . number_format($result['cruise_speed'], 2) . " (" . number_format($result['cruise_speed_tas'], 0) . " KTAS)";
                        } else {
                            echo number_format($result['cruise_speed'], 0) . " KTAS";
                        }
                        ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['cruise_altitude']; ?>:</strong> <?php echo number_format($result['cruise_altitude']); ?> <?php echo $lang['feet']; ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['climb_descent_speed']; ?>:</strong> <?php echo $result['climb_speed_knots']; ?> <?php echo $lang['knots']; ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['climb_descent_rate']; ?>:</strong> <?php echo number_format($result['climb_rate']); ?> <?php echo $lang['feet_per_minute']; ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['flight_time']; ?>:</strong> <?php echo number_format($result['flight_time'], 0); ?> <?php echo $lang['minutes']; ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['buffer_time']; ?>:</strong> <?php echo $result['buffer_time']; ?> <?php echo $lang['minutes']; ?>
                    </div>
                    <div class="info-line">
                        <strong><?php echo $lang['total_time']; ?>:</strong> <?php echo number_format($result['flight_time'] + $result['buffer_time'], 0); ?> <?php echo $lang['minutes']; ?> (<?php echo floor(($result['flight_time'] + $result['buffer_time']) / 60); ?>h <?php echo ($result['flight_time'] + $result['buffer_time']) % 60; ?>m)
                    </div>
                </div>
                
                <div class="times-grid">
                    <div class="time-display">
                        <div class="time-label">🛫 <?php echo $lang['departure_icao']; ?>:</div>
                        <div class="time-value" onclick="copyToClipboard('<?php echo $result['dep_icao']; ?>', this)"><?php echo $result['dep_icao']; ?></div>
                    </div>
                    
                    <div class="time-display">
                        <div class="time-label">🛬 <?php echo $lang['arrival_icao']; ?>:</div>
                        <div class="time-value" onclick="copyToClipboard('<?php echo $result['arr_icao']; ?>', this)"><?php echo $result['arr_icao']; ?></div>
                    </div>
                </div>
                
                <div class="times-grid">
                    <div class="time-display">
                        <div class="time-label">🛫 <?php echo $lang['departure_time']; ?>:</div>
                        <div class="time-value" onclick="copyToClipboard('<?php echo $result['departure_time']; ?>', this)"><?php echo $result['departure_time']; ?></div>
                    </div>
                    
                    <div class="time-display">
                        <div class="time-label">🛬 <?php echo $lang['arrival_time']; ?>:</div>
                        <div class="time-value" onclick="copyToClipboard('<?php echo $result['arrival_time']; ?>', this)"><?php echo $result['arrival_time']; ?></div>
                    </div>
                </div>
                
                <div class="button-group">
                    <form method="POST" action="" style="margin: 0;">
                        <input type="hidden" name="next_leg" value="1">
                        <input type="hidden" name="next_leg_dep" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">
                        <input type="hidden" name="aircraft" value="<?php echo htmlspecialchars($result['aircraft']); ?>">
                        <?php if ($result['aircraft'] === 'custom'): ?>
                            <input type="hidden" name="custom_speed" value="<?php echo htmlspecialchars($result['custom_speed']); ?>">
                            <input type="hidden" name="custom_speed_type" value="<?php echo htmlspecialchars($result['custom_speed_type']); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="cruise_altitude" value="<?php echo htmlspecialchars($result['cruise_altitude']); ?>">
                        <input type="hidden" name="sunrise_date" value="<?php echo htmlspecialchars($result['sunrise_date']); ?>">
                        <input type="hidden" name="minutes_before_sunrise" value="<?php echo htmlspecialchars($result['minutes_before_sunrise']); ?>">
                        <input type="hidden" name="hours_after_sunrise" value="<?php echo htmlspecialchars($result['hours_after_sunrise']); ?>">
                        <input type="hidden" name="buffer_time_vfr" value="<?php echo htmlspecialchars($result['buffer_time_vfr']); ?>">
                        <input type="hidden" name="buffer_time_ifr" value="<?php echo htmlspecialchars($result['buffer_time_ifr']); ?>">
                        <input type="hidden" name="climb_rate_vfr" value="<?php echo htmlspecialchars($result['climb_rate_vfr']); ?>">
                        <input type="hidden" name="climb_rate_ifr" value="<?php echo htmlspecialchars($result['climb_rate_ifr']); ?>">
                        <input type="hidden" name="climb_speed_knots" value="<?php echo htmlspecialchars($result['climb_speed_knots']); ?>">
                        <button type="submit" class="button-next-leg">✈️ <?php echo $lang['next_leg']; ?></button>
                    </form>
                    
                    <form method="POST" action="" style="margin: 0;">
                        <input type="hidden" name="icao_dep" value="<?php echo htmlspecialchars($result['dep_icao']); ?>">
                        <input type="hidden" name="icao_arr" value="<?php echo htmlspecialchars($result['arr_icao']); ?>">
                        <input type="hidden" name="aircraft" value="<?php echo htmlspecialchars($result['aircraft']); ?>">
                        <?php if ($result['aircraft'] === 'custom'): ?>
                            <input type="hidden" name="custom_speed" value="<?php echo htmlspecialchars($result['custom_speed']); ?>">
                            <input type="hidden" name="custom_speed_type" value="<?php echo htmlspecialchars($result['custom_speed_type']); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="cruise_altitude" value="<?php echo htmlspecialchars($result['cruise_altitude']); ?>">
                        <input type="hidden" name="sunrise_date" value="<?php echo htmlspecialchars($result['sunrise_date']); ?>">
                        <input type="hidden" name="minutes_before_sunrise" value="<?php echo htmlspecialchars($result['minutes_before_sunrise']); ?>">
                        <input type="hidden" name="hours_after_sunrise" value="<?php echo htmlspecialchars($result['hours_after_sunrise']); ?>">
                        <input type="hidden" name="buffer_time_vfr" value="<?php echo htmlspecialchars($result['buffer_time_vfr']); ?>">
                        <input type="hidden" name="buffer_time_ifr" value="<?php echo htmlspecialchars($result['buffer_time_ifr']); ?>">
                        <input type="hidden" name="climb_rate_vfr" value="<?php echo htmlspecialchars($result['climb_rate_vfr']); ?>">
                        <input type="hidden" name="climb_rate_ifr" value="<?php echo htmlspecialchars($result['climb_rate_ifr']); ?>">
                        <input type="hidden" name="climb_speed_knots" value="<?php echo htmlspecialchars($result['climb_speed_knots']); ?>">
                        <button type="submit" class="button-secondary">🔄 <?php echo $lang['recalculate']; ?></button>
                    </form>
                    
                    <form method="POST" action="" style="margin: 0;">
                        <input type="hidden" name="reset" value="1">
                        <button type="submit" class="button-reset">🔃 <?php echo $lang['reset']; ?></button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="version-info">
            <div class="version-title"><?php echo $lang['version']; ?> <?php echo VERSION; ?></div>
            <div class="credits">
                <strong>Ramón Cutanda</strong><br>
                <a href="https://github.com/rcutanda/FSAirlines-Flight-Scheduler" target="_blank">https://github.com/rcutanda/FSAirlines-Flight-Scheduler</a>
            </div>
        </div>
    </div>
    
    <div id="copiedNotification" class="copied-notification">
        ✓ <?php echo $lang['copied']; ?>
    </div>
    
    <script>
        function toggleAdvanced() {
            const content = document.getElementById('advancedContent');
            const toggle = document.getElementById('advancedToggle');
            
            if (content.classList.contains('show')) {
                content.classList.remove('show');
                toggle.textContent = '▼';
            } else {
                content.classList.add('show');
                toggle.textContent = '▲';
            }
        }
        
        function toggleCustomSpeed() {
            const aircraftSelect = document.getElementById('aircraft');
            const customFields = document.getElementById('customSpeedFields');
            
            if (aircraftSelect.value === 'custom') {
                customFields.classList.add('show');
            } else {
                customFields.classList.remove('show');
            }
        }
        
        function updateAltitudeForAircraft() {
            const aircraftSelect = document.getElementById('aircraft');
            const altitudeInput = document.getElementById('cruise_altitude');
            const customSpeedTypeSelect = document.getElementById('custom_speed_type');
            const selectedAircraft = aircraftSelect.value;
            
            const aircraftData = <?php echo json_encode($aircraft_list); ?>;
            
            if (selectedAircraft === 'custom') {
                if (customSpeedTypeSelect && customSpeedTypeSelect.value === 'ktas') {
                    altitudeInput.value = 24000;
                } else {
                    altitudeInput.value = 35000;
                }
            } else if (aircraftData[selectedAircraft]) {
                altitudeInput.value = aircraftData[selectedAircraft]['altitude'];
            }
        }
        
        function updateClimbSpeedForCustom() {
            const aircraftSelect = document.getElementById('aircraft');
            const customSpeedTypeSelect = document.getElementById('custom_speed_type');
            const customSpeedInput = document.getElementById('custom_speed');
            const climbSpeedInput = document.getElementById('climb_speed_knots');
            
            if (aircraftSelect.value === 'custom') {
                if (customSpeedTypeSelect.value === 'ktas') {
                    const customSpeed = parseFloat(customSpeedInput.value);
                    if (!isNaN(customSpeed) && customSpeed > 0) {
                        climbSpeedInput.value = Math.round(customSpeed * 0.7);
                    }
                } else if (customSpeedTypeSelect.value === 'mach') {
                    climbSpeedInput.value = 250;
                }
            }
        }
        
        function copyToClipboard(text, element) {
            const textWithoutColon = text.replace(':', '');
            
            const textarea = document.createElement('textarea');
            textarea.value = textWithoutColon;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                showNotification();
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            
            document.body.removeChild(textarea);
        }
        
        function showNotification() {
            const notification = document.getElementById('copiedNotification');
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 2500);
        }
        
        function scrollToResults() {
            const resultsSection = document.getElementById('resultsSection');
            if (resultsSection) {
                resultsSection.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            toggleCustomSpeed();
            updateAltitudeForAircraft();
            scrollToResults();
        });
    </script>
</body>
</html>