<?php
return [
    // Page
    'page_title' => 'FSAirlines Flight Scheduler',
    'title' => 'FSAirlines Flight Scheduler',
    'subtitle' => 'Calculate random departure times in UTC for FSAirlines within a specified range, and the corresponding arrival',
    
    // Form Labels
    'departure_icao' => 'Departure ICAO',
    'arrival_icao' => 'Arrival ICAO',
    'aircraft' => 'Aircraft',
    'custom_speed' => 'Custom Speed',
    'cruise_altitude' => 'Cruise Altitude',
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
    
    // Help Texts
    'local_departure_time' => 'Local Departure Time',
    'minutes_before_departure' => 'Minutes before reference time for departure',
    'minutes_after_departure' => 'Minutes after reference time for departure',
    'hours_after_departure' => 'Hours after reference time for departure',
    'minutes_after' => 'minutes after',
    'local_departure_time_help' => 'Reference time for the randomisation. Will be converted to UTC for flight calculation.',
    'flight_mode' => 'Flight Mode',
    'charter_flight' => 'Charter flight',
    'daily_schedule' => 'Daily schedule',
    'latest_arrival_time' => 'Latest local arrival time allowed',
    'turnaround_time' => 'Turnaround time',
    'departure_randomized' => 'Departure will be randomised within this range',
    'local_departure_text' => 'local departure time',
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
    'departure_range' => 'Departure Range',
    'minutes_before' => 'mins before',
    'to' => 'to',
    'hours_after' => 'hours after',
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
    'departure_time' => 'DEPARTURE TIME (UTC)',
    'arrival_time' => 'ARRIVAL TIME (UTC)',
    'local_departure_time_reference' => 'Local time (reference)',
    'utc_departure_time_reference' => 'UTC time (reference)',
    'actual_departure_time_local' => 'Local departure time',
    'actual_arrival_time_local' => 'Local arrival time',

    // Units
    'feet' => 'feet',
    'knots' => 'knots',
    'minutes' => 'minutes',
    'feet_per_minute' => 'ft/min',
    
    // Errors
    'error' => 'Error',
    'error_cruise_speed' => 'Cruise speed must be greater than 0.',
    'error_cruise_altitude' => 'Cruise altitude must be greater than 0.',
    'error_buffer_time' => 'Buffer times cannot be negative.',
    'error_climb_rate' => 'Climb/descent rates must be greater than 0.',
    'error_climb_speed' => 'Climb/descent speed must be greater than 0.',
    'error_time_conversion' => 'Unable to convert local time to UTC.',
    'new_day_warning' => 'The arrival time exceeds the latest local arrival time allowed. The next flight will use a new daily schedule.',
    'new_day_text' => 'Next day cycle starting. New randomisation will be applied.',
	'error_both_airports' => 'Departure airport %s not found in the database. %s<br><br>Arrival airport %s not found in the database. %s',
    'error_departure_airport' => 'Departure airport %s not found in the database. %s',
    'error_arrival_airport' => 'Arrival airport %s not found in the database. %s',
	'timezone_api_warning_title' => 'Connection Problem with Timezone Server',
	'timezone_api_warning_message' => 'There was a connection problem with the server in charge of making the timezone conversion. Please, <b>use the "Recalculate Schedule"</b> button to try again.',
        
    // Other
    'note' => 'NOTE',

    'fsa_login_note' => 'For the link to work, you must be logged into FSA beforehand.',
    'version' => 'Version',
    'copied' => 'Copied to clipboard!',
];
?>
