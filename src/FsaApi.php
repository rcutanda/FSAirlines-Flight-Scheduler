<?php
declare(strict_types=1);

// Function to get airport coordinates and name from FSAirlines API
function getAirportData($icao) {
    try {
        $url = 'https://www.fsairlines.net/va_interface2.php?function=getAirportData&va_id=' . FSA_VA_ID . '&icao=' . urlencode($icao) . '&apikey=' . FSA_API_KEY;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FSAirlines-Flight-Scheduler/1.1');
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return ['status' => 'connection_error', 'data' => null];
        }

        if ($httpCode !== 200) {
            return ['status' => 'connection_error', 'data' => null];
        }

        if ($response === false) {
            return ['status' => 'connection_error', 'data' => null];
        }

        $xml = simplexml_load_string($response);
        if ($xml === false) {
            return ['status' => 'connection_error', 'data' => null];
        }

        if ((string)$xml['success'] !== 'SUCCESS') {
            return ['status' => 'not_found', 'data' => null];
        }

        $data = $xml->data;
        $attrs = $data->attributes();
        if (isset($attrs['lat']) && isset($attrs['lon']) && isset($attrs['name'])) {
            return ['status' => 'success', 'data' => [
                'lat' => floatval($attrs['lat']),
                'lon' => floatval($attrs['lon']),
                'name' => (string)$attrs['name'],
                // Altitude is returned only if the API provides it in the response attributes.
                'altitude' => isset($attrs['altitude']) ? floatval($attrs['altitude']) : null
            ]];
        }
        return ['status' => 'not_found', 'data' => null];
    } catch (Exception $e) {
        return ['status' => 'connection_error', 'data' => null];
    }
}
