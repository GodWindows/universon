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

    /* ─── shared styles ─────────────────────────────────────────────── */
    ob_start();
?>
<style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
        --ink:    #09090A;
        --ink-2:  #111113;
        --ivory:  #F0EBE3;
        --copper: #C87941;
        --line:   rgba(240, 235, 227, 0.1);
        --muted:  rgba(240, 235, 227, 0.45);
    }

    html { scroll-behavior: smooth; }

    body {
        font-family: 'Space Grotesk', sans-serif;
        background: var(--ink);
        color: var(--ivory);
        overflow-x: hidden;
        cursor: none;
    }

    body::after {
        content: '';
        position: fixed; inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
        opacity: 0.035;
        pointer-events: none;
        z-index: 9990;
    }

    /* ─── CURSOR ── */
    .cursor {
        position: fixed; width: 7px; height: 7px;
        background: var(--copper);
        border-radius: 50%;
        pointer-events: none; z-index: 9999;
        transform: translate(-50%, -50%);
    }
    .cursor-ring {
        position: fixed; width: 38px; height: 38px;
        border: 1px solid rgba(200,121,65,0.5);
        border-radius: 50%;
        pointer-events: none; z-index: 9998;
        transform: translate(-50%, -50%);
        transition: width 0.3s, height 0.3s, border-color 0.3s;
    }
    .cursor--hover { background: var(--ivory); width: 12px; height: 12px; }
    .cursor-ring--hover { width: 56px; height: 56px; border-color: rgba(240,235,227,0.25); }

    /* ─── HEADER ── */
    .pp-header {
        position: fixed; top: 0; left: 0; right: 0;
        z-index: 200;
        display: flex; justify-content: space-between; align-items: center;
        padding: 1.75rem 2.5rem;
        transition: padding 0.4s, background 0.4s, border-color 0.4s;
    }
    .pp-header.scrolled {
        padding: 1rem 2.5rem;
        background: rgba(9,9,10,0.9);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid var(--line);
    }
    .pp-logo {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 1.3rem; letter-spacing: 0.2em;
        color: var(--ivory); text-decoration: none;
        transition: opacity 0.2s;
    }
    .pp-logo:hover { opacity: 0.5; }

    .pp-logout {
        display: inline-flex; align-items: center; gap: 0.4rem;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.8rem; font-weight: 600; letter-spacing: 0.06em;
        color: rgba(240,235,227,0.5);
        padding: 0.55rem 1.1rem;
        border: 1px solid rgba(240,235,227,0.15);
        border-radius: 999px;
        background: none;
        transition: all 0.25s; cursor: none;
    }
    .pp-logout:hover { color: var(--ivory); border-color: rgba(240,235,227,0.4); background: rgba(240,235,227,0.06); }
    .pp-logout svg { width: 13px; height: 13px; }

    /* ─── FULLSCREEN ERROR ── */
    .pp-error {
        min-height: 100vh;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center;
        text-align: center; padding: 2rem;
        position: relative;
    }
    .pp-error::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(ellipse 50% 50% at 50% 55%, rgba(200,121,65,0.06) 0%, transparent 70%);
        pointer-events: none;
    }
    .pp-error-label {
        font-size: 0.68rem; letter-spacing: 0.28em;
        text-transform: uppercase; color: var(--copper);
        margin-bottom: 2rem;
    }
    .pp-error-title {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(5rem, 18vw, 18rem);
        line-height: 0.82; color: var(--ivory);
        margin-bottom: 2.5rem;
    }
    .pp-error-desc {
        font-size: 1rem; color: var(--muted);
        max-width: 380px; line-height: 1.7;
        margin-bottom: 2.5rem;
    }
    .pp-error-desc em { font-style: normal; color: var(--copper); }
    .pp-back-btn {
        display: inline-flex; align-items: center; gap: 0.6rem;
        background: var(--ivory); color: var(--ink);
        padding: 0.85rem 2rem; border-radius: 999px;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.88rem; font-weight: 600;
        text-decoration: none; transition: all 0.25s;
    }
    .pp-back-btn:hover { background: var(--copper); color: var(--ivory); transform: scale(1.04); }

    /* ─── PROFILE HERO ── */
    .pp-hero {
        padding: 10rem 2.5rem 4rem;
        max-width: 1400px; margin: 0 auto;
        display: grid; grid-template-columns: 140px 1fr;
        gap: 3.5rem; align-items: start;
        border-bottom: 1px solid var(--line);
    }
    .pp-avatar {
        width: 120px; height: 120px;
        border-radius: 50%; object-fit: cover;
        border: 2px solid rgba(200,121,65,0.3);
        display: block; padding-top: 0.5rem;
        transition: border-color 0.3s;
    }
    .pp-avatar:hover { border-color: var(--copper); }

    .pp-avatar-placeholder {
        width: 120px; height: 120px;
        border-radius: 50%;
        background: rgba(200,121,65,0.08);
        border: 2px solid rgba(200,121,65,0.2);
        display: flex; align-items: center; justify-content: center;
        margin-top: 0.5rem;
    }
    .pp-avatar-placeholder svg { width: 40px; height: 40px; color: rgba(200,121,65,0.3); }

    .pp-hero-right { display: flex; flex-direction: column; gap: 1.5rem; }

    .pp-name {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(3rem, 7vw, 6.5rem);
        line-height: 0.85; color: var(--ivory);
        letter-spacing: -0.01em;
    }

    .pp-meta { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: 0.25rem; }

    .pp-pseudo {
        font-size: 0.8rem; font-weight: 600; letter-spacing: 0.1em;
        color: var(--copper);
        background: rgba(200,121,65,0.1);
        border: 1px solid rgba(200,121,65,0.3);
        padding: 0.3rem 0.75rem; border-radius: 999px;
    }

    .pp-share-btn {
        display: inline-flex; align-items: center; gap: 0.4rem;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.78rem; font-weight: 600; letter-spacing: 0.05em;
        color: var(--muted);
        padding: 0.3rem 0.85rem;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: none; transition: all 0.25s; cursor: none;
    }
    .pp-share-btn:hover { color: var(--ivory); border-color: rgba(240,235,227,0.3); }
    .pp-share-btn svg { width: 12px; height: 12px; }

    .pp-bio {
        max-width: 560px;
    }
    .pp-bio-label {
        font-size: 0.65rem; letter-spacing: 0.25em;
        text-transform: uppercase; color: rgba(240,235,227,0.28);
        margin-bottom: 0.5rem; display: block;
    }
    .pp-bio p {
        font-size: 1.05rem; line-height: 1.75;
        color: rgba(240,235,227,0.65);
    }
    .pp-bio-empty { font-style: italic; opacity: 0.35; }

    /* ─── TICKER ── */
    .pp-ticker {
        border-top: 1px solid var(--line);
        border-bottom: 1px solid var(--line);
        background: var(--ink-2);
        padding: 0.9rem 0; overflow: hidden;
    }
    .pp-ticker-track {
        display: flex; gap: 0;
        animation: marquee 30s linear infinite;
        white-space: nowrap; width: max-content;
    }
    .pp-ticker-item {
        display: inline-flex; align-items: center; gap: 2rem;
        font-family: 'Bebas Neue', sans-serif;
        font-size: 0.95rem; letter-spacing: 0.18em;
        color: rgba(240,235,227,0.25); padding: 0 2rem;
    }
    .pp-ticker-sep { color: var(--copper); }

    @keyframes marquee {
        from { transform: translateX(0); }
        to   { transform: translateX(-50%); }
    }

    /* ─── COLLECTION ── */
    .pp-collection {
        max-width: 1400px; margin: 0 auto;
        padding: 0 2.5rem 6rem;
    }

    .pp-category {
        padding: 3.5rem 0;
        border-bottom: 1px solid var(--line);
    }
    .pp-category:last-child { border-bottom: none; }

    .pp-category-header { margin-bottom: 2rem; }

    .pp-category-title {
        font-family: 'Bebas Neue', sans-serif;
        font-size: clamp(2.5rem, 5vw, 4.5rem);
        line-height: 0.85; color: var(--ivory);
        letter-spacing: -0.01em;
    }

    /* ─── ALBUM SCROLL ── */
    .pp-scroll {
        display: flex; gap: 1rem;
        overflow-x: auto; overflow-y: hidden;
        padding-bottom: 0.75rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(200,121,65,0.25) transparent;
    }
    .pp-scroll::-webkit-scrollbar { height: 3px; }
    .pp-scroll::-webkit-scrollbar-thumb { background: rgba(200,121,65,0.3); border-radius: 99px; }

    /* ─── ALBUM CARD ── */
    .pp-album {
        flex: 0 0 200px;
        display: flex; flex-direction: column; gap: 0.65rem;
    }

    .pp-cover {
        position: relative;
        width: 200px; height: 200px;
        border-radius: 4px; overflow: hidden;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.06);
    }
    .pp-cover img {
        width: 100%; height: 100%; object-fit: cover; display: block;
        transition: transform 0.4s cubic-bezier(0.4,0,0.2,1);
    }
    .pp-album:hover .pp-cover img { transform: scale(1.04); }

    .pp-cover-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(to top, rgba(9,9,10,0.9) 0%, rgba(9,9,10,0.35) 50%, transparent 100%);
        display: flex; flex-direction: column;
        justify-content: flex-end; padding: 0.85rem;
        opacity: 0; transition: opacity 0.3s;
    }
    .pp-album:hover .pp-cover-overlay { opacity: 1; }

    .pp-cover-overlay .pp-album-title {
        font-size: 0.85rem; font-weight: 700;
        color: var(--ivory); margin: 0 0 0.2rem;
        line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2;
        -webkit-box-orient: vertical; overflow: hidden;
    }
    .pp-cover-overlay .pp-album-artist {
        font-size: 0.75rem; color: rgba(240,235,227,0.6);
        margin: 0; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
    }

    .pp-album-info { display: none; } /* shown on mobile via responsive */
    .pp-album-info .pp-album-title {
        font-size: 0.82rem; font-weight: 600;
        color: var(--ivory); line-height: 1.3;
    }
    .pp-album-info .pp-album-artist {
        font-size: 0.72rem; color: var(--muted);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    .pp-cover-placeholder {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
    }
    .pp-cover-placeholder svg { width: 2.5rem; height: 2.5rem; color: rgba(200,121,65,0.2); }

    /* ─── NO ALBUMS ── */
    .pp-no-albums {
        padding: 3rem 0;
        display: flex; flex-direction: column;
        align-items: center; gap: 0.75rem;
        color: rgba(240,235,227,0.2);
    }
    .pp-no-albums svg { width: 2rem; height: 2rem; color: rgba(200,121,65,0.2); }
    .pp-no-albums p { font-size: 0.82rem; letter-spacing: 0.1em; text-transform: uppercase; }

    /* ─── EMPTY COLLECTION ── */
    .pp-empty {
        padding: 6rem 2.5rem;
        text-align: center;
        max-width: 1400px; margin: 0 auto;
    }
    .pp-empty svg { width: 2.5rem; height: 2.5rem; color: rgba(200,121,65,0.2); margin-bottom: 1rem; }
    .pp-empty p { font-size: 0.85rem; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(240,235,227,0.2); }

    /* ─── FOOTER ── */
    .pp-footer {
        border-top: 1px solid var(--line);
        padding: 1.75rem 2.5rem;
        display: flex; justify-content: space-between;
        font-size: 0.72rem; letter-spacing: 0.06em;
        color: rgba(240,235,227,0.2);
        max-width: 1400px; margin: 0 auto;
    }

    /* ─── TOAST ── */
    .pp-toast {
        position: fixed; bottom: 2rem; left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: #111113;
        border: 1px solid rgba(200,121,65,0.3);
        border-radius: 999px;
        padding: 0.65rem 1.5rem;
        font-size: 0.82rem; font-weight: 600; letter-spacing: 0.04em;
        color: var(--ivory); z-index: 5000;
        opacity: 0; transition: all 0.35s cubic-bezier(0.16,1,0.3,1);
        pointer-events: none;
    }
    .pp-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

    /* ─── SCROLL REVEAL ── */
    .js-reveal {
        opacity: 0; transform: translateY(24px);
        transition: opacity 0.85s cubic-bezier(0.16,1,0.3,1),
                    transform 0.85s cubic-bezier(0.16,1,0.3,1);
    }
    .js-reveal.is-revealed { opacity: 1; transform: translateY(0); }
    .js-reveal-delay-1 { transition-delay: 0.1s; }
    .js-reveal-delay-2 { transition-delay: 0.2s; }

    /* ─── RESPONSIVE ── */
    @media (max-width: 768px) {
        body { cursor: auto; }
        .cursor, .cursor-ring { display: none; }

        .pp-header { padding: 1.25rem; }
        .pp-header.scrolled { padding: 0.9rem 1.25rem; }

        .pp-hero {
            grid-template-columns: 1fr;
            padding: 8rem 1.25rem 3rem;
            gap: 1.5rem;
        }
        .pp-avatar, .pp-avatar-placeholder { width: 80px; height: 80px; }

        .pp-collection, .pp-empty, .pp-footer { padding-left: 1.25rem; padding-right: 1.25rem; }

        .pp-category { padding: 2.5rem 0; }
        .pp-category-title { font-size: clamp(2rem, 8vw, 3rem); }

        .pp-album { flex: 0 0 150px; }
        .pp-cover { width: 150px; height: 150px; }
        .pp-cover-overlay { opacity: 1; }
        .pp-album-info { display: flex; flex-direction: column; gap: 0.15rem; }

        .pp-footer { flex-direction: column; gap: 0.4rem; text-align: center; }
    }

    @media (max-width: 480px) {
        .pp-name { font-size: 2.75rem; }
        .pp-album { flex: 0 0 130px; }
        .pp-cover { width: 130px; height: 130px; }
    }
</style>
<?php
    $commonStyles = ob_get_clean();

    /* ─── common head helper ─────────────────────────────────────────── */
    function pp_head($title, $description = '', $styles = '') {
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
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    ' . $styles . '
</head>
<body>';
    }

    /* ─── common cursor + header ─────────────────────────────────────── */
    function pp_cursor_and_header($site_title, $logoutBtn = false) {
        echo '<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<header class="pp-header" id="ppHeader">
    <a href="/" class="pp-logo">UNIVERSON</a>';
        if ($logoutBtn) {
            echo '<button id="logoutBtn" class="pp-logout">
        <i data-lucide="log-out"></i>
        Déconnexion
    </button>';
        }
        echo '</header>';
    }

    /* ─── common cursor JS ───────────────────────────────────────────── */
    function pp_cursor_js() {
        echo '<script>
    var cur = document.getElementById("cursor");
    var ring = document.getElementById("cursorRing");
    if (cur && ring) {
        var mx=0,my=0,rx=0,ry=0;
        document.addEventListener("mousemove",function(e){mx=e.clientX;my=e.clientY;cur.style.left=mx+"px";cur.style.top=my+"px";});
        (function raf(){rx+=(mx-rx)*0.1;ry+=(my-ry)*0.1;ring.style.left=rx+"px";ring.style.top=ry+"px";requestAnimationFrame(raf);})();
        document.querySelectorAll("a,button").forEach(function(el){
            el.addEventListener("mouseenter",function(){cur.classList.add("cursor--hover");ring.classList.add("cursor-ring--hover");});
            el.addEventListener("mouseleave",function(){cur.classList.remove("cursor--hover");ring.classList.remove("cursor-ring--hover");});
        });
    }
    var ppHeader=document.getElementById("ppHeader");
    if(ppHeader){window.addEventListener("scroll",function(){ppHeader.classList.toggle("scrolled",window.scrollY>60);},{passive:true});}
    if(typeof lucide!=="undefined"){lucide.createIcons();}
</script>';
    }

    /* ─── 404 ─────────────────────────────────────────────────────────── */
    if ($publicUser === null) {
        http_response_code(404);
        pp_head(
            $site_title . ' — Profil introuvable',
            'Ce profil n\'existe pas ou n\'est plus disponible.',
            $commonStyles
        );
        pp_cursor_and_header($site_title);
?>
<main class="pp-error">
    <span class="pp-error-label">— Erreur 404</span>
    <h1 class="pp-error-title">PROFIL<br>INTROUVABLE</h1>
    <p class="pp-error-desc">
        Le profil <em>@<?= htmlspecialchars($username) ?></em> n'existe pas ou n'est plus disponible sur Universon.
    </p>
    <a href="/" class="pp-back-btn">← Retour à l'accueil</a>
</main>
<?php pp_cursor_js(); ?>
</body>
</html>
<?php
        exit();
    }

    /* ─── PRIVATE ─────────────────────────────────────────────────────── */
    if ($publicUser['profile_visibility'] !== 'public') {
        pp_head(
            $site_title . ' — Profil privé',
            'Ce profil est privé et n\'est pas accessible au public.',
            $commonStyles
        );
        pp_cursor_and_header($site_title);
?>
<main class="pp-error">
    <span class="pp-error-label">— Accès restreint</span>
    <h1 class="pp-error-title">PROFIL<br>PRIVÉ</h1>
    <p class="pp-error-desc">
        Ce profil est privé et n'est pas accessible au public.
    </p>
    <a href="/" class="pp-back-btn">← Retour à l'accueil</a>
</main>
<?php pp_cursor_js(); ?>
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
        'Découvrez la collection musicale de @' . htmlspecialchars($publicUser['pseudo']) . ' sur Universon. ' . $bioMeta,
        $commonStyles
    );

    // Extra meta for public profile
    echo '<meta property="og:type" content="profile">
<meta property="og:url" content="' . $shareUrl . '">
<meta property="og:title" content="' . $profileName . ' — Universon">
<meta property="og:description" content="' . $bioMeta . '">
<meta name="twitter:card" content="summary">
<link rel="canonical" href="' . $shareUrl . '">';

    pp_cursor_and_header($site_title, $hasLogout);
?>

<!-- Profile hero -->
<section class="pp-hero">
    <div>
        <?php if (!empty($publicUser['picture'])): ?>
            <img src="<?= htmlspecialchars($publicUser['picture']) ?>" alt="<?= $profileName ?>" class="pp-avatar">
        <?php else: ?>
            <div class="pp-avatar-placeholder">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        <?php endif; ?>
    </div>
    <div class="pp-hero-right">
        <div>
            <h1 class="pp-name"><?= $profileName ?></h1>
            <div class="pp-meta">
                <span class="pp-pseudo">@<?= htmlspecialchars($publicUser['pseudo']) ?></span>
                <button class="pp-share-btn" id="shareProfileBtn"
                        data-share-url="<?= $shareUrl ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/>
                    </svg>
                    <?= $isOwnProfile ? 'Partager mon profil' : 'Partager ce profil' ?>
                </button>
            </div>
        </div>

        <?php if (!empty($publicUser['bio'])): ?>
        <div class="pp-bio">
            <span class="pp-bio-label">Bio</span>
            <p><?= htmlspecialchars($publicUser['bio']) ?></p>
        </div>
        <?php else: ?>
        <div class="pp-bio">
            <p class="pp-bio-empty">Aucune bio renseignée.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Ticker -->
<div class="pp-ticker" aria-hidden="true">
    <div class="pp-ticker-track">
        <span class="pp-ticker-item">COLLECTION DE @<?= htmlspecialchars(strtoupper($publicUser['pseudo'])) ?> <span class="pp-ticker-sep">×</span></span>
        <span class="pp-ticker-item">UNIVERSON <span class="pp-ticker-sep">×</span></span>
        <span class="pp-ticker-item">MUSÉE MUSICAL <span class="pp-ticker-sep">×</span></span>
        <span class="pp-ticker-item">EXPOSITION PERSONNELLE <span class="pp-ticker-sep">×</span></span>
        <span class="pp-ticker-item">COLLECTION DE @<?= htmlspecialchars(strtoupper($publicUser['pseudo'])) ?> <span class="pp-ticker-sep">×</span></span>
        <span class="pp-ticker-item">UNIVERSON <span class="pp-ticker-sep">×</span></span>
        <span class="pp-ticker-item">MUSÉE MUSICAL <span class="pp-ticker-sep">×</span></span>
        <span class="pp-ticker-item">EXPOSITION PERSONNELLE <span class="pp-ticker-sep">×</span></span>
    </div>
</div>

<!-- Album collection -->
<div class="pp-collection">

<?php if (!empty($categories)): ?>
    <?php foreach ($categories as $category):
        $albums = $categoriesAlbums[$category['name']] ?? [];
    ?>
    <section class="pp-category js-reveal">
        <div class="pp-category-header">
            <h2 class="pp-category-title"><?= htmlspecialchars(strtoupper($category['description'])) ?></h2>
        </div>

        <?php if (!empty($albums)): ?>
        <div class="pp-scroll">
            <?php foreach ($albums as $album):
                $imgSrc = !empty($album['image_url_100']) ? $album['image_url_100'] : ($album['image_url_60'] ?? '');
            ?>
            <article class="pp-album">
                <div class="pp-cover">
                    <?php if ($imgSrc): ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>"
                             alt="<?= htmlspecialchars($album['name']) ?>"
                             loading="lazy"
                             onerror="this.parentNode.classList.add('no-img'); this.remove();">
                        <div class="pp-cover-overlay">
                            <h3 class="pp-album-title"><?= htmlspecialchars($album['name']) ?></h3>
                            <?php if (!empty($album['artist_name'])): ?>
                            <p class="pp-album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="pp-cover-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="pp-album-info">
                    <h3 class="pp-album-title"><?= htmlspecialchars($album['name']) ?></h3>
                    <?php if (!empty($album['artist_name'])): ?>
                    <p class="pp-album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="pp-no-albums">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <p>Aucun album dans cette catégorie</p>
        </div>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>

<?php elseif (!empty($publicUserAlbums)): ?>
    <!-- Legacy fallback: uncategorised albums -->
    <section class="pp-category js-reveal">
        <div class="pp-category-header">
            <h2 class="pp-category-title">ALBUMS PUBLICS</h2>
        </div>
        <div class="pp-scroll">
            <?php foreach ($publicUserAlbums as $album):
                $imgSrc = !empty($album['image_url_100']) ? $album['image_url_100'] : ($album['image_url_60'] ?? '');
            ?>
            <article class="pp-album">
                <div class="pp-cover">
                    <?php if ($imgSrc): ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($album['name']) ?>" loading="lazy" onerror="this.parentNode.classList.add('no-img'); this.remove();">
                        <div class="pp-cover-overlay">
                            <h3 class="pp-album-title"><?= htmlspecialchars($album['name']) ?></h3>
                            <?php if (!empty($album['artist_name'])): ?>
                            <p class="pp-album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="pp-cover-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="pp-album-info">
                    <h3 class="pp-album-title"><?= htmlspecialchars($album['name']) ?></h3>
                    <?php if (!empty($album['artist_name'])): ?>
                    <p class="pp-album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

<?php else: ?>
    <div class="pp-empty js-reveal">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/>
        </svg>
        <p>Aucune collection publique à afficher pour le moment</p>
    </div>
<?php endif; ?>

</div><!-- .pp-collection -->

<!-- Toast -->
<div class="pp-toast" id="ppToast"></div>

<footer class="pp-footer">
    <span>© <?= date('Y') ?> Universon</span>
    <span>Votre musée musical personnel</span>
</footer>

<?php pp_cursor_js(); ?>
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
                toast.classList.add('show');
                setTimeout(function () { toast.classList.remove('show'); }, 2200);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    showToast('✓ Lien copié dans le presse-papier');
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

    /* ── Scroll reveal ── */
    var reveals = document.querySelectorAll('.js-reveal');
    if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('is-revealed'); obs.unobserve(e.target); }
            });
        }, { threshold: 0.1 });
        reveals.forEach(function (el) { obs.observe(el); });
    } else {
        reveals.forEach(function (el) { el.classList.add('is-revealed'); });
    }
</script>
</body>
</html>
