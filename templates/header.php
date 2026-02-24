<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="css/style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['page_title']; ?> <?php echo VERSION; ?></title>

<script>
window.FSA_SCHEDULER = {
  PREF_STORAGE_KEY: <?php echo json_encode(fsa_defaults()['pref_storage_key'], JSON_UNESCAPED_SLASHES); ?>,
  DEFAULT_PREFERENCES: <?php
    $DEF = fsa_defaults();
    echo json_encode([
      'local_departure_time' => $DEF['local_departure_time'],
      'latest_departure_time' => $DEF['latest_departure_time'],
      'aircraft' => array_key_first($aircraft_list) ?: reset(array_keys($aircraft_list)) ?? 'AIRBUS A220-100 (BCS1)',
      'minutes_before_departure' => $DEF['minutes_before_departure'],
      'hours_after_departure_charter' => $DEF['hours_after_departure_charter'],
      'hours_after_departure_daily_schedule' => $DEF['hours_after_departure_daily_schedule'],
      'buffer_time_knots' => $DEF['buffer_time_knots'],
      'buffer_time_mach' => $DEF['buffer_time_mach'],
      'flight_mode' => $DEF['flight_mode'],
      'turnaround_time' => $DEF['turnaround_time'],
      'short_haul' => $DEF['short_haul'],
      'medium_haul' => $DEF['medium_haul'],
      'long_haul' => $DEF['long_haul'],
      'ultra_long_haul' => $DEF['ultra_long_haul'],

      'pref_storage_key' => $DEF['pref_storage_key'],

      'cruise_range_corr_enabled' => $DEF['cruise_range_corr_enabled'],
      'cruise_range_thr1_nm' => $DEF['cruise_range_thr1_nm'],
      'cruise_range_thr2_nm' => $DEF['cruise_range_thr2_nm'],
      'cruise_range_thr3_nm' => $DEF['cruise_range_thr3_nm'],
      'cruise_range_pp_lt_thr1' => $DEF['cruise_range_pp_lt_thr1'],
      'cruise_range_pp_thr1_thr2' => $DEF['cruise_range_pp_thr1_thr2'],
      'cruise_range_pp_thr2_thr3' => $DEF['cruise_range_pp_thr2_thr3'],
      'cruise_range_pp_ge_thr3' => $DEF['cruise_range_pp_ge_thr3']
    ], JSON_UNESCAPED_SLASHES);
  ?>,
  RESET_ALL_CONFIRM_MSG: <?php echo json_encode($lang['reset_all_confirm'], JSON_UNESCAPED_SLASHES); ?>,
  SAVED_DEFAULT_MSG: <?php echo json_encode($lang['saved_default'] ?? 'Saved', JSON_UNESCAPED_SLASHES); ?>,
  COPIED_MSG: <?php echo json_encode($lang['copied'] ?? 'Copied to clipboard!', JSON_UNESCAPED_SLASHES); ?>,
  IS_NEXT_LEG: <?php echo json_encode((bool)$is_next_leg); ?>,
  IS_NEW_DAY: <?php echo json_encode((bool)$is_new_day); ?>,
  IS_RESET_ALL: <?php echo json_encode(isset($_GET['reset_all']) && $_GET['reset_all'] === '1'); ?>,

  CRUISE_RANGE_CORR_SANITIZED: <?php echo json_encode((($cruise_range_corr_sanitized ?? '0') === '1')); ?>,
  CRUISE_RANGE_CORR_SANITIZED_MSG: <?php echo json_encode($cruise_range_corr_sanitized_msg ?? '', JSON_UNESCAPED_SLASHES); ?>,
  CRUISE_RANGE_CORR_SANITIZED_VALUES: <?php echo json_encode([
    'cruise_range_corr_enabled' => (string)($cruise_range_corr_enabled ?? ''),
    'cruise_range_thr1_nm' => (string)($cruise_range_thr1_nm ?? ''),
    'cruise_range_thr2_nm' => (string)($cruise_range_thr2_nm ?? ''),
    'cruise_range_thr3_nm' => (string)($cruise_range_thr3_nm ?? ''),
    'cruise_range_pp_lt_thr1' => (string)($cruise_range_pp_lt_thr1 ?? ''),
    'cruise_range_pp_thr1_thr2' => (string)($cruise_range_pp_thr1_thr2 ?? ''),
    'cruise_range_pp_thr2_thr3' => (string)($cruise_range_pp_thr2_thr3 ?? ''),
    'cruise_range_pp_ge_thr3' => (string)($cruise_range_pp_ge_thr3 ?? ''),
  ], JSON_UNESCAPED_SLASHES); ?>
};
</script>

<script defer src="js/app.js?v=<?php echo rawurlencode(VERSION); ?>"></script>
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
		<center><?php echo $lang['subtitle']; ?><br>
        <img src="favicon.png"></center>
        <p class="subtitle"></p>
