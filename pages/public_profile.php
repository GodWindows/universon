<?php
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../env_data.php';
    require_once __DIR__ . '/../util/functions.php';

    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $username = null;
    if (preg_match('#/@([A-Za-z0-9_.-]+)$#', $requestPath, $matches)) {
        $username = $matches[1];
    } elseif (isset($_GET['u']) && $_GET['u'] !== '') {
        $username = trim($_GET['u']);
    }

    if (!$username) {
        http_response_code(400);
        echo 'Requête invalide.';
        exit();
    }

    $publicUser = get_user_public_min_by_pseudo($username);

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
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>';
    }

    /* ─── common header ──────────────────────────────────────────────── */
    function pp_header($logoutBtn = false) {
        echo '<header class="site-header">
    <a href="/" class="site-logo">Universon</a>';
        if ($logoutBtn) {
            echo '
    <button type="button" id="logoutBtn">Déconnexion</button>';
        }
        echo '
</header>';
    }

    /* ─── 404 ─────────────────────────────────────────────────────────── */
    if ($publicUser === null) {
        http_response_code(404);
        pp_head(
            $site_title . ' — Profil introuvable',
            'Ce profil n\'existe pas ou n\'est plus disponible.'
        );
        pp_header();
?>
<main class="error">
    <h1>Profil introuvable</h1>
    <p>Le profil @<?= htmlspecialchars($username) ?> n'existe pas ou n'est plus disponible sur Universon.</p>
    <a href="/">Retour à l'accueil</a>
</main>
</body>
</html>
<?php
        exit();
    }

    /* ─── PRIVATE ─────────────────────────────────────────────────────── */
    if ($publicUser['profile_visibility'] !== 'public') {
        pp_head(
            $site_title . ' — Profil privé',
            'Ce profil est privé et n\'est pas accessible au public.'
        );
        pp_header();
?>
<main class="error">
    <h1>Profil privé</h1>
    <p>Ce profil est privé et n'est pas accessible au public.</p>
    <a href="/">Retour à l'accueil</a>
</main>
</body>
</html>
<?php
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
    $hasAnyAlbum = false;
    foreach ($categories as $category) {
        $albums = get_user_albums_by_category($publicUser['id'], $category['name']);
        $categoriesAlbums[$category['name']] = $albums;
        if (!empty($albums)) $hasAnyAlbum = true;
    }

    $publicUserAlbums = get_user_albums($publicUser['id']);

    $isOwnProfile = $viewer && isset($viewer['pseudo']) && $viewer['pseudo'] === $publicUser['pseudo'];
    $hasLogout = (bool) $viewer;

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
?>

<main>

    <section class="profile" aria-labelledby="profile-title">
        <?php if (!empty($publicUser['picture'])): ?>
            <img src="<?= htmlspecialchars($publicUser['picture']) ?>" alt="<?= $profileName ?>" class="profile-avatar">
        <?php endif; ?>

        <h1 id="profile-title"><?= $profileName ?></h1>
        <p><span class="pseudo-display">@<?= htmlspecialchars($publicUser['pseudo']) ?></span></p>

        <button type="button" id="shareProfileBtn" data-share-url="<?= $shareUrl ?>">
            <?= $isOwnProfile ? 'Partager mon profil' : 'Partager ce profil' ?>
        </button>

        <div class="bio">
            <h2>Bio</h2>
            <?php if (!empty($publicUser['bio'])): ?>
                <p><?= htmlspecialchars($publicUser['bio']) ?></p>
            <?php else: ?>
                <p>Aucune bio renseignée.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $category):
            $albums = $categoriesAlbums[$category['name']] ?? [];
        ?>
        <section class="category">
            <h2><?= htmlspecialchars($category['description']) ?></h2>

            <?php if (!empty($albums)): ?>
            <ul class="album-list">
                <?php foreach ($albums as $album):
                    $imgSrc = !empty($album['image_url_100']) ? $album['image_url_100'] : ($album['image_url_60'] ?? '');
                ?>
                <li class="album">
                    <?php if ($imgSrc): ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>"
                             alt="<?= htmlspecialchars($album['name']) ?>"
                             loading="lazy"
                             onerror="this.remove();">
                    <?php endif; ?>
                    <h3 class="album-title"><?= htmlspecialchars($album['name']) ?></h3>
                    <?php if (!empty($album['artist_name'])): ?>
                    <p class="album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="no-albums">Aucun album dans cette catégorie.</p>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>

    <?php elseif (!empty($publicUserAlbums)): ?>
        <!-- Legacy fallback: uncategorised albums -->
        <section class="category">
            <h2>Albums publics</h2>
            <ul class="album-list">
                <?php foreach ($publicUserAlbums as $album):
                    $imgSrc = !empty($album['image_url_100']) ? $album['image_url_100'] : ($album['image_url_60'] ?? '');
                ?>
                <li class="album">
                    <?php if ($imgSrc): ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>"
                             alt="<?= htmlspecialchars($album['name']) ?>"
                             loading="lazy"
                             onerror="this.remove();">
                    <?php endif; ?>
                    <h3 class="album-title"><?= htmlspecialchars($album['name']) ?></h3>
                    <?php if (!empty($album['artist_name'])): ?>
                    <p class="album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>

    <?php else: ?>
        <p class="no-albums">Aucune collection publique à afficher pour le moment.</p>
    <?php endif; ?>

</main>

<p id="ppToast" role="status" aria-live="polite"></p>

<footer class="site-footer">
    <p>© <?= date('Y') ?> Universon</p>
    <p>Votre univers musical à partager</p>
</footer>

<script>
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
                    showToast('Lien copié dans le presse-papier');
                }).catch(function () { prompt('Copiez le lien', url); });
            } else {
                prompt('Copiez le lien', url);
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
