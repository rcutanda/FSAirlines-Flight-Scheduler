<?php
return [
    // Page
    'page_title' => 'Calculadora de horarios para FSairlines',
    'title' => '✈️ Calculadora de horarios para FSairlines',
    'subtitle' => 'Calcula las horas de salida y llegada en horario UTC para FSAirlines basadas en la hora del amanecer del aeropuerto de salida',
    
    // Form Labels
    'departure_icao' => 'ICAO de Salida',
    'arrival_icao' => 'ICAO de Llegada',
    'aircraft' => 'Aeronave',
    'custom_speed' => 'Velocidad Personalizada',
    'minutes_before_sunrise' => 'Minutos antes del amanecer',
    'hours_after_sunrise' => 'Horas después del amanecer',
    'cruise_altitude' => 'Altitud de Crucero',
    'sunrise_date' => 'Fecha del Amanecer',
    'climb_descent_speed' => 'Velocidad en Ascenso/Descenso',
    'buffer_time_knots' => 'Tiempo de Margen cuando se vuela en nudos',
    'buffer_time_mach' => 'Tiempo de Margen cuando se vuela en Mach',
    'climb_rate_knots' => 'Tasa Ascenso/Descenso para velocidades en nudos',
    'climb_rate_mach' => 'Tasa Ascenso/Descenso para velocidades en Mach',
    'advanced_options' => 'Opciones Avanzadas',
    
    // Placeholders
    'placeholder_departure' => 'ej., LEMD',
    'placeholder_arrival' => 'ej., KJFK',
    'placeholder_custom_speed' => 'ej., 0.8 o 450',
    'placeholder_cruise_altitude' => 'ej., 35000',
    'placeholder_sunrise_date' => 'ej., 03/20',
    
    // Help Texts
    'departure_randomized' => 'La salida será aleatoria dentro de este rango',
    'sunrise_date_format' => 'Formato: mes/día (ej., 03/20 para 20 de marzo)',
    'climb_speed_help' => 'Velocidad fija en nudos durante ascenso y descenso',
    
    // Buttons
    'calculate_times' => 'Calcular Horas',
    'next_leg' => 'Siguiente Tramo',
    'recalculate' => 'Recalcular Horas',
    'reset' => 'Reiniciar Todo',
    
    // Results
    'departure' => 'SALIDA',
    'arrival' => 'LLEGADA',
    'flight_data' => 'DATOS DEL VUELO',
    'icao' => 'ICAO',
    'name' => 'Nombre',
    'coordinates' => 'Coordenadas',
    'sunrise' => 'Amanecer',
    'departure_range' => 'Rango de Salida',
    'minutes_before' => 'min antes',
    'to' => 'hasta',
    'hours_after' => 'horas después',
    'sunrise_text' => 'del amanecer',
    'custom' => 'Personalizada',
    'distance' => 'Distancia',
    'cruise_speed' => 'Velocidad de Crucero',
    'cruise_altitude' => 'Altitud de Crucero',
    'climb_descent_speed' => 'Velocidad Ascenso/Descenso',
    'climb_descent_rate' => 'Tasa de Ascenso/Descenso',
    'flight_time' => 'Tiempo de Vuelo',
    'buffer_time' => 'Tiempo de Margen',
    'total_time' => 'Tiempo Total',
    'departure_icao' => 'ICAO DE SALIDA',
    'arrival_icao' => 'ICAO DE LLEGADA',
    'departure_time' => 'HORA DE SALIDA',
    'arrival_time' => 'HORA DE LLEGADA',
    
    // Units
    'feet' => 'pies',
    'knots' => 'nudos',
    'minutes' => 'minutos',
    'feet_per_minute' => 'pies/min',
    
    // Errors
    'error' => 'Error',
    'error_cruise_speed' => 'La velocidad de crucero debe ser mayor que 0.',
    'error_cruise_altitude' => 'La altitud de crucero debe ser mayor que 0.',
    'error_minutes_before' => 'Los minutos antes del amanecer no pueden ser negativos.',
    'error_hours_after' => 'Las horas después del amanecer deben ser mayores que 0.',
    'error_buffer_time' => 'Los tiempos de margen no pueden ser negativos.',
    'error_climb_rate' => 'Las tasas de ascenso/descenso deben ser mayores que 0.',
    'error_climb_speed' => 'La velocidad de ascenso/descenso debe ser mayor que 0.',
    'error_sunrise_api' => 'No se pudo obtener la hora del amanecer desde la API.',
	'error_both_airports' => 'Aeropuerto de salida %s no encontrado en la base de datos. %s<br><br>Aeropuerto de llegada %s no encontrado en la base de datos. %s',
    'error_departure_airport' => 'Aeropuerto de salida %s no encontrado en la base de datos. %s',
    'error_arrival_airport' => 'Aeropuerto de llegada %s no encontrado en la base de datos. %s',
    'find_in_fsa' => 'Localizar el aeropuerto solicitado en FSAirlines',


    
    // Language selector
    'lang_es' => '🇪🇸 Español',
    'lang_en' => '🇬🇧 English',
    
    // Other
    'note' => 'NOTA',

    'fsa_login_note' => 'Para que el enlace funcione es necesario haber iniciado sesión en FSA previamente.',
    'version' => 'Versión',
    'copied' => '¡Copiado al portapapeles!',
];
?>
