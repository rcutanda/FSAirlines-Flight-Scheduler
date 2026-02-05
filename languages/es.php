<?php
return [
    // Page
    'page_title' => 'Calculadora de horarios para FSairlines',
    'title' => 'Calculadora de horarios para FSairlines',
    'subtitle' => 'Calcula horas de salida aleatorias en zona UTC para FSAirlines dentro de un rango personalizada y su correspondiente hora de llegada',
    
    // Form Labels
    'departure_icao' => 'ICAO de Salida',
    'arrival_icao' => 'ICAO de Llegada',
    'aircraft' => 'Aeronave',
    'custom_speed' => 'Velocidad Personalizada',
    'cruise_altitude' => 'Altitud de Crucero',
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
    
    // Help Texts
    'local_departure_time' => 'Hora de Salida Local',
    'minutes_before_departure' => 'Minutos antes de la hora de salida de referencia',
    'minutes_after_departure' => 'Minutos después de la hora de salida de referencia',
    'hours_after_departure' => 'Horas después de la hora de salida de referencia',
    'minutes_after' => 'minutos después',
    'local_departure_time_help' => 'Esta será la hora de refencia para el margen aleatorio. Se convertirá a UTC para el cálculo del vuelo.',
    'flight_mode' => 'Modo de Vuelo',
    'charter_flight' => 'Vuelo chárter',
    'daily_schedule' => 'Planificación diaria',
    'latest_arrival_time' => 'Hora local límite de llegada',
    'turnaround_time' => 'Tiempo de escala',
    'departure_randomized' => 'La salida será aleatoria dentro de este rango',
    'local_departure_text' => 'hora de salida local',
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
    'departure_range' => 'Rango de Salida',
    'minutes_before' => 'min antes',
    'to' => 'hasta',
    'hours_after' => 'horas después',
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
    'local_departure_time_reference' => 'Hora local (referencia)',
    'utc_departure_time_reference' => 'Hora UTC (referencia)',
    'actual_departure_time_local' => 'Hora local de salida',
    
    // Units
    'feet' => 'pies',
    'knots' => 'nudos',
    'minutes' => 'minutos',
    'feet_per_minute' => 'pies/min',
    
    // Errors
    'error' => 'Error',
    'error_cruise_speed' => 'La velocidad de crucero debe ser mayor que 0.',
    'error_cruise_altitude' => 'La altitud de crucero debe ser mayor que 0.',
    'error_buffer_time' => 'Los tiempos de margen no pueden ser negativos.',
    'error_climb_rate' => 'Las tasas de ascenso/descenso deben ser mayores que 0.',
    'error_climb_speed' => 'La velocidad de ascenso/descenso debe ser mayor que 0.',
    'error_time_conversion' => 'No se pudo convertir la hora local a UTC.',
    'new_day_warning' => 'La hora de llegada supera la hora local límite, por lo que el siguiente vuelo usará una nueva planificación diaria.',
    'new_day_text' => 'Inicia el ciclo del siguiente día. Se aplicará una nueva aleatorización.',
	'error_both_airports' => 'Aeropuerto de salida %s no encontrado en la base de datos. %s<br><br>Aeropuerto de llegada %s no encontrado en la base de datos. %s',
    'error_departure_airport' => 'Aeropuerto de salida %s no encontrado en la base de datos. %s',
    'error_arrival_airport' => 'Aeropuerto de llegada %s no encontrado en la base de datos. %s',
    'find_in_fsa' => 'Localizar el aeropuerto solicitado en FSAirlines',
	'timezone_api_warning_title' => 'Problema de Conexión con el Servidor de Zona Horaria',
	'timezone_api_warning_message' => 'No ha sido posible conectar con el servidor encargado de la conversión horaria. Por favor, usa el botón "Recalcular Horas" para volver a intentarlo',
    
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
