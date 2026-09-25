<?php
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../env_data.php';
    require_once __DIR__ . '/../util/functions.php';
    require_once __DIR__ . '/../util/i18n.php';

    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $username = null;
    if (preg_match('#/@([A-Za-z0-9_.-]+)$#', $requestPath, $matches)) {
        $username = $matches[1];
    } elseif (isset($_GET['u']) && $_GET['u'] !== '') {
        $username = trim($_GET['u']);
    }

    /* ─── common head helper ─────────────────────────────────────────── */
    function pp_head($title, $description = '') {
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="' . htmlspecialchars($description) . '">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/img/logo.ico">
    <title>' . htmlspecialchars($title) . '</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/universon.css">
</head>
<body>';
    }

    /* ─── common header ──────────────────────────────────────────────── */
    function pp_header($logoutBtn = false) {
        echo '<div class="wrap">
    <header class="site-header">
        <a href="/" class="site-logo">' . e('brand.wordmark') . '<b>' . e('brand.dot') . '</b></a>';
        if ($logoutBtn) {
            echo '
        <nav>
            <button type="button" id="logoutBtn" class="btn btn-line">' . e('nav.logout') . '</button>
        </nav>';
        }
        echo '
    </header>
</div>';
    }

    /* ─── shared error screen (§ 5.20, § 6.5) ────────────────────────── */
    function pp_error($key, $vars = []) {
        echo '
<main class="error">
    <h1>' . e('error.' . $key . '.title') . '</h1>
    <p>' . e('error.' . $key . '.body', $vars) . '</p>
    <a href="/" class="btn">' . e('error.back') . '</a>
</main>
</body>
</html>';
    }

    /* ─── 400 ─────────────────────────────────────────────────────────── */
    if (!$username) {
        http_response_code(400);
        pp_head($site_title . ' — ' . t('error.badrequest.title'), t('error.badrequest.body'));
        pp_header();
        pp_error('badrequest');
        exit();
    }

    $publicUser = get_user_public_min_by_pseudo($username);

    /* ─── 404 ─────────────────────────────────────────────────────────── */
    if ($publicUser === null) {
        http_response_code(404);
        pp_head(
            $site_title . ' — ' . t('error.notfound.title'),
            t('error.notfound.body', ['pseudo' => $username])
        );
        pp_header();
        pp_error('notfound', ['pseudo' => $username]);
        exit();
    }

    /* ─── PRIVATE ─────────────────────────────────────────────────────── */
    if ($publicUser['profile_visibility'] !== 'public') {
        pp_head($site_title . ' — ' . t('error.private.title'), t('error.private.body'));
        pp_header();
        pp_error('private');
        exit();
    }

    /* ─── PUBLIC PROFILE ─────────────────────────────────────────────── */
    $viewer = (isset($_COOKIE['session_token']) && $_COOKIE['session_token'] !== '')
        ? getUserFromSessionToken($_COOKIE['session_token'])
        : null;

    $conn = connect_database();
    $categories = [];
    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT name, description FROM album_categories ORDER BY name ASC");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
        }
    }

    $categoriesAlbums = [];
    foreach ($categories as $category) {
        $categoriesAlbums[$category['name']] = get_user_albums_by_category($publicUser['id'], $category['name']);
    }

    $hasLogout    = (bool) $viewer;
    $totalAlbums  = array_sum(array_map('count', $categoriesAlbums));

    $profileName = htmlspecialchars($publicUser['firstName'] . (!empty($publicUser['lastName']) ? ' ' . $publicUser['lastName'] : ''));
    $shareUrl = htmlspecialchars($site_url) . '/@' . htmlspecialchars($publicUser['pseudo']);
    $bioMeta = !empty($publicUser['bio']) ? htmlspecialchars(substr($publicUser['bio'], 0, 200)) : 'Découvrez ma collection musicale sur Universon';

    pp_head(
        $site_title . ' — ' . $profileName . ' (@' . htmlspecialchars($publicUser['pseudo']) . ')',
        'Découvrez la collection musicale de @' . htmlspecialchars($publicUser['pseudo']) . ' sur Universon. ' . $bioMeta
    );

    // Extra meta for public profile
    $profileImage = !empty($publicUser['picture'])
        ? htmlspecialchars($publicUser['picture'])
        : htmlspecialchars($site_url) . '/img/planet.png';
    $profileKeywords = 'profil musical, ' . $profileName . ', @' . htmlspecialchars($publicUser['pseudo'])
        . ', collection musicale, albums préférés, partager ses musiques, univers musical';

    echo '<meta name="keywords" content="' . $profileKeywords . '">
<meta name="robots" content="index, follow">
<meta property="og:type" content="profile">
<meta property="og:site_name" content="Universon">
<meta property="og:locale" content="fr_FR">
<meta property="og:url" content="' . $shareUrl . '">
<meta property="og:title" content="' . $profileName . ' — Universon">
<meta property="og:description" content="' . $bioMeta . '">
<meta property="og:image" content="' . $profileImage . '">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="' . $profileName . ' — Universon">
<meta name="twitter:description" content="' . $bioMeta . '">
<meta name="twitter:image" content="' . $profileImage . '">
<link rel="canonical" href="' . $shareUrl . '">
<script type="application/ld+json">' . json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ProfilePage',
        'inLanguage' => 'fr',
        'url' => $shareUrl,
        'name' => $profileName . ' — Universon',
        'description' => $bioMeta,
        'mainEntity' => [
            '@type' => 'Person',
            'name' => $profileName,
            'alternateName' => '@' . $publicUser['pseudo'],
            'url' => $shareUrl,
            'image' => $profileImage,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

    pp_header($hasLogout);

    /* Rendu d'un mur en lecture seule. Aucun bouton de retrait ici (§ 6.4). */
    function pp_wall(array $albums) {
        echo '<ul class="album-list">';
        $i = 0;
        foreach ($albums as $album) {
            $i++;
            $imgSrc = !empty($album['image_url_100']) ? $album['image_url_100'] : ($album['image_url_60'] ?? '');
            echo '<li class="album">';
            if ($imgSrc) {
                echo '<img src="' . htmlspecialchars($imgSrc) . '"'
                   . ' alt="' . htmlspecialchars($album['name']) . '"'
                   . ' loading="lazy" onerror="this.remove();">';
            }
            echo '<span class="album-idx">' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</span>';
            echo '<div class="album-meta">';
            echo '<h3 class="album-title">' . htmlspecialchars($album['name']) . '</h3>';
            if (!empty($album['artist_name'])) {
                echo '<p class="album-artist">' . htmlspecialchars($album['artist_name']) . '</p>';
            }
            echo '</div></li>';
        }
        echo '</ul>';
    }
?>

<main>

    <div class="wrap">
        <section class="profile" aria-labelledby="profile-title">
            <?php if (!empty($publicUser['picture'])): ?>
                <img src="<?= htmlspecialchars($publicUser['picture']) ?>" alt="<?= $profileName ?>" class="profile-avatar">
            <?php endif; ?>

            <div>
                <h1 id="profile-title"><?= $profileName ?></h1>
                <p><span class="pseudo-display">@<?= htmlspecialchars($publicUser['pseudo']) ?></span></p>

                <?php if (!empty($publicUser['bio'])): ?>
                    <p class="bio"><?= htmlspecialchars($publicUser['bio']) ?></p>
                <?php else: ?>
                    <p class="bio"><?= e('profile.bio.empty') ?></p>
                <?php endif; ?>

                <div class="profile-actions">
                    <button type="button" id="shareProfileBtn" class="btn" data-share-url="<?= $shareUrl ?>">
                        <?= e('profile.share') ?>
                    </button>
                </div>

                <div class="stats-row">
                    <div>
                        <span class="mo"><?= e('profile.stat.albums') ?></span>
                        <b><?= (int) $totalAlbums ?></b>
                    </div>
                    <div>
                        <span class="mo"><?= e('profile.stat.shelves') ?></span>
                        <b><?= count($categories) ?></b>
                    </div>
                    <?php if (!empty($publicUser['created_at'])): ?>
                    <div>
                        <span class="mo"><?= e('profile.stat.since') ?></span>
                        <b><?= htmlspecialchars(date('Y', strtotime($publicUser['created_at']))) ?></b>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if (!empty($categories)): ?>
            <?php $catIndex = 0; foreach ($categories as $category):
                $catIndex++;
                $albums = $categoriesAlbums[$category['name']] ?? [];
                $headId = 'cat-' . $category['name'];
            ?>
            <section class="category" aria-labelledby="<?= htmlspecialchars($headId) ?>">
                <div class="cat-head">
                    <h2 id="<?= htmlspecialchars($headId) ?>"><?= e('category.' . $category['name']) ?></h2>
                    <div class="cat-head-aside">
                        <span class="mo cat-meta"><?= e('category.meta', [
                            'count' => count($albums),
                            'n'     => str_pad((string) $catIndex, 2, '0', STR_PAD_LEFT),
                        ]) ?></span>
                    </div>
                </div>

                <?php if (!empty($albums)): ?>
                    <?php pp_wall($albums); ?>
                <?php else: ?>
                    <p class="no-albums"><?= e('category.empty') ?></p>
                <?php endif; ?>
            </section>
            <?php endforeach; ?>

        <?php else: ?>
            <p class="no-albums"><?= e('category.empty') ?></p>
        <?php endif; ?>

        <footer class="site-footer">
            <p class="big"><?= e('footer.big') ?><b><?= e('brand.dot') ?></b></p>
            <p class="mo"><?= e('footer.rights', ['year' => date('Y')]) ?></p>
        </footer>
    </div>

</main>

<p id="ppToast" role="status" aria-live="polite"></p>

<script>
    var UNIVERSON_PP = {
        copied: <?= json_encode(t('notify.link_copied'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        prompt: <?= json_encode(t('notify.link_prompt'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };

    /* ── Share button ── */
    (function () {
        var btn = document.getElementById('shareProfileBtn');
        var toast = document.getElementById('ppToast');
        if (!btn || !toast) return;

        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-share-url');
            function showToast(msg) {
                toast.textContent = msg;
                setTimeout(function () { toast.textContent = ''; }, 2200);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    showToast(UNIVERSON_PP.copied);
                }).catch(function () { prompt(UNIVERSON_PP.prompt, url); });
            } else {
                prompt(UNIVERSON_PP.prompt, url);
            }
        });
    })();

    /* ── Logout (if viewer) ── */
    (function () {
        var logoutBtn = document.getElementById('logoutBtn');
        if (!logoutBtn) return;
        logoutBtn.addEventListener('click', function () {
            window.location.href = '/api/logout.php?redirect=/index.php';
        });
    })();
</script>
</body>
</html>
