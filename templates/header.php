<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="css/style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['page_title']; ?> <?php echo VERSION; ?></title>
<script>
function saveDeparturDefault() {
    const time = document.getElementById('local_departure_time').value;
    if (!time) {
        alert('Please select a time first');
        return;
    }
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'save_departure_default=1&departure_time=' + encodeURIComponent(time)
    }).then(() => location.reload());
}

function saveArrivalDefault() {
    const time = document.getElementById('latest_arrival_time').value;
    if (!time) {
        alert('Please select a time first');
        return;
    }
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'save_arrival_default=1&arrival_time=' + encodeURIComponent(time)
    }).then(() => location.reload());
}
</script>
</head>
<body>
    <div class="language-selector">
            <a href="?lang=es" class="<?php echo $current_language === 'es' ? 'active' : ''; ?>" title="<?php echo 'debug: ' . htmlspecialchars($current_language); ?>">
                <img src="languages/es.svg" alt="Español">
            </a>
            <a href="?lang=en" class="<?php echo $current_language === 'en' ? 'active' : ''; ?>" title="<?php echo 'debug: ' . htmlspecialchars($current_language); ?>">
                <img src="languages/gb.svg" alt="English">
            </a>
    </div>

    <div class="container">
        <h1><?php echo $lang['title']; ?></h1>
        <center><img src="favicon.png"></center>
        <p class="subtitle"><?php echo $lang['subtitle']; ?></p>

        <?php if (!empty($error)): ?>
            <div style="color: red; margin: 10px 0;">
                ⚠️ Error: <?php echo htmlspecialchars($error); ?>
                <?php if (strpos($error, 'not found in the database') !== false): ?>
                    <div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; color: #856404; font-size: 13px;">
                        <strong><?php echo $lang['note']; ?>:</strong> <?php echo $lang['fsa_login_note']; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
