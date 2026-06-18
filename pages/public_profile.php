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
        --craie:#F1E9DA; --velin:#FBF6EC; --stuc:#E5DAC4; --marbre:#D8D2C4;
        --sepia:#2B2118; --sepia-doux:#5A4B38; --muted:rgba(43,33,24,.45);
        --or:#B5912F; --or-clair:#E6C86E; --or-ombre:#856321;
        --grenat:#7A2233; --grenat-fonce:#511522;
        --bleu-nuit:#213A4C; --patine:#6E8275;
        --filet:rgba(43,33,24,.12); --filet-or:rgba(181,145,47,.35);
        --ombre-cadre:rgba(43,33,24,.18); --halo:rgba(230,200,110,.18);
        --r-cartel:2px; --r-sm:6px; --r-cartouche:14px; --r-panneau:26px; --r-medaillon:999px;
        --dorure:linear-gradient(135deg,#856321,#B5912F 38%,#E6C86E 55%,#B5912F 72%,#856321);
        --velours:linear-gradient(180deg,#7A2233,#5E1A28);
        --verriere:radial-gradient(125% 90% at 50% -8%,#FBF6EC 0%,#F1E9DA 45%,#E5DAC4 100%);
        --spot:radial-gradient(ellipse 55% 50% at 50% 42%,var(--halo) 0%,transparent 70%);
        --font-display:'Fraunces',serif;
        --font-body:'EB Garamond',serif;
    }

    html { scroll-behavior: smooth; }

    body {
        font-family: var(--font-body);
        background: var(--craie);
        background-image: var(--verriere);
        background-attachment: fixed;
        color: var(--sepia);
        overflow-x: hidden;
        cursor: none;
    }

    body::after {
        content: '';
        position: fixed; inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.7' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
        opacity: 0.045;
        mix-blend-mode: multiply;
        pointer-events: none;
        z-index: 9990;
    }

    /* ─── CURSEUR : FEUILLE D'OR + LAURIER ── */
    .cursor {
        position: fixed; width: 9px; height: 9px;
        background: var(--or); border-radius: 1px;
        pointer-events: none; z-index: 9999;
        transform: translate(-50%, -50%) rotate(45deg);
    }
    .cursor-ring {
        position: fixed; width: 36px; height: 36px;
        border: 1px solid var(--filet-or); border-radius: 50%;
        pointer-events: none; z-index: 9998;
        transform: translate(-50%, -50%);
        transition: width .3s, height .3s, border-color .3s;
    }
    .cursor--hover { background: var(--grenat); width: 13px; height: 13px; }
    .cursor-ring--hover { width: 54px; height: 54px; border-color: rgba(181,145,47,.6); }

    /* ─── HEADER / VITRINE ── */
    .pp-header {
        position: fixed; top: 0; left: 0; right: 0; z-index: 200;
        display: flex; justify-content: space-between; align-items: center;
        padding: 1.6rem 2.5rem;
        transition: padding .4s, background .4s, border-color .4s;
    }
    .pp-header.scrolled {
        padding: .9rem 2.5rem;
        background: rgba(251,246,236,.84);
        backdrop-filter: blur(14px);
        border-bottom: 1px solid var(--filet-or);
    }
    .pp-logo {
        display: inline-flex; align-items: center; gap: .55rem;
        font-family: var(--font-display); font-weight: 600;
        font-size: 1.35rem; letter-spacing: 0.16em;
        color: var(--sepia); text-decoration: none; transition: opacity .2s;
    }
    .pp-logo:hover { opacity: .6; }
    .pp-logo .lys { width: 16px; height: 20px; color: var(--or); }

    .pp-logout {
        display: inline-flex; align-items: center; gap: .4rem;
        font-family: var(--font-body); font-size: .9rem; font-weight: 600; letter-spacing: 0.04em;
        color: var(--sepia-doux); padding: .55rem 1.1rem;
        border: 1px solid var(--filet); border-radius: var(--r-medaillon);
        background: none; transition: all .25s; cursor: none;
    }
    .pp-logout:hover { color: var(--velin); background: var(--grenat); border-color: var(--grenat); }
    .pp-logout svg { width: 13px; height: 13px; }

    /* ─── SALLE FERMÉE (ERREUR) ── */
    .pp-error {
        min-height: 100vh;
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        text-align: center; padding: 2rem; position: relative;
        background: radial-gradient(125% 90% at 50% -8%, #2c4a5e 0%, #213A4C 45%, #182c39 100%);
        color: #EAE2D2;
    }
    .pp-error::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(ellipse 50% 50% at 50% 50%, var(--halo) 0%, transparent 70%);
        pointer-events: none;
    }
    /* Cordon de velours barrant l'entrée */
    .pp-error::after {
        content: '';
        position: absolute; top: 30%; left: 12%; right: 12%; height: 5px;
        background: var(--velours); border-radius: 99px;
        box-shadow: 0 4px 14px rgba(0,0,0,.4), 0 0 0 6px rgba(122,34,51,.18);
    }
    .pp-error-label {
        font-size: .72rem; letter-spacing: 0.26em; text-transform: uppercase;
        color: var(--or-clair); margin-bottom: 2rem; position: relative; z-index: 1;
    }
    .pp-error-title {
        font-family: var(--font-display); font-weight: 600;
        font-size: clamp(4.5rem, 17vw, 17rem); line-height: 0.88; color: #FBF6EC;
        margin-bottom: 2.5rem; position: relative; z-index: 1;
    }
    .pp-error-desc {
        font-size: 1.05rem; color: rgba(234,226,210,.7); max-width: 400px; line-height: 1.75;
        margin-bottom: 2.5rem; position: relative; z-index: 1;
    }
    .pp-error-desc em { font-style: italic; color: var(--or-clair); }
    .pp-back-btn {
        display: inline-flex; align-items: center; gap: .6rem;
        background: var(--velin); color: var(--sepia);
        padding: .9rem 2rem; border: 1px solid var(--or); border-radius: var(--r-medaillon);
        font-family: var(--font-body); font-size: .95rem; font-weight: 600;
        text-decoration: none; transition: all .25s; position: relative; z-index: 1;
        box-shadow: 0 10px 30px rgba(0,0,0,.3);
    }
    .pp-back-btn:hover { background: var(--grenat); color: var(--velin); border-color: var(--grenat); transform: scale(1.03); }

    /* ─── SALLE D'HONNEUR (HERO) ── */
    .pp-hero {
        padding: 10rem 2.5rem 4rem; max-width: 1400px; margin: 0 auto;
        display: grid; grid-template-columns: 150px 1fr; gap: 3.5rem; align-items: start;
        border-bottom: 1px solid var(--filet-or);
    }
    .pp-avatar {
        width: 130px; height: 130px; border-radius: 50%; object-fit: cover;
        border: 3px solid var(--velin);
        box-shadow: 0 0 0 2px var(--or), 0 14px 34px var(--ombre-cadre);
        display: block; margin-top: .5rem; transition: box-shadow .3s, filter .3s;
    }
    .pp-avatar:hover { box-shadow: 0 0 0 2px var(--or-clair), 0 16px 40px var(--ombre-cadre); filter: brightness(1.04); }
    .pp-avatar-placeholder {
        width: 130px; height: 130px; border-radius: 50%;
        background: var(--stuc); border: 3px solid var(--velin);
        box-shadow: 0 0 0 2px var(--or), 0 14px 34px var(--ombre-cadre);
        display: flex; align-items: center; justify-content: center; margin-top: .5rem;
    }
    .pp-avatar-placeholder svg { width: 42px; height: 42px; color: var(--or-ombre); }

    .pp-hero-right { display: flex; flex-direction: column; gap: 1.5rem; }
    .pp-name {
        font-family: var(--font-display); font-weight: 600;
        font-size: clamp(3rem, 7vw, 6rem); line-height: 0.9; color: var(--sepia); letter-spacing: -0.01em;
    }
    .pp-meta { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: .5rem; }
    .pp-pseudo {
        font-size: .85rem; font-weight: 600; letter-spacing: 0.08em; color: var(--or-ombre);
        background: var(--velin); border: 1px solid var(--or); padding: .3rem .8rem; border-radius: var(--r-medaillon);
    }
    .pp-share-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        font-family: var(--font-body); font-size: .82rem; font-weight: 600; letter-spacing: 0.03em;
        color: var(--or-ombre); padding: .3rem .85rem;
        border: 1px solid var(--filet-or); border-radius: var(--r-medaillon);
        background: var(--velin); transition: all .25s; cursor: none;
    }
    .pp-share-btn:hover { border-color: var(--or); box-shadow: 0 0 0 3px var(--halo); }
    .pp-share-btn svg { width: 12px; height: 12px; }

    .pp-bio { max-width: 560px; }
    .pp-bio-label { font-size: .72rem; letter-spacing: 0.22em; text-transform: uppercase; color: var(--or-ombre); margin-bottom: .5rem; display: block; }
    .pp-bio p { font-size: 1.1rem; line-height: 1.8; color: var(--sepia-doux); }
    .pp-bio-empty { font-style: italic; opacity: .5; }

    /* ─── FRISE-GUIRLANDE (TICKER) ── */
    .pp-ticker {
        border-top: 1px solid var(--filet-or); border-bottom: 1px solid var(--filet-or);
        background: var(--stuc); padding: .9rem 0; overflow: hidden;
    }
    .pp-ticker-track { display: flex; gap: 0; animation: marquee 32s linear infinite; white-space: nowrap; width: max-content; }
    .pp-ticker-item {
        display: inline-flex; align-items: center; gap: 1.5rem;
        font-family: var(--font-display); font-weight: 500; font-size: 1rem; letter-spacing: 0.12em;
        color: var(--or-ombre); padding: 0 1.5rem;
    }
    .pp-ticker-sep { color: var(--or); }
    @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }

    /* ─── SALLES / COLLECTION ── */
    .pp-collection { max-width: 1400px; margin: 0 auto; padding: 0 2.5rem 6rem; }
    .pp-category { padding: 3.5rem 0; border-bottom: 1px solid var(--filet); }
    .pp-category:last-child { border-bottom: none; }
    .pp-category-header { margin-bottom: 2rem; }
    .pp-category-title {
        font-family: var(--font-display); font-weight: 600;
        font-size: clamp(2.25rem, 5vw, 4rem); line-height: 0.9; color: var(--sepia); letter-spacing: -0.01em;
        position: relative; padding-top: 1.4rem;
    }
    .pp-category-title::before { content: '\10086'; position: absolute; top: 0; left: 0; font-size: 1rem; color: var(--or); }

    /* ─── CIMAISE ── */
    .pp-scroll {
        display: flex; gap: 1.25rem; overflow-x: auto; overflow-y: hidden; padding-bottom: .75rem;
        scrollbar-width: thin; scrollbar-color: var(--or) transparent;
    }
    .pp-scroll::-webkit-scrollbar { height: 3px; }
    .pp-scroll::-webkit-scrollbar-thumb { background: var(--dorure); border-radius: 99px; }

    /* ─── ŒUVRES ENCADRÉES ── */
    .pp-album { flex: 0 0 200px; display: flex; flex-direction: column; gap: .65rem; }
    .pp-cover {
        position: relative; width: 200px; height: 200px; border-radius: var(--r-cartel); overflow: hidden;
        background: var(--stuc); border: 6px solid var(--velin);
        box-shadow: 0 0 0 2px var(--or), 0 16px 34px var(--ombre-cadre), inset 0 2px 6px rgba(0,0,0,.18);
        transition: box-shadow .35s, filter .35s;
    }
    .pp-album:hover .pp-cover { box-shadow: 0 0 0 2px var(--or-clair), 0 20px 44px var(--ombre-cadre); filter: brightness(1.05) saturate(1.04); }
    .pp-cover img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .4s cubic-bezier(0.4,0,0.2,1); }

    .pp-cover-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(to top, rgba(43,33,24,.88) 0%, rgba(43,33,24,.32) 48%, transparent 100%);
        display: flex; flex-direction: column; justify-content: flex-end; padding: .85rem;
        opacity: 0; transition: opacity .3s;
    }
    .pp-album:hover .pp-cover-overlay { opacity: 1; }
    .pp-cover-overlay .pp-album-title {
        font-family: var(--font-body); font-size: .92rem; font-weight: 600; color: var(--velin); margin: 0 0 .2rem; line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .pp-cover-overlay .pp-album-artist { font-style: italic; font-size: .8rem; color: rgba(251,246,236,.7); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .pp-album-info { display: none; }
    .pp-album-info .pp-album-title { font-family: var(--font-body); font-size: .9rem; font-weight: 600; color: var(--sepia); line-height: 1.3; }
    .pp-album-info .pp-album-artist { font-style: italic; font-size: .8rem; color: var(--sepia-doux); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .pp-cover-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
    .pp-cover-placeholder svg { width: 2.5rem; height: 2.5rem; color: var(--or-ombre); opacity: .4; }

    /* ─── CIMAISE VIDE ── */
    .pp-no-albums { padding: 3rem 0; display: flex; flex-direction: column; align-items: center; gap: .75rem; color: var(--muted); }
    .pp-no-albums svg { width: 2rem; height: 2rem; color: var(--filet-or); }
    .pp-no-albums p { font-size: .9rem; letter-spacing: 0.08em; text-transform: uppercase; }

    .pp-empty { padding: 6rem 2.5rem; text-align: center; max-width: 1400px; margin: 0 auto; }
    .pp-empty svg { width: 2.5rem; height: 2.5rem; color: var(--filet-or); margin-bottom: 1rem; }
    .pp-empty p { font-size: .92rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); }

    /* ─── SOCLE (FOOTER) ── */
    .pp-footer {
        border-top: 1px solid var(--filet-or); background: var(--marbre);
        padding: 1.75rem 2.5rem; display: flex; justify-content: space-between;
        font-size: .8rem; letter-spacing: 0.04em; color: var(--sepia-doux); max-width: 1400px; margin: 4rem auto 0;
    }

    /* ─── CARTEL VOLANT (TOAST) ── */
    .pp-toast {
        position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%) translateY(20px);
        background: var(--velin); border: 1px solid var(--or); border-radius: var(--r-medaillon);
        padding: .65rem 1.5rem; font-size: .88rem; font-weight: 600; letter-spacing: 0.02em;
        color: var(--sepia); z-index: 5000; opacity: 0;
        transition: all .35s cubic-bezier(0.16,1,0.3,1); pointer-events: none; box-shadow: 0 14px 36px var(--ombre-cadre);
    }
    .pp-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

    /* ─── LEVER DE VOILE ── */
    .js-reveal { opacity: 0; transform: translateY(24px); transition: opacity .9s cubic-bezier(0.16,1,0.3,1), transform .9s cubic-bezier(0.16,1,0.3,1); }
    .js-reveal.is-revealed { opacity: 1; transform: translateY(0); }
    .js-reveal-delay-1 { transition-delay: .1s; }
    .js-reveal-delay-2 { transition-delay: .2s; }

    /* ─── RESPONSIVE ── */
    @media (max-width: 768px) {
        body { cursor: auto; }
        .cursor, .cursor-ring { display: none; }
        .pp-header { padding: 1.25rem; }
        .pp-header.scrolled { padding: .9rem 1.25rem; }
        .pp-hero { grid-template-columns: 1fr; padding: 8rem 1.25rem 3rem; gap: 1.5rem; }
        .pp-avatar, .pp-avatar-placeholder { width: 88px; height: 88px; }
        .pp-collection, .pp-empty, .pp-footer { padding-left: 1.25rem; padding-right: 1.25rem; }
        .pp-category { padding: 2.5rem 0; }
        .pp-category-title { font-size: clamp(2rem, 8vw, 3rem); }
        .pp-album { flex: 0 0 150px; }
        .pp-cover { width: 150px; height: 150px; }
        .pp-cover-overlay { opacity: 1; }
        .pp-album-info { display: flex; flex-direction: column; gap: .15rem; }
        .pp-footer { flex-direction: column; gap: .4rem; text-align: center; }
        .pp-error::after { left: 6%; right: 6%; }
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
    <meta name="theme-color" content="#F1E9DA">
    <meta name="description" content="' . htmlspecialchars($description) . '">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/img/logo.ico">
    <title>' . htmlspecialchars($title) . '</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..900;1,9..144,400..900&family=EB+Garamond:ital,wght@0,400..600;1,400..600&display=swap" rel="stylesheet">
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
    <a href="/" class="pp-logo">
        <svg class="lys" viewBox="0 0 24 30" fill="currentColor" aria-hidden="true"><path d="M12 0c1.6 2.3 1.6 4.7 0 7-1.6-2.3-1.6-4.7 0-7zM12 7c2.4 1.2 3.6 3.2 3.4 6.2 2.2-1.4 4.4-1 6.6 1.2-3 .4-4.6 2-4.8 4.8-1.6-1.8-3.4-2.4-5.2-1.8v8.2c2-.4 3.8-.2 5.4 1.4H8.6c1.6-1.6 3.4-1.8 5.4-1.4v-8.2c-1.8-.6-3.6 0-5.2 1.8-.2-2.8-1.8-4.4-4.8-4.8 2.2-2.2 4.4-2.6 6.6-1.2C8.4 10.2 9.6 8.2 12 7z"/></svg>
        UNIVERSON
    </a>';
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
    <span class="pp-error-label">— Erreur 404 · Salle introuvable</span>
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
    <span class="pp-error-label">— Accès restreint · Salle fermée</span>
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

<!-- Salle d'honneur -->
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

<!-- Frise-guirlande -->
<div class="pp-ticker" aria-hidden="true">
    <div class="pp-ticker-track">
        <span class="pp-ticker-item">COLLECTION DE @<?= htmlspecialchars(strtoupper($publicUser['pseudo'])) ?> <span class="pp-ticker-sep">&#10086;</span></span>
        <span class="pp-ticker-item">UNIVERSON <span class="pp-ticker-sep">&#10086;</span></span>
        <span class="pp-ticker-item">MUSÉE MUSICAL <span class="pp-ticker-sep">&#10086;</span></span>
        <span class="pp-ticker-item">EXPOSITION PERSONNELLE <span class="pp-ticker-sep">&#10086;</span></span>
        <span class="pp-ticker-item">COLLECTION DE @<?= htmlspecialchars(strtoupper($publicUser['pseudo'])) ?> <span class="pp-ticker-sep">&#10086;</span></span>
        <span class="pp-ticker-item">UNIVERSON <span class="pp-ticker-sep">&#10086;</span></span>
        <span class="pp-ticker-item">MUSÉE MUSICAL <span class="pp-ticker-sep">&#10086;</span></span>
        <span class="pp-ticker-item">EXPOSITION PERSONNELLE <span class="pp-ticker-sep">&#10086;</span></span>
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
            <p>Salle en cours d'accrochage</p>
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
