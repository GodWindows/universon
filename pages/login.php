<?php
    require __DIR__.  '/../vendor/autoload.php';
    require __DIR__.  '/../env_data.php'; // create this file after fetching the github code and store your client-id, client-secret and redirect uri in it
    require_once __DIR__.  '/../util/functions.php';

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

    <title><?= $site_title ?> — Connexion</title>
    <link rel="icon" href="/img/logo.ico">
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>

    <main class="login">
        <h1><?= $site_title ?></h1>
        <p>Créez votre collection musicale personnalisée</p>

        <a href="<?= $url ?>" class="btn-google">Continuer avec Google</a>

        <p class="login-terms">En vous connectant, vous acceptez nos conditions d'utilisation</p>
    </main>

</body>
</html>
