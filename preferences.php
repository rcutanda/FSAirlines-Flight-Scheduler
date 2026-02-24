<?php
function handleLanguageSelection() {
    $available_languages = array('es', 'en');
    $current_language = 'en';
    $cookie_name = 'fsa_scheduler_lang';
    $cookie_expiration = time() + (86400 * 365);

    if (isset($_GET['lang']) && in_array($_GET['lang'], $available_languages, true)) {
        $current_language = $_GET['lang'];
        setcookie($cookie_name, $current_language, $cookie_expiration, '/', '', false, true);
    } elseif (isset($_COOKIE[$cookie_name]) && in_array($_COOKIE[$cookie_name], $available_languages, true)) {
        $current_language = $_COOKIE[$cookie_name];
    } else {
        $detected_language = 'en';

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang_header = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $languages = array();
            $lang_parts = explode(',', $lang_header);

            foreach ($lang_parts as $lang_part) {
                $lang_part = trim($lang_part);
                $parts = explode(';', $lang_part);
                $lang_code = strtolower(substr($parts[0], 0, 2));
                $quality = 1.0;

                if (isset($parts[1]) && preg_match('/q=([0-9.]+)/', $parts[1], $matches)) {
                    $quality = floatval($matches[1]);
                }

                if (in_array($lang_code, $available_languages, true) && !isset($languages[$lang_code])) {
                    $languages[$lang_code] = $quality;
                }
            }

            arsort($languages);
            if (!empty($languages)) {
                $detected_language = key($languages);
            }
        }

        $current_language = $detected_language;
        setcookie($cookie_name, $current_language, $cookie_expiration, '/', '', false, true);
    }

    $lang_file = __DIR__ . "/languages/{$current_language}.php";
    if (!file_exists($lang_file)) {
        $current_language = 'es';
        $lang_file = __DIR__ . "/languages/{$current_language}.php";
    }

    $lang = require($lang_file);

    return ['lang' => $lang, 'current_language' => $current_language];
}
?>