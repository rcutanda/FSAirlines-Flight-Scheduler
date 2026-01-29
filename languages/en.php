<?php
return [
    // Page
    'page_title' => 'FSAirlines Flight Scheduler',
    'title' => 'FSAirlines Flight Scheduler',
    'subtitle' => 'Calculate departure and arrival times in UTC for FSAirlines based on the sunrise time at the departure airport',
    
    // Form Labels
    'departure_icao' => 'Departure ICAO',
    'arrival_icao' => 'Arrival ICAO',
    'aircraft' => 'Aircraft',
    'custom_speed' => 'Custom Speed',
    'minutes_before_sunrise' => 'Minutes before sunrise',
    'hours_after_sunrise' => 'Hours after sunrise',
    'cruise_altitude' => 'Cruise Altitude',
    'sunrise_date' => 'Sunrise Date',
    'climb_descent_speed' => 'Climb/Descent Speed',
    'buffer_time_knots' => 'Buffer Time when flying in knots',
    'buffer_time_mach' => 'Buffer Time when flying in Mach',
    'climb_rate_knots' => 'Climb/Descent Rate for knots speeds',
    'climb_rate_mach' => 'Climb/Descent Rate for Mach speeds',
    'advanced_options' => 'Advanced Options',
    
    // Placeholders
    'placeholder_departure' => 'e.g., LEMD',
    'placeholder_arrival' => 'e.g., KJFK',
    'placeholder_custom_speed' => 'e.g., 0.8 or 450',
    'placeholder_cruise_altitude' => 'e.g., 35000',
    'placeholder_sunrise_date' => 'e.g., 03/20',
    
    // Help Texts
    'departure_randomized' => 'Departure will be randomised within this range',
    'sunrise_date_format' => 'Format: month/day (e.g., 03/20 for 20 March)',
    'climb_speed_help' => 'Fixed speed in knots during climb and descent',
    
    // Buttons
    'calculate_times' => 'Calculate Times',
    'next_leg' => 'Next Leg',
    'recalculate' => 'Recalculate Schedule',
    'reset' => 'Reset All',
    
    // Results
    'departure' => 'DEPARTURE',
    'arrival' => 'ARRIVAL',
    'flight_data' => 'FLIGHT DATA',
    'icao' => 'ICAO',
    'name' => 'Name',
    'coordinates' => 'Coordinates',
    'sunrise' => 'Sunrise',
    'departure_range' => 'Departure Range',
    'minutes_before' => 'mins before',
    'to' => 'to',
    'hours_after' => 'hours after',
    'sunrise_text' => 'sunrise',
    'custom' => 'Custom',
    'distance' => 'Distance',
    'cruise_speed' => 'Cruise Speed',
    'cruise_altitude' => 'Cruise Altitude',
    'climb_descent_speed' => 'Climb/Descent Speed',
    'climb_descent_rate' => 'Climb/Descent Rate',
    'flight_time' => 'Flight Time',
    'buffer_time' => 'Buffer Time',
    'total_time' => 'Total Time',
    'departure_icao' => 'DEPARTURE ICAO',
    'arrival_icao' => 'ARRIVAL ICAO',
    'departure_time' => 'DEPARTURE TIME',
    'arrival_time' => 'ARRIVAL TIME',
    
    // Units
    'feet' => 'feet',
    'knots' => 'knots',
    'minutes' => 'minutes',
    'feet_per_minute' => 'ft/min',
    
    // Errors
    'error' => 'Error',
    'error_cruise_speed' => 'Cruise speed must be greater than 0.',
    'error_cruise_altitude' => 'Cruise altitude must be greater than 0.',
    'error_minutes_before' => 'Minutes before sunrise cannot be negative.',
    'error_hours_after' => 'Hours after sunrise must be greater than 0.',
    'error_buffer_time' => 'Buffer times cannot be negative.',
    'error_climb_rate' => 'Climb/descent rates must be greater than 0.',
    'error_climb_speed' => 'Climb/descent speed must be greater than 0.',
    'error_sunrise_api' => 'Unable to retrieve sunrise time from the API.',
	 'error_both_airports' => 'Departure airport %s not found in the database. %s<br><br>Arrival airport %s not found in the database. %s',
    'error_departure_airport' => 'Departure airport %s not found in the database. %s',
    'error_arrival_airport' => 'Arrival airport %s not found in the database. %s',
    'find_in_fsa' => 'Find the requested airport in FSAirlines',


    
    // Language selector
    'lang_es' => '🇪🇸 Español',
    'lang_en' => '🇬🇧 English',
    
    // Other
    'note' => 'NOTE',

    'fsa_login_note' => 'For the link to work, you must be logged into FSA beforehand.',
    'version' => 'Version',
    'copied' => 'Copied to clipboard!',
];
?>
