<?php
// Generate or retrieve permanent user ID (survives IP changes and browser closes)
function getOrGenerateUserId() {
    $user_id_cookie_name = 'fsa_scheduler_user_id';
    $cookie_expiration = time() + (86400 * 365); // 1 year in seconds

    if (!isset($_COOKIE[$user_id_cookie_name])) {
        $user_id = bin2hex(random_bytes(16));
        setcookie($user_id_cookie_name, $user_id, $cookie_expiration, '/', '', false, true);
    } else {
        $user_id = $_COOKIE[$user_id_cookie_name];
    }
    return $user_id;
}

$user_id = getOrGenerateUserId(); // Available as global
$prefs_file = __DIR__ . '/user_preferences' . '/' . $user_id . '.json'; // Adjust path

// Language selection with browser detection (respects priority weights)
function handleLanguageSelection($prefs_dir) {
    $available_languages = array('es', 'en');
    $current_language = 'en'; // Default fallback

    // Language preference file
    $lang_pref_file = $prefs_dir . '/' . $_GLOBALS['user_id'] . '_lang.txt';

    // Check URL parameter first (allows changing language)
    if (isset($_GET['lang'])) {
        if (in_array($_GET['lang'], $available_languages)) {
            $current_language = $_GET['lang'];
            file_put_contents($lang_pref_file, $current_language, LOCK_EX);
        }
    }
    // Load saved language preference from file
    elseif (file_exists($lang_pref_file)) {
        $saved_lang = trim(file_get_contents($lang_pref_file));
        if (in_array($saved_lang, $available_languages)) {
            $current_language = $saved_lang;
        }
    }
    // First visit: parse browser language with priority weights
    else {
        $detected_language = 'en'; // Fallback
        
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang_header = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $languages = array();
            
            // Parse all languages with quality values
            $lang_parts = explode(',', $lang_header);
            foreach ($lang_parts as $lang_part) {
                $lang_part = trim($lang_part);
                $parts = explode(';', $lang_part);
                $lang_code = strtolower(substr($parts[0], 0, 2)); // Extract 2-letter code
                $quality = 1.0; // Default quality
                
                if (isset($parts[1])) {
                    if (preg_match('/q=([0-9.]+)/', $parts[1], $matches)) {
                        $quality = floatval($matches[1]);
                    }
                }
                
                // Only store the FIRST occurrence of each language (highest priority)
                if (in_array($lang_code, $available_languages) && !isset($languages[$lang_code])) {
                    $languages[$lang_code] = $quality;
                }
            }
            
            // Sort by quality (highest first)
            arsort($languages);
            
            if (!empty($languages)) {
                $detected_language = key($languages); // Get language with highest quality
            }
        }
        
        $current_language = $detected_language;
        file_put_contents($lang_pref_file, $current_language, LOCK_EX);
    }

    // Verify language file exists
    $lang_file = __DIR__ . "/languages/{$current_language}.php";
    if (!file_exists($lang_file)) {
        $current_language = 'es';
        $lang_file = __DIR__ . "/languages/{$current_language}.php";
    }

    // Load language file
    $lang = require($lang_file);

    return $lang;
}

// Load preferences from file
function loadPreferences($file) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

// Save preferences to file
function savePreferences($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

// Save on POST (extracted)
function savePreferencesOnPost($prefs_file, $saved_prefs) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $prefs_to_save = $saved_prefs;
        
        if (isset($_POST['aircraft'])) {
            $prefs_to_save['aircraft'] = $_POST['aircraft'];
        }
        if (isset($_POST['custom_speed'])) {
            $prefs_to_save['custom_speed'] = $_POST['custom_speed'];
        }
        if (isset($_POST['custom_speed_type'])) {
            $prefs_to_save['custom_speed_type'] = $_POST['custom_speed_type'];
        }
        if (isset($_POST['custom_speed_type'])) {
            $type = $_POST['custom_speed_type'];
            if (isset($_POST['custom_speed'])) {
                if ($type === 'mach') {
                    $prefs_to_save['custom_speed_mach'] = $_POST['custom_speed'];
                } elseif ($type === 'ktas') {
                    $prefs_to_save['custom_speed_ktas'] = $_POST['custom_speed'];
                }
            }
        }
        if (isset($_POST['custom_speed_mach'])) {
            $prefs_to_save['custom_speed_mach'] = $_POST['custom_speed_mach'];
        }
        if (isset($_POST['custom_speed_ktas'])) {
            $prefs_to_save['custom_speed_ktas'] = $_POST['custom_speed_ktas'];
        }
        if (isset($_POST['cruise_altitude'])) {
            $prefs_to_save['cruise_altitude'] = $_POST['cruise_altitude'];
        }
        if (isset($_POST['local_departure_time'])) {
            $prefs_to_save['local_departure_time'] = $_POST['local_departure_time'];
        }
        if (isset($_POST['flight_mode'])) {
            $prefs_to_save['flight_mode'] = $_POST['flight_mode'];
        }
        if (isset($_POST['latest_arrival_time'])) {
            $prefs_to_save['latest_arrival_time'] = $_POST['latest_arrival_time'];
        }
        if (isset($_POST['minutes_before_departure'])) {
            $prefs_to_save['minutes_before_departure'] = $_POST['minutes_before_departure'];
        }
        if (isset($_POST['hours_after_departure'])) {
            $prefs_to_save['hours_after_departure'] = $_POST['hours_after_departure'];
        }
        if (isset($_POST['minutes_after_departure'])) {
            $prefs_to_save['minutes_after_departure'] = $_POST['minutes_after_departure'];
        }
        if (isset($_POST['turnaround_time_input'])) {
            $turnaround_input_value = intval($_POST['turnaround_time_input']);
            // Determine if this is from a Mach or knots aircraft based on speed_type in result
            // We'll save both possibilities for flexibility
            if (isset($_POST['aircraft']) && $_POST['aircraft'] !== 'custom') {
                global $aircraft_list;
                $aircraft_data = $aircraft_list[$_POST['aircraft']];
                $current_speed_type = $aircraft_data['type'];
            } elseif (isset($_POST['custom_speed_type'])) {
                $current_speed_type = $_POST['custom_speed_type'];
            } else {
                $current_speed_type = 'mach';
            }
            
            if ($current_speed_type === 'mach') {
                $prefs_to_save['turnaround_time_mach'] = $turnaround_input_value;
            } else {
                $prefs_to_save['turnaround_time_knots'] = $turnaround_input_value;
            }
        }
        if (isset($_POST['turnaround_time_mach'])) {
            $prefs_to_save['turnaround_time_mach'] = $_POST['turnaround_time_mach'];
        }
        if (isset($_POST['turnaround_time_knots'])) {
            $prefs_to_save['turnaround_time_knots'] = $_POST['turnaround_time_knots'];
        }
        if (isset($_POST['buffer_time_vfr'])) {
            $prefs_to_save['buffer_time_vfr'] = $_POST['buffer_time_vfr'];
        }
        if (isset($_POST['buffer_time_ifr'])) {
            $prefs_to_save['buffer_time_ifr'] = $_POST['buffer_time_ifr'];
        }
        if (isset($_POST['climb_rate_vfr'])) {
            $prefs_to_save['climb_rate_vfr'] = $_POST['climb_rate_vfr'];
        }
        if (isset($_POST['climb_rate_ifr'])) {
            $prefs_to_save['climb_rate_ifr'] = $_POST['climb_rate_ifr'];
        }
        if (isset($_POST['climb_speed_knots'])) {
            $prefs_to_save['climb_speed_knots'] = $_POST['climb_speed_knots'];
        }
        
        savePreferences($prefs_file, $prefs_to_save);
        return $prefs_to_save;
    }
    return $saved_prefs;
}
