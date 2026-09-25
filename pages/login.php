<?php
    require __DIR__.  '/../vendor/autoload.php';
    require __DIR__.  '/../env_data.php'; // create this file after fetching the github code and store your client-id, client-secret and redirect uri in it
    require_once __DIR__.  '/../util/functions.php';
    require_once __DIR__.  '/../util/i18n.php';

    $client = new Google\Client;
    $client->setClientId($clientID);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirect_uri);
    $client->addScope("email");
    $client->addScope("profile ");

    $url = $client->createAuthUrl();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Connectez-vous à Universon pour créer et gérer votre collection musicale personnelle. Organisez vos albums préférés et partagez votre profil musical.">
    <meta name="keywords" content="universon, connexion, login, musique, collection musicale, profil musical">
    <meta name="author" content="Universon">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://universon.fr/pages/login.php">
    <meta property="og:title" content="Universon - Connexion">
    <meta property="og:description" content="Créez votre univers musical personnel avec Universon.">
    <meta property="og:site_name" content="Universon">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="https://universon.fr/pages/login.php">
    <meta name="twitter:title" content="Universon - Connexion">
    <meta name="twitter:description" content="Créez votre univers musical personnel avec Universon.">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">

    <title><?= htmlspecialchars($site_title) ?> — Connexion</title>
    <link rel="icon" href="/img/logo.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/universon.css">
</head>
<body>

    <main class="login">
        <div class="login-in">
            <h1><a href="/" class="site-logo"><?= e('brand.wordmark') ?><b><?= e('brand.dot') ?></b></a></h1>
            <p><?= e('login.tagline') ?></p>

            <a href="<?= htmlspecialchars($url) ?>" class="btn btn-google"><?= e('login.google') ?></a>

            <p class="mo login-terms"><?= e('login.terms') ?></p>
        </div>
    </main>

</body>
</html>
