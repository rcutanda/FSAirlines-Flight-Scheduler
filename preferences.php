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
    global $user_id; // Added: Access global user_id
    $available_languages = array('es', 'en');
    $current_language = 'en'; // Default fallback

    // Language preference file
    $lang_pref_file = $prefs_dir . '/' . $user_id . '_lang.txt'; // Fixed: Use $user_id

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

// Return both lang and current_language
return ['lang' => $lang, 'current_language' => $current_language];
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