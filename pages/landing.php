<?php
    require __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../env_data.php';
    require_once __DIR__ . '/../util/functions.php';
    require_once __DIR__ . '/../util/i18n.php';

    $client = new Google\Client;
    $client->setClientId($clientID);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirect_uri);
    $client->addScope("email");
    $client->addScope("profile");

    $url = $client->createAuthUrl();

    $isLoggedIn = isset($_COOKIE['session_token']) && $_COOKIE['session_token'] !== '';
    $buttonUrl  = $isLoggedIn ? '/pages/dashboard.php' : $url;
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/universon.css">
</head>
<body>

    <div class="wrap">
        <header class="site-header">
            <a href="/" class="site-logo"><?= e('brand.wordmark') ?><b><?= e('brand.dot') ?></b></a>
            <nav>
                <a href="/@godwin" class="link-m"><?= e('nav.explore') ?></a>
                <a href="<?= htmlspecialchars($buttonUrl) ?>" class="btn btn-line"><?= e('nav.login') ?></a>
            </nav>
        </header>
    </div>

    <main>

        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-wall" aria-hidden="true">
                <?php for ($i = 0; $i < 36; $i++): ?><span class="cv<?= ($i % 16) + 1 ?>"></span><?php endfor; ?>
            </div>
            <div class="hero-in wrap">
                <span class="mo"><?= e('hero.eyebrow') ?></span>
                <h1 id="hero-title"><?= e('hero.title') ?></h1>
                <p><?= e('hero.lede') ?></p>
                <div class="cta-row">
                    <a href="<?= htmlspecialchars($buttonUrl) ?>" class="btn"><?= e('hero.cta.primary') ?></a>
                    <a href="/@godwin" class="link-m"><?= e('hero.cta.secondary') ?></a>
                </div>
            </div>
        </section>

        <div class="wrap">

            <div class="slab slab-row counters">
                <div class="slab-cell">
                    <b><?= e('counters.shelves.value') ?></b>
                    <span class="mo"><?= e('counters.shelves.label') ?></span>
                </div>
                <div class="slab-cell">
                    <b><?= e('counters.albums.value') ?></b>
                    <span class="mo"><?= e('counters.albums.label') ?></span>
                </div>
                <div class="slab-cell">
                    <b><?= e('counters.link.value') ?></b>
                    <span class="mo"><?= e('counters.link.label') ?></span>
                </div>
                <div class="slab-cell">
                    <b><?= e('counters.algo.value') ?></b>
                    <span class="mo"><?= e('counters.algo.label') ?></span>
                </div>
            </div>

            <section class="steps-section" aria-labelledby="steps-title">
                <div class="section-head">
                    <h2 id="steps-title"><?= e('steps.title') ?></h2>
                    <span class="mo"><?= e('steps.aside') ?></span>
                </div>
                <div class="slab slab-row steps">
                    <?php for ($n = 1; $n <= 3; $n++): ?>
                    <div class="slab-cell">
                        <span class="mo"><?= e("steps.$n.index") ?></span>
                        <h3><?= e("steps.$n.title") ?></h3>
                        <p><?= e("steps.$n.body") ?></p>
                    </div>
                    <?php endfor; ?>
                </div>
            </section>

            <footer class="site-footer">
                <p class="big"><?= e('footer.big') ?><b><?= e('brand.dot') ?></b></p>
                <p class="mo"><?= e('footer.rights', ['year' => date('Y')]) ?></p>
            </footer>

        </div>

    </main>

</body>
</html>
