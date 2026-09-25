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

    // Le mur du bandeau : de vraies pochettes de profils publics (§ 6.1).
    $wallCovers = get_public_wall_covers(40);
?><!DOCTYPE html>
<html lang="<?= APP_LOCALE ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e('meta.landing.description') ?>">
    <meta name="keywords" content="<?= e('meta.landing.keywords') ?>">
    <meta name="author" content="Universon">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($site_url) ?>/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Universon">
    <meta property="og:locale" content="<?= e('meta.og_locale') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($site_url) ?>">
    <meta property="og:title" content="<?= e('meta.landing.title') ?>">
    <meta property="og:description" content="<?= e('meta.landing.og_description') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($site_url) ?>/img/planet.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e('meta.landing.title') ?>">
    <meta name="twitter:description" content="<?= e('meta.landing.og_description') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($site_url) ?>/img/planet.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/img/logo.ico">
    <title><?= e('meta.landing.title') ?></title>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Universon",
        "url": "<?= htmlspecialchars($site_url) ?>/",
        "applicationCategory": "MultimediaApplication",
        "operatingSystem": "Web",
        "inLanguage": <?= json_encode(APP_LOCALE) ?>,
        "description": <?= json_encode(t('meta.landing.og_description'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
        "offers": { "@type": "Offer", "price": "0", "priceCurrency": "EUR" },
        "keywords": <?= json_encode(t('meta.landing.keywords'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>
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
                <?= lang_switcher() ?>
            </nav>
        </header>
    </div>

    <main>

        <!-- Bandeau : le mur de pochettes est l'interface (§ 1.1). Les deux photos
             y sont posées comme des pochettes géantes, calées sur la grille ; le
             texte flotte sur un voile qui se retire vers la droite. -->
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-wall">
                <figure class="hero-tile hero-tile--lead">
                    <img src="/img/landing/street-phone.webp" width="1600" height="900"
                         alt="<?= e('hero.img_alt') ?>" fetchpriority="high">
                </figure>
                <figure class="hero-tile hero-tile--second">
                    <img src="/img/landing/flatlay.webp" width="1200" height="1200"
                         alt="<?= e('hero.img2_alt') ?>">
                </figure>
                <?php foreach ($wallCovers as $cover): ?>
                <div class="hero-tile hero-tile--cover">
                    <img class="cover" src="<?= htmlspecialchars($cover['image_url']) ?>" width="300" height="300"
                         alt="<?= htmlspecialchars($cover['name']) ?>" decoding="async" onerror="this.remove();">
                </div>
                <?php endforeach; ?>
            </div>
            <div class="wrap hero-in">
                <div class="hero-copy">
                    <span class="mo"><?= e('hero.eyebrow') ?></span>
                    <h1 id="hero-title"><?= e('hero.title') ?></h1>
                    <p><?= e('hero.lede') ?></p>
                    <div class="cta-row">
                        <a href="<?= htmlspecialchars($buttonUrl) ?>" class="btn"><?= e('hero.cta.primary') ?></a>
                        <a href="/@godwin" class="link-m"><?= e('hero.cta.secondary') ?></a>
                    </div>
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

            <!-- Vitrine : captures brutes de vrais profils publics, cliquables. -->
            <section class="showcase" aria-labelledby="showcase-title">
                <div class="showcase-copy">
                    <span class="mo"><?= e('showcase.aside') ?></span>
                    <h2 id="showcase-title"><?= e('showcase.title') ?></h2>
                    <p><?= e('showcase.body') ?></p>
                </div>
                <div class="slab slab-row showcase-row">
                    <?php foreach (['godwin', 'yann', 'max'] as $pseudo): ?>
                    <a href="/@<?= $pseudo ?>" class="slab-cell screen-card">
                        <img src="/img/landing/screen-<?= $pseudo ?>.webp" width="780" height="1688" loading="lazy"
                             alt="<?= e('showcase.alt', ['pseudo' => $pseudo]) ?>">
                        <span class="mo">@<?= $pseudo ?> →</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="steps-section" aria-labelledby="steps-title">
                <div class="section-head">
                    <h2 id="steps-title"><?= e('steps.title') ?></h2>
                    <span class="mo"><?= e('steps.aside') ?></span>
                </div>
                <div class="slab slab-row steps">
                    <?php foreach ([1 => 'desk-laptop', 2 => 'adding-album', 3 => 'friends-sharing'] as $n => $photo): ?>
                    <div class="slab-cell">
                        <img src="/img/landing/<?= $photo ?>.webp" width="1400" height="1050" loading="lazy"
                             class="step-photo" alt="<?= e("steps.$n.img_alt") ?>">
                        <span class="mo"><?= e("steps.$n.index") ?></span>
                        <h3><?= e("steps.$n.title") ?></h3>
                        <p><?= e("steps.$n.body") ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Détails du produit, en captures brutes. -->
            <section class="details" aria-labelledby="details-title">
                <div class="section-head">
                    <h2 id="details-title"><?= e('details.title') ?></h2>
                </div>
                <figure class="wall-strip">
                    <img src="/img/landing/screen-wall.webp" width="2762" height="326" loading="lazy"
                         alt="<?= e('details.wall_alt') ?>">
                    <figcaption class="mo"><?= e('details.wall_caption') ?></figcaption>
                </figure>
                <div class="detail-row">
                    <div>
                        <h3><?= e('details.toggle.title') ?></h3>
                        <p><?= e('details.toggle.body') ?></p>
                    </div>
                    <figure class="detail-shot">
                        <img src="/img/landing/screen-toggle.webp" width="2784" height="168" loading="lazy"
                             alt="<?= e('details.toggle_alt') ?>">
                    </figure>
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
