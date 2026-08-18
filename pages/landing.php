<?php
    require __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../env_data.php';
    require_once __DIR__ . '/../util/functions.php';

    $client = new Google\Client;
    $client->setClientId($clientID);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirect_uri);
    $client->addScope("email");
    $client->addScope("profile");

    $url = $client->createAuthUrl();

    $isLoggedIn = isset($_COOKIE['session_token']) && $_COOKIE['session_token'] !== '';
    $buttonUrl = $isLoggedIn ? '/pages/dashboard.php' : $url;
    $buttonText = $isLoggedIn ? 'Accéder à votre profil' : 'Se connecter avec Google';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Créez votre profil musical, montrez vos musiques et partagez votre collection d'albums préférés en un seul lien. Universon met en valeur votre univers musical.">
    <meta name="keywords" content="profil musical, montrer mes musiques, partager ses musiques, collection musicale, collection d'albums, albums préférés, exposer ses albums, univers musical">
    <meta name="author" content="Universon">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($site_url) ?>/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Universon">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:url" content="<?= htmlspecialchars($site_url) ?>">
    <meta property="og:title" content="Universon — Montrez et partagez votre univers musical">
    <meta property="og:description" content="Créez votre profil musical et partagez votre collection d'albums préférés en un seul lien.">
    <meta property="og:image" content="<?= htmlspecialchars($site_url) ?>/img/planet.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Universon — Montrez et partagez votre univers musical">
    <meta name="twitter:description" content="Créez votre profil musical et partagez votre collection d'albums préférés en un seul lien.">
    <meta name="twitter:image" content="<?= htmlspecialchars($site_url) ?>/img/planet.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/img/logo.ico">
    <title>Universon — Montrez et partagez votre univers musical</title>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Universon",
        "url": "<?= htmlspecialchars($site_url) ?>/",
        "applicationCategory": "MultimediaApplication",
        "operatingSystem": "Web",
        "inLanguage": "fr",
        "description": "Créez votre profil musical, montrez vos musiques et partagez votre collection d'albums préférés en un seul lien.",
        "offers": { "@type": "Offer", "price": "0", "priceCurrency": "EUR" },
        "keywords": "profil musical, montrer mes musiques, partager ses musiques, collection musicale, albums préférés, univers musical"
    }
    </script>
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>

    <header class="site-header">
        <a href="/" class="site-logo">Universon</a>
        <a href="<?= htmlspecialchars($buttonUrl) ?>" class="login-link"><?= htmlspecialchars($buttonText) ?></a>
    </header>

    <main>

        <section class="hero" aria-labelledby="hero-title">
            <h1 id="hero-title">Universon</h1>
            <p class="hero-tagline">Exposez vos albums comme des œuvres d'art</p>
            <p class="hero-description">Composez votre collection musicale, montrez vos musiques et partagez vos albums préférés en un seul lien.</p>
            <a href="<?= htmlspecialchars($buttonUrl) ?>" class="hero-cta">Créer mon profil musical</a>
        </section>

        <section class="intro" aria-labelledby="intro-title">
            <h2 id="intro-title">Manifeste</h2>
            <blockquote>
                <p>Chaque album est une œuvre d'art. Chaque collection raconte une histoire unique.</p>
            </blockquote>
            <p>Universon est l'endroit où votre passion musicale prend vie. Organisez et partagez vos albums préférés dans une collection personnelle qui vous ressemble.</p>
        </section>

        <section class="features" aria-labelledby="features-title">
            <h2 id="features-title">Fonctionnalités</h2>
            <ul>
                <li>
                    <h3>Collection personnelle</h3>
                    <p>Présentez vos albums dans une collection qui vous appartient.</p>
                </li>
                <li>
                    <h3>Recherche d'albums</h3>
                    <p>Accédez à des millions d'albums et enrichissez votre collection musicale.</p>
                </li>
                <li>
                    <h3>Profil public</h3>
                    <p>Obtenez votre URL personnalisée (@username) et partagez votre univers musical avec qui vous voulez, quand vous voulez.</p>
                </li>
                <li>
                    <h3>Personnalisation</h3>
                    <p>Organisez votre collection par coups de cœur, albums les plus écoutés, et ajoutez vos notes personnelles.</p>
                </li>
            </ul>
        </section>

        <section class="steps" aria-labelledby="steps-title">
            <h2 id="steps-title">Trois étapes</h2>
            <ol>
                <li>
                    <h3>Connectez-vous</h3>
                    <p>Créez votre compte en quelques secondes avec Google. Simple, rapide, sécurisé.</p>
                </li>
                <li>
                    <h3>Ajoutez vos albums</h3>
                    <p>Recherchez vos albums favoris et construisez votre collection unique.</p>
                </li>
                <li>
                    <h3>Partagez votre univers</h3>
                    <p>Votre profil public est prêt. Inspirez d'autres passionnés de musique avec vos découvertes.</p>
                </li>
            </ol>
        </section>

        <section class="final-cta" aria-labelledby="final-cta-title">
            <h2 id="final-cta-title">Créez votre profil musical</h2>
            <a href="<?= htmlspecialchars($buttonUrl) ?>"><?= htmlspecialchars($buttonText) ?></a>
        </section>

    </main>

    <footer class="site-footer">
        <p>© <?= date('Y') ?> Universon</p>
        <p>Votre univers musical à partager</p>
    </footer>

</body>
</html>
