<?php
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../env_data.php';
    require_once __DIR__ . '/../util/functions.php';

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
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="../img/logo.ico">
    <title><?= htmlspecialchars($site_title) ?> — Mon profil</title>
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>

    <header class="site-header">
        <a href="/" class="site-logo">Universon</a>
        <button type="button" id="logoutBtn">Déconnexion</button>
    </header>

    <main>

        <!-- Identité — JS queries: .pseudo-display -->
        <section class="profile" aria-labelledby="profile-title">
            <?php if (!empty($user['picture'])): ?>
                <img src="<?= htmlspecialchars($user['picture']) ?>" alt="Photo de profil" class="profile-avatar">
            <?php endif; ?>

            <h1 id="profile-title"><?= htmlspecialchars($user['firstName'] ?? '') ?> <?= htmlspecialchars($user['lastName'] ?? '') ?></h1>

            <?php if (!empty($user['pseudo'])): ?>
                <p><span class="pseudo-display">@<?= htmlspecialchars($user['pseudo']) ?></span></p>
            <?php else: ?>
                <p><span class="pseudo-display" hidden></span></p>
            <?php endif; ?>

            <!-- Bio — JS queries: #editBioBtn, #bioContent, #bioEditForm, #bioTextarea, #saveBioBtn, #cancelBioBtn -->
            <div class="bio">
                <h2>Bio</h2>
                <button type="button" id="editBioBtn">Modifier</button>

                <div id="bioContent">
                    <?php if (!empty($user['bio'])): ?>
                        <p><?= htmlspecialchars($user['bio']) ?></p>
                    <?php else: ?>
                        <p>Ajoutez une bio pour partager vos goûts musicaux.</p>
                    <?php endif; ?>
                </div>

                <div id="bioEditForm" style="display: none;">
                    <label for="bioTextarea">Bio</label>
                    <textarea id="bioTextarea" rows="4" placeholder="Parlez-nous de vos goûts musicaux"><?= isset($user['bio']) ? htmlspecialchars($user['bio']) : '' ?></textarea>
                    <button type="button" id="saveBioBtn">Sauvegarder</button>
                    <button type="button" id="cancelBioBtn">Annuler</button>
                </div>
            </div>
        </section>

        <!-- Visibilité — JS queries: #visibilityToggle, .switch-label, .switch-text, #shareOwnProfileBtn -->
        <section class="visibility" aria-labelledby="visibility-title">
            <h2 id="visibility-title">Visibilité du profil</h2>

            <?php if (!empty($user['pseudo'])): ?>
                <button type="button" id="shareOwnProfileBtn"
                        data-share-url="<?= htmlspecialchars($site_url) ?>/@<?= htmlspecialchars($user['pseudo']) ?>">
                    Copier le lien
                </button>
            <?php else: ?>
                <button type="button" id="shareOwnProfileBtn" title="Choisissez un pseudo pour partager">
                    Copier le lien
                </button>
            <?php endif; ?>

            <p class="switch-label">
                <span class="switch-text"><?= ($user['profile_visibility'] === 'public') ? 'Public' : 'Privé' ?></span>
            </p>
            <label for="visibilityToggle">Rendre mon profil public</label>
            <input type="checkbox" id="visibilityToggle" <?= ($user['profile_visibility'] === 'public') ? 'checked' : '' ?>>
        </section>

        <!-- Albums par catégorie — JS queries [id^="add"][id$="Btn"] -->
        <?php foreach ($categories as $category):
            $categoryAlbums = $categoriesAlbums[$category['name']] ?? [];
            $btnId = 'add' . ucfirst(str_replace('_', '', $category['name'])) . 'Btn';
        ?>
        <section class="category">
            <h2><?= htmlspecialchars($category['description']) ?></h2>
            <button type="button" id="<?= $btnId ?>">Ajouter un album</button>

            <?php if (!empty($categoryAlbums)): ?>
            <ul class="album-list">
                <?php foreach ($categoryAlbums as $album): ?>
                <li class="album-card-horizontal" data-album-id="<?= $album['id'] ?>">
                    <?php if (!empty($album['image_url_100']) || !empty($album['image_url_60'])): ?>
                        <img src="<?= htmlspecialchars($album['image_url_100'] ?: $album['image_url_60']) ?>"
                             alt="<?= htmlspecialchars($album['name']) ?>"
                             loading="lazy"
                             onerror="this.remove();">
                    <?php endif; ?>
                    <h3 class="album-title"><?= htmlspecialchars($album['name']) ?></h3>
                    <?php if (!empty($album['artist_name'])): ?>
                    <p class="album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                    <?php endif; ?>
                    <button type="button" class="remove-album-btn"
                            onclick="removeAlbumFromCategory(<?= $album['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                        Retirer
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="no-albums">Aucun album dans cette catégorie.</p>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>

    </main>

    <footer class="site-footer">
        <p>© <?= date('Y') ?> Universon</p>
    </footer>

    <!-- Choix du pseudo — JS queries: #pseudoModal, #pseudoInput, #pseudoFeedback, #savePseudoBtn, #cancelPseudoBtn -->
    <div id="pseudoModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="pseudoModalTitle">
        <h2 id="pseudoModalTitle">Choisir un pseudo</h2>
        <p>Pour rendre votre profil public, choisissez un pseudo unique.</p>
        <label for="pseudoInput">Pseudo</label>
        <input type="text" id="pseudoInput" placeholder="votre_pseudo" maxlength="45" minlength="3">
        <p id="pseudoFeedback" class="feedback" role="status" aria-live="polite"></p>
        <button type="button" id="savePseudoBtn" disabled>Enregistrer</button>
        <button type="button" id="cancelPseudoBtn">Annuler</button>
    </div>

    <script src="/js/app.js"></script>
</body>
</html>
