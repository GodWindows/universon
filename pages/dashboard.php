<?php
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../env_data.php';
    require_once __DIR__ . '/../util/functions.php';
    require_once __DIR__ . '/../util/i18n.php';

    if (!isset($_COOKIE['session_token']) || $_COOKIE['session_token'] == "") {
        header('Location: /pages/login.php');
        exit();
    }

    $user = getUserFromSessionToken($_COOKIE['session_token']);
    if ($user == null) {
        header('Location: /pages/login.php');
        exit();
    }

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
        $categoriesAlbums[$category['name']] = get_user_albums_by_category($user['id'], $category['name']);
    }

    $totalAlbums = array_sum(array_map('count', $categoriesAlbums));
    $hasPseudo   = !empty($user['pseudo']);
    $shareUrl    = $hasPseudo ? $site_url . '/@' . $user['pseudo'] : '';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="../img/logo.ico">
    <title><?= htmlspecialchars($site_title) ?> — Mon profil</title>
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
                <button type="button" id="logoutBtn" class="btn btn-line"><?= e('nav.logout') ?></button>
            </nav>
        </header>
    </div>

    <main>

        <!-- Identité — JS queries: .pseudo-display -->
        <div class="wrap">
            <section class="profile" aria-labelledby="profile-title">
                <?php if (!empty($user['picture'])): ?>
                    <img src="<?= htmlspecialchars($user['picture']) ?>" alt="<?= t('profile.avatar.alt') ?>" class="profile-avatar">
                <?php endif; ?>

                <div>
                    <h1 id="profile-title"><?= htmlspecialchars(trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''))) ?></h1>

                    <?php if ($hasPseudo): ?>
                        <p><span class="pseudo-display">@<?= htmlspecialchars($user['pseudo']) ?></span></p>
                    <?php else: ?>
                        <p><span class="pseudo-display" hidden></span></p>
                    <?php endif; ?>

                    <!-- Bio — JS queries: #editBioBtn, #bioContent, #bioEditForm, #bioTextarea, #saveBioBtn, #cancelBioBtn -->
                    <div class="bio-block">
                        <div class="bio-head">
                            <h2 id="bio-title"><?= e('profile.bio.title') ?></h2>
                            <button type="button" id="editBioBtn" class="btn btn-line btn--sm"><?= e('profile.bio.edit') ?></button>
                        </div>

                        <div id="bioContent">
                            <?php if (!empty($user['bio'])): ?>
                                <p class="bio"><?= htmlspecialchars($user['bio']) ?></p>
                            <?php else: ?>
                                <p class="bio"><?= e('profile.bio.empty') ?></p>
                            <?php endif; ?>
                        </div>

                        <div id="bioEditForm" style="display: none;">
                            <label for="bioTextarea" class="field-label"><?= e('profile.bio.title') ?></label>
                            <textarea id="bioTextarea" rows="4" placeholder="<?= t('profile.bio.placeholder') ?>"><?= isset($user['bio']) ? htmlspecialchars($user['bio']) : '' ?></textarea>
                            <div class="form-actions">
                                <button type="button" id="cancelBioBtn" class="btn btn-line"><?= e('profile.bio.cancel') ?></button>
                                <button type="button" id="saveBioBtn" class="btn"><?= e('profile.bio.save') ?></button>
                            </div>
                        </div>
                    </div>

                    <!-- Actions — JS queries: #shareOwnProfileBtn -->
                    <div class="profile-actions">
                        <?php if ($hasPseudo): ?>
                            <button type="button" id="shareOwnProfileBtn" class="btn"
                                    data-share-url="<?= htmlspecialchars($shareUrl) ?>">
                                <?= e('profile.share') ?>
                            </button>
                            <a href="/@<?= htmlspecialchars($user['pseudo']) ?>" class="btn btn-line"><?= e('profile.enlarge') ?></a>
                        <?php else: ?>
                            <button type="button" id="shareOwnProfileBtn" class="btn">
                                <?= e('profile.share') ?>
                            </button>
                        <?php endif; ?>
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
                        <?php if (!empty($user['created_at'])): ?>
                        <div>
                            <span class="mo"><?= e('profile.stat.since') ?></span>
                            <b><?= htmlspecialchars(date('Y', strtotime($user['created_at']))) ?></b>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- Visibilité — JS queries: #visibilityToggle, .switch-label, .switch-text -->
        <div class="panelbox">
            <div class="wrap">
                <section class="visibility" aria-labelledby="visibility-title">
                    <div class="row">
                        <div>
                            <h2 class="row-title" id="visibility-title"><?= e('dashboard.visibility.title') ?></h2>
                            <?php if ($hasPseudo): ?>
                                <span class="mo row-hint"><?= e('dashboard.visibility.hint', ['pseudo' => $user['pseudo']]) ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="switch">
                            <span class="switch-label">
                                <span class="switch-text mo"><?= ($user['profile_visibility'] === 'public') ? e('dashboard.visibility.public') : e('dashboard.visibility.private') ?></span>
                            </span>
                            <input type="checkbox" id="visibilityToggle" <?= ($user['profile_visibility'] === 'public') ? 'checked' : '' ?>>
                            <label for="visibilityToggle"><span class="sr-only"><?= t('dashboard.visibility.title') ?></span></label>
                        </p>
                    </div>
                </section>
            </div>
        </div>

        <div class="wrap">

            <!-- Albums par catégorie — JS queries button[data-category] -->
            <?php $catIndex = 0; foreach ($categories as $category):
                $catIndex++;
                $categoryAlbums = $categoriesAlbums[$category['name']] ?? [];
                $headId   = 'cat-' . $category['name'];
                $catLabel = t('category.' . $category['name']);
            ?>
            <section class="category" aria-labelledby="<?= htmlspecialchars($headId) ?>">
                <div class="cat-head">
                    <h2 id="<?= htmlspecialchars($headId) ?>"><?= e('category.' . $category['name']) ?></h2>
                    <div class="cat-head-aside">
                        <span class="mo cat-meta"><?= e('category.meta', [
                            'count' => count($categoryAlbums),
                            'n'     => str_pad((string) $catIndex, 2, '0', STR_PAD_LEFT),
                        ]) ?></span>
                        <button type="button" data-category="<?= htmlspecialchars($category['name']) ?>" class="btn btn--sm"><?= e('category.add') ?></button>
                    </div>
                </div>

                <?php if (!empty($categoryAlbums)): ?>
                <ul class="album-list">
                    <?php $i = 0; foreach ($categoryAlbums as $album): $i++; ?>
                    <li class="album-card-horizontal" data-album-id="<?= $album['id'] ?>">
                        <?php if (!empty($album['image_url_100']) || !empty($album['image_url_60'])): ?>
                            <img src="<?= htmlspecialchars($album['image_url_100'] ?: $album['image_url_60']) ?>"
                                 alt="<?= htmlspecialchars($album['name']) ?>"
                                 loading="lazy"
                                 onerror="this.remove();">
                        <?php endif; ?>
                        <span class="album-idx"><?= str_pad((string) $i, 2, '0', STR_PAD_LEFT) ?></span>
                        <div class="album-meta">
                            <h3 class="album-title"><?= htmlspecialchars($album['name']) ?></h3>
                            <?php if (!empty($album['artist_name'])): ?>
                            <p class="album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="remove-album-btn"
                                onclick="removeAlbumFromCategory(<?= $album['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                            <?= e('dashboard.remove') ?>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="no-albums"><?= e('category.empty') ?></p>
                <?php endif; ?>
            </section>
            <?php endforeach; ?>

            <footer class="site-footer">
                <p class="big"><?= e('footer.big') ?><b><?= e('brand.dot') ?></b></p>
                <p class="mo"><?= e('footer.rights', ['year' => date('Y')]) ?></p>
            </footer>

        </div>

    </main>

    <!-- Choix du pseudo — JS queries: #pseudoModal, #pseudoInput, #pseudoFeedback, #savePseudoBtn, #cancelPseudoBtn -->
    <div id="pseudoModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="pseudoModalTitle">
        <div class="modal-head">
            <h2 id="pseudoModalTitle"><?= e('pseudo.title') ?></h2>
        </div>
        <div class="modal-body">
            <p class="modal-lede"><?= e('pseudo.body') ?></p>
            <label for="pseudoInput" class="field-label"><?= e('pseudo.label') ?></label>
            <input type="text" id="pseudoInput" placeholder="<?= t('pseudo.placeholder') ?>" maxlength="45" minlength="3">
            <p id="pseudoFeedback" class="feedback" role="status" aria-live="polite"></p>
        </div>
        <div class="modal-foot">
            <button type="button" id="cancelPseudoBtn" class="btn btn-line"><?= e('pseudo.cancel') ?></button>
            <button type="button" id="savePseudoBtn" class="btn" disabled><?= e('pseudo.save') ?></button>
        </div>
    </div>

    <script>
        window.UNIVERSON_I18N = <?= json_encode(array_merge([
            /* Libellés de rayon : le fichier de langue fait foi à l'affichage (§ 9.3.4). */
        ], array_combine(
            array_map(function ($c) { return 'category.' . $c['name']; }, $categories),
            array_map(function ($c) { return t('category.' . $c['name']); }, $categories)
        ) ?: [], [
            'dashboard.add.title'       => t('dashboard.add.title'),
            'dashboard.add.context'     => t('dashboard.add.context'),
            'dashboard.add.label'       => t('dashboard.add.label'),
            'dashboard.add.placeholder' => t('dashboard.add.placeholder'),
            'dashboard.add.cancel'      => t('dashboard.add.cancel'),
            'dashboard.add.confirm'     => t('dashboard.add.confirm'),
            'dashboard.add.saving'      => t('dashboard.add.saving'),
            'dashboard.add.select'      => t('dashboard.add.select'),
            'dashboard.add.empty'       => t('dashboard.add.empty'),
            'dashboard.add.too_long'    => t('dashboard.add.too_long'),
            'dashboard.remove.confirm'  => t('dashboard.remove.confirm'),
            'dashboard.visibility.public'  => t('dashboard.visibility.public'),
            'dashboard.visibility.private' => t('dashboard.visibility.private'),
            'nav.logging_out'           => t('nav.logging_out'),
            'notify.added'              => t('notify.added'),
            'notify.removed'            => t('notify.removed'),
            'notify.bio_saved'          => t('notify.bio_saved'),
            'notify.link_copied'        => t('notify.link_copied'),
            'notify.link_prompt'        => t('notify.link_prompt'),
            'notify.visibility'         => t('notify.visibility'),
            'notify.offline'            => t('notify.offline'),
            'notify.error'              => t('notify.error'),
            'notify.close'              => t('notify.close'),
            'profile.bio.save'          => t('profile.bio.save'),
            'profile.bio.saving'        => t('profile.bio.saving'),
            'pseudo.save'               => t('pseudo.save'),
            'pseudo.saving'             => t('pseudo.saving'),
            'pseudo.saved'              => t('pseudo.saved'),
            'pseudo.checking'           => t('pseudo.checking'),
            'pseudo.available'          => t('pseudo.available'),
            'pseudo.taken'              => t('pseudo.taken'),
            'pseudo.too_short'          => t('pseudo.too_short'),
            'pseudo.too_long'           => t('pseudo.too_long'),
            'pseudo.check_error'        => t('pseudo.check_error'),
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="/js/app.js"></script>
</body>
</html>
