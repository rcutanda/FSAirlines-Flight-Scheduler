<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="css/style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['page_title']; ?> v<?php echo VERSION; ?></title>
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
        <center><img src="favicon.png"></center>
        <p class="subtitle"><?php echo $lang['subtitle']; ?></p>
