<?php
declare(strict_types=1);

// Function to get timezone information using multiple API fallbacks
function getTimezoneFromCoordinates($lat, $lon) {
    try {
        $apis = [
            "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&timezone=auto&current=temperature_2m&forecast_days=1",
            "https://timeapi.io/api/v1/timezone/coordinate?latitude={$lat}&longitude={$lon}"
        ];

        foreach ($apis as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_USERAGENT, 'FSAirlines-Flight-Scheduler/1.1');
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($error || $response === false || $httpCode !== 200) {
                continue;
            }

            $data = json_decode($response, true);
            if ($data === null) {
                continue;
            }

            $timezone = null;

            if (preg_match('/timeapi.io/', $url) && isset($data['timezone']) && isset($data['current_utc_offset_seconds'])) {
                $timezone = $data['timezone'];
            } elseif (preg_match('/open-meteo/', $url) && isset($data['timezone'])) {
                $timezone = $data['timezone'];
            }

            // Only return if we have a valid timezone name
            if ($timezone !== null && $timezone !== '' && $timezone !== 'UTC') {
                return [
                    'timezone' => $timezone
                ];
            }
        }

        return null;

    } catch (Exception $e) {
        return null;
    }
}

// Function to convert local time to UTC time
function convertLocalTimeToUTC($localTime, $timezone) {
    try {
        $tz = new DateTimeZone($timezone);

        // If only HH:MM is provided, keep legacy behavior (assumes today's date).
        // If a date is included (YYYY-MM-DD HH:MM), DST is handled correctly for that date.
        $dateTime = DateTime::createFromFormat('H:i', (string)$localTime, $tz);
        if (!$dateTime) {
            $dateTime = new DateTime((string)$localTime, $tz);
        }

        $utcTz = new DateTimeZone('UTC');
        $dateTime->setTimezone($utcTz);

        return $dateTime->format('H:i');
    } catch (Exception $e) {
        return null;
    }
}