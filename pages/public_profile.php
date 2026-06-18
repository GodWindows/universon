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
        /* Salon vert (musée Cognacq-Jay) */
        --salon-vert:#2f5d44; --salon-vert-clair:#4a7a5d; --salon-vert-fonce:#1d3d2c; --salon-vert-profond:#13261c;
        --parquet:#7a5a36; --parquet-clair:#9c7a4a; --parquet-fonce:#4f3a22;
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

    /* ════════════════════════════════════════════════════════════════
       TOGGLE DE VUE  ·  Classique / Salon
    ════════════════════════════════════════════════════════════════ */
    .pp-viewtoggle {
        position: fixed; top: 1.5rem; left: 50%; transform: translateX(-50%); z-index: 260;
        display: inline-flex; align-items: center; gap: 2px; padding: 3px;
        background: rgba(251,246,236,.9); backdrop-filter: blur(10px);
        border: 1px solid var(--filet-or); border-radius: var(--r-medaillon);
        box-shadow: 0 6px 20px var(--ombre-cadre), inset 0 1px 2px rgba(43,33,24,.06);
        transition: top .4s;
    }
    .pp-header.scrolled ~ .pp-viewtoggle, body.salon-active .pp-viewtoggle { top: .9rem; }
    .pp-viewtoggle button {
        display: inline-flex; align-items: center; gap: .4rem;
        font-family: var(--font-body); font-size: .8rem; font-weight: 600; letter-spacing: 0.04em;
        color: var(--sepia-doux); padding: .42rem .95rem; border: none; background: none;
        border-radius: var(--r-medaillon); cursor: none; transition: all .28s cubic-bezier(.16,1,.3,1);
    }
    .pp-viewtoggle button svg { width: 14px; height: 14px; }
    .pp-viewtoggle button:hover { color: var(--sepia); }
    .pp-viewtoggle button.active {
        background: var(--velours); color: var(--velin);
        box-shadow: 0 4px 12px var(--ombre-cadre), inset 0 1px 0 rgba(255,255,255,.12);
    }

    /* ════════════════════════════════════════════════════════════════
       VUE SALON  ·  galerie 3D Three.js (musée vert immersif)
    ════════════════════════════════════════════════════════════════ */
    .pp-salon { display: none; }
    body.salon-active { overflow: hidden; }
    body.salon-active .pp-salon {
        display: block; position: fixed; inset: 0; z-index: 100; background: #0c1c14;
    }
    body.salon-active .pp-ticker,
    body.salon-active .pp-collection,
    body.salon-active .pp-footer { display: none; }

    #salonCanvas { display: block; width: 100%; height: 100%; cursor: grab; touch-action: none; }
    #salonCanvas.grabbing { cursor: grabbing; }
    #salonCanvas.pointing { cursor: pointer; }

    /* loader */
    .salon-loader {
        position: absolute; inset: 0; z-index: 3; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 1.2rem; text-align: center;
        background: radial-gradient(125% 90% at 50% 30%, #1d3d2c 0%, #0c1c14 70%);
        color: var(--craie); transition: opacity .8s ease; padding: 2rem;
    }
    .salon-loader.hidden { opacity: 0; pointer-events: none; }
    .salon-loader .ring {
        width: 46px; height: 46px; border: 2px solid var(--filet-or); border-top-color: var(--or-clair);
        border-radius: 50%; animation: salon-spin 1s linear infinite;
    }
    @keyframes salon-spin { to { transform: rotate(360deg); } }
    .salon-loader .label { font-size: .72rem; letter-spacing: .26em; text-transform: uppercase; color: var(--or-clair); }
    .salon-loader .title { font-family: var(--font-display); font-weight: 600; font-size: clamp(1.6rem,4vw,2.4rem); }
    .salon-loader .title em { font-style: italic; color: var(--or-clair); }

    /* HUD */
    .salon-hud { position: absolute; inset: 0; z-index: 2; pointer-events: none; }
    .salon-hud .room-cartel {
        position: absolute; top: 5.2rem; left: 50%; transform: translateX(-50%);
        background: rgba(12,28,20,.55); border: 1px solid var(--filet-or); backdrop-filter: blur(6px);
        border-radius: var(--r-cartel); padding: .55rem 1.4rem; text-align: center; max-width: 80vw;
        opacity: 0; transition: opacity .5s;
    }
    .salon-hud .room-cartel.show { opacity: 1; }
    .salon-hud .room-cartel .t { font-family: var(--font-body); font-weight: 600; font-size: 1rem; color: var(--velin); }
    .salon-hud .room-cartel .a { font-style: italic; font-size: .82rem; color: var(--or-clair); }
    .salon-hud .room-cartel .lab { font-size: .62rem; letter-spacing: .24em; text-transform: uppercase; color: var(--or-clair); display: block; margin-bottom: .25rem; }

    .salon-hint {
        position: absolute; bottom: 1.6rem; left: 50%; transform: translateX(-50%);
        display: inline-flex; align-items: center; gap: 1.4rem; flex-wrap: wrap; justify-content: center;
        background: rgba(12,28,20,.5); border: 1px solid var(--filet-or); backdrop-filter: blur(6px);
        border-radius: var(--r-medaillon); padding: .55rem 1.4rem; max-width: 92vw;
        font-style: italic; color: var(--craie); font-size: .85rem;
    }
    .salon-hint kbd {
        font-style: normal; font-size: .68rem; letter-spacing: .04em;
        background: rgba(251,246,236,.12); border: 1px solid var(--filet-or); border-radius: var(--r-medaillon);
        padding: .12rem .55rem; color: var(--or-clair); margin-right: .4rem;
    }
    .salon-hint .sep { color: var(--or); }
    .salon-empty-note {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); z-index: 2;
        text-align: center; color: var(--craie); font-style: italic; opacity: .8; pointer-events: none;
        font-size: 1rem; letter-spacing: .04em;
    }
    .salon-nowebgl {
        position: absolute; inset: 0; z-index: 4; display: none; flex-direction: column; gap: 1rem;
        align-items: center; justify-content: center; text-align: center; padding: 2rem;
        background: radial-gradient(125% 90% at 50% 30%, #1d3d2c 0%, #0c1c14 70%); color: var(--craie);
    }
    .salon-nowebgl.show { display: flex; }

    @media (max-width: 768px) {
        .salon-hint { font-size: .78rem; gap: .9rem; padding: .5rem 1rem; }
        .salon-hud .room-cartel { top: 4.4rem; }
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

    /* Albums indispensables (catégorie « favorite ») → paires pour la vue Salon */
    $favoriteAlbums = $categoriesAlbums['favorite'] ?? [];
    $salonRooms = array_chunk($favoriteAlbums, 2);
    if (empty($salonRooms)) $salonRooms = [[]]; // une salle vide « en cours d'accrochage »

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
                <div class="pp-viewtoggle" role="group" aria-label="Mode d'affichage">
                    <button type="button" id="viewClassicBtn" class="active" aria-pressed="true">
                        <i data-lucide="layout-grid"></i> Classique
                    </button>
                    <button type="button" id="viewSalonBtn" aria-pressed="false">
                        <i data-lucide="frame"></i> Salon
                    </button>
                </div>
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

<!-- ════════ VUE SALON · galerie 3D Three.js ════════ -->
<?php
    /* Une salle (couleur) par catégorie, dans cet ordre ; on n'inclut que les
       catégories qui contiennent au moins un album. */
    $salonOrder = ['favorite', 'most_played', 'guilty_pleasure'];
    $catDesc = [];
    foreach ($categories as $c) { $catDesc[$c['name']] = $c['description']; }
    $mapAlbum = function ($a) {
        return [
            'name'   => $a['name'],
            'artist' => $a['artist_name'] ?? '',
            'img'    => !empty($a['image_url_100']) ? $a['image_url_100'] : ($a['image_url_60'] ?? ''),
        ];
    };
    $orderedKeys = $salonOrder;
    foreach (array_keys($categoriesAlbums) as $k) { if (!in_array($k, $orderedKeys, true)) $orderedKeys[] = $k; }
    $salonCats = [];
    foreach ($orderedKeys as $key) {
        $albums = $categoriesAlbums[$key] ?? [];
        if (empty($albums)) continue;
        $salonCats[] = [
            'key'    => $key,
            'label'  => $catDesc[$key] ?? $key,
            'albums' => array_map($mapAlbum, $albums),
        ];
    }
    $salonData = ['categories' => $salonCats];
?>
<div class="pp-salon" id="ppSalon">
    <canvas id="salonCanvas"></canvas>

    <div class="salon-hud">
        <div class="room-cartel" id="salonCartel">
            <span class="lab">Album indispensable</span>
            <span class="t"></span>
            <span class="a"></span>
        </div>
        <div class="salon-hint">
            <span><kbd>Glisser</kbd>regarder</span>
            <span class="sep">&#10086;</span>
            <span><kbd>Molette</kbd>avancer</span>
            <span class="sep">&#10086;</span>
            <span><kbd>Clic</kbd>une œuvre</span>
        </div>
    </div>

    <?php if (empty($salonCats)): ?>
    <div class="salon-empty-note">Aucun album catégorisé à accrocher pour le moment.</div>
    <?php endif; ?>

    <div class="salon-nowebgl" id="salonNoWebgl">
        <span class="pp-bio-label" style="color:var(--or-clair)">❧ 3D indisponible ❧</span>
        <p>Votre navigateur ne supporte pas la 3D temps réel.<br>Repassez en vue classique pour parcourir la collection.</p>
    </div>

    <div class="salon-loader" id="salonLoader">
        <span class="label">❧ Vernissage ❧</span>
        <div class="ring"></div>
        <div class="title">Le <em>Salon</em> de @<?= htmlspecialchars($publicUser['pseudo']) ?></div>
    </div>
</div><!-- .pp-salon -->

<script id="salonData" type="application/json"><?= json_encode($salonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

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

    /* ── Toggle de vue + chargement paresseux du moteur 3D ── */
    (function () {
        var classicBtn = document.getElementById('viewClassicBtn');
        var salonBtn   = document.getElementById('viewSalonBtn');
        if (!classicBtn || !salonBtn) return;
        var KEY = 'universon-view';
        var THREE_SRC = 'https://unpkg.com/three@0.150.1/build/three.min.js';
        var engine = null, loadingThree = false;

        function setUI(isSalon) {
            document.body.classList.toggle('salon-active', isSalon);
            salonBtn.classList.toggle('active', isSalon);
            classicBtn.classList.toggle('active', !isSalon);
            salonBtn.setAttribute('aria-pressed', isSalon ? 'true' : 'false');
            classicBtn.setAttribute('aria-pressed', isSalon ? 'false' : 'true');
        }
        function ensureEngine() {
            if (engine || loadingThree) return;
            if (typeof THREE !== 'undefined') { engine = buildSalon(); start(); return; }
            loadingThree = true;
            var s = document.createElement('script');
            s.src = THREE_SRC; s.async = true;
            s.onload = function () { loadingThree = false; engine = buildSalon(); start(); };
            s.onerror = function () {
                loadingThree = false;
                var n = document.getElementById('salonNoWebgl'); if (n) n.classList.add('show');
                var l = document.getElementById('salonLoader'); if (l) l.classList.add('hidden');
            };
            document.head.appendChild(s);
        }
        function start() { if (engine && document.body.classList.contains('salon-active')) engine.resume(); }

        function enter() { setUI(true); ensureEngine(); if (engine) engine.resume(); }
        function exit()  { setUI(false); if (engine) engine.pause(); }

        classicBtn.addEventListener('click', function () { exit(); try { localStorage.setItem(KEY, 'classic'); } catch (e) {} });
        salonBtn.addEventListener('click',   function () { enter(); try { localStorage.setItem(KEY, 'salon'); } catch (e) {} });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && document.body.classList.contains('salon-active')) { exit(); try { localStorage.setItem(KEY, 'classic'); } catch (e2) {} } });

        var saved; try { saved = localStorage.getItem(KEY); } catch (e) {}
        if (saved === 'salon') enter();

        /* ════════════════════ MOTEUR SALON 3D (Three.js) ════════════════════ */
        function buildSalon() {
            var canvas = document.getElementById('salonCanvas');
            var loaderEl = document.getElementById('salonLoader');
            var cartelEl = document.getElementById('salonCartel');
            var cartelT = cartelEl ? cartelEl.querySelector('.t') : null;
            var cartelA = cartelEl ? cartelEl.querySelector('.a') : null;

            function webglOK() { try { var c = document.createElement('canvas'); return !!(window.WebGLRenderingContext && (c.getContext('webgl') || c.getContext('experimental-webgl'))); } catch (e) { return false; } }
            if (!webglOK()) { document.getElementById('salonNoWebgl').classList.add('show'); if (loaderEl) loaderEl.classList.add('hidden'); return null; }

            var data = [];
            try { data = JSON.parse(document.getElementById('salonData').textContent || '[]'); } catch (e) {}
            var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            var renderer;
            try { renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true }); }
            catch (e) { document.getElementById('salonNoWebgl').classList.add('show'); if (loaderEl) loaderEl.classList.add('hidden'); return null; }
            renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
            renderer.outputColorSpace = THREE.SRGBColorSpace;

            var scene = new THREE.Scene();
            scene.background = new THREE.Color(0x141009);
            scene.fog = new THREE.Fog(0x141009, 14, 62);
            var camera = new THREE.PerspectiveCamera(60, 1, 0.1, 220);
            camera.rotation.order = 'YXZ';

            /* palette */
            var GOLD = 0xb5912f, GOLD_L = 0xe6c86e, CREAM = 0xf1e9da, STUC = 0xe5dac4;

            /* ── une couleur de salle par catégorie ── */
            var PALETTES = {
                favorite:        { top: '#3f7257', mid: '#336249', bot: '#234734', line: 'rgba(18,38,26,.38)',  wall: 0x336249 },
                most_played:     { top: '#9c4651', mid: '#7a2f3a', bot: '#551f28', line: 'rgba(40,10,16,.42)',  wall: 0x7a2f3a },
                guilty_pleasure: { top: '#3f6280', mid: '#2c4a63', bot: '#1b3043', line: 'rgba(8,20,36,.42)',   wall: 0x2c4a63 },
                _a:              { top: '#7a6a92', mid: '#574a6e', bot: '#392f4a', line: 'rgba(20,12,34,.42)',  wall: 0x574a6e },
                _b:              { top: '#9a7a3e', mid: '#7a5e2c', bot: '#54401c', line: 'rgba(36,24,8,.42)',   wall: 0x7a5e2c },
                _default:        { top: '#3f7257', mid: '#336249', bot: '#234734', line: 'rgba(18,38,26,.38)',  wall: 0x336249 }
            };
            var EXTRA = ['_a', '_b'], extraI = 0, palKeyMap = {};
            function palOf(key) {
                if (PALETTES[key]) return PALETTES[key];
                if (!palKeyMap[key]) { palKeyMap[key] = EXTRA[extraI % EXTRA.length]; extraI++; }
                return PALETTES[palKeyMap[key]];
            }

            /* ── textures procédurales ── */
            function parquetTex() {
                var c = document.createElement('canvas'); c.width = c.height = 256; var g = c.getContext('2d');
                g.fillStyle = '#5c4329'; g.fillRect(0, 0, 256, 256);
                var cols = ['#7a5a36', '#8a6a40', '#6d5030', '#83613a', '#74552f'];
                var bw = 64, bh = 32;
                for (var row = 0, y = 0; y < 256; y += bh, row++) {
                    var off = (row % 2) * (bw / 2);
                    for (var x = -bw; x < 256; x += bw) {
                        var px = x + off;
                        g.fillStyle = cols[(Math.random() * cols.length) | 0];
                        g.fillRect(px + 1, y + 1, bw - 2, bh - 2);
                        g.strokeStyle = 'rgba(40,28,15,.5)'; g.lineWidth = 1;
                        for (var k = 0; k < 3; k++) { g.beginPath(); var gy = y + 6 + k * 9; g.moveTo(px + 2, gy); g.lineTo(px + bw - 2, gy + (Math.random() * 2 - 1)); g.stroke(); }
                    }
                }
                var t = new THREE.CanvasTexture(c); t.wrapS = t.wrapT = THREE.RepeatWrapping; t.colorSpace = THREE.SRGBColorSpace; return t;
            }
            function wallTex(pal) {
                var c = document.createElement('canvas'); c.width = c.height = 256; var g = c.getContext('2d');
                var grd = g.createLinearGradient(0, 0, 0, 256);
                grd.addColorStop(0, pal.top); grd.addColorStop(0.5, pal.mid); grd.addColorStop(1, pal.bot);
                g.fillStyle = grd; g.fillRect(0, 0, 256, 256);
                g.strokeStyle = pal.line; g.lineWidth = 2;
                for (var i = 1; i < 4; i++) { g.beginPath(); g.moveTo(i * 64, 0); g.lineTo(i * 64, 256); g.stroke(); }
                for (var n = 0; n < 1600; n++) { g.fillStyle = 'rgba(255,255,255,' + (Math.random() * 0.025) + ')'; g.fillRect(Math.random() * 256, Math.random() * 256, 1, 1); }
                var t = new THREE.CanvasTexture(c); t.wrapS = t.wrapT = THREE.RepeatWrapping; t.colorSpace = THREE.SRGBColorSpace; return t;
            }

            /* ── matériaux ── */
            var floorMat = new THREE.MeshStandardMaterial({ map: parquetTex(), roughness: 0.65, metalness: 0.04 });
            var ceilMat  = new THREE.MeshStandardMaterial({ color: CREAM, roughness: 1, side: THREE.DoubleSide });
            var goldMat  = new THREE.MeshStandardMaterial({ color: GOLD, metalness: 0.9, roughness: 0.32, emissive: 0x3c2e0d, emissiveIntensity: 0.5 });
            var darkWood = new THREE.MeshStandardMaterial({ color: 0x281d12, roughness: 1 });

            /* murs : texture de gradient par catégorie (côtés) + couleur plate (transversaux) */
            var wallTexCache = {}, flatMatCache = {};
            function wallTexOf(key) { if (!wallTexCache[key]) wallTexCache[key] = wallTex(palOf(key)); return wallTexCache[key]; }
            function sideWallMat(key, rx, ry) {
                var t = wallTexOf(key).clone(); t.needsUpdate = true; t.wrapS = t.wrapT = THREE.RepeatWrapping; t.repeat.set(rx, ry); t.colorSpace = THREE.SRGBColorSpace;
                return new THREE.MeshStandardMaterial({ map: t, roughness: 0.92, metalness: 0, side: THREE.DoubleSide });
            }
            function flatWallMat(key) { if (!flatMatCache[key]) flatMatCache[key] = new THREE.MeshStandardMaterial({ color: palOf(key).wall, roughness: 0.95, metalness: 0, side: THREE.DoubleSide }); return flatMatCache[key]; }

            /* ── salles : une travée (paire) par catégorie, à la suite ── */
            var cats = (data && data.categories) ? data.categories : [];
            var bayList = [];
            cats.forEach(function (cat) {
                var al = cat.albums || [];
                for (var p = 0; p < al.length; p += 2) {
                    bayList.push({ key: cat.key, label: (p === 0 ? cat.label : null), a0: al[p] || null, a1: al[p + 1] || null });
                }
            });
            if (bayList.length === 0) bayList.push({ key: 'favorite', label: null, a0: null, a1: null });
            var nBays = bayList.length;

            /* ── dimensions ── */
            var W = 10, H = 7, DOOR_W = 3.2, DOOR_H = 5, ROOM_D = 9;
            function zWall(i) { return -2 - i * ROOM_D; }
            var zEnd = zWall(nBays);
            var zFront = 6;
            var spanZ = zFront - (zEnd - 1);
            var midZ = (zFront + (zEnd - 1)) / 2;

            /* sol + plafond (continus) */
            floorMat.map.repeat.set(6, Math.max(8, Math.round(spanZ / 2)));
            var floor = new THREE.Mesh(new THREE.PlaneGeometry(W, spanZ), floorMat);
            floor.rotation.x = -Math.PI / 2; floor.position.set(0, 0, midZ); scene.add(floor);
            var ceil = new THREE.Mesh(new THREE.PlaneGeometry(W, spanZ), ceilMat);
            ceil.rotation.x = Math.PI / 2; ceil.position.set(0, H, midZ); scene.add(ceil);

            /* murs latéraux d'une salle (de zA à zB) à la couleur de sa catégorie */
            function sideWalls(zA, zB, key) {
                var len = Math.abs(zA - zB), cz = (zA + zB) / 2;
                [-1, 1].forEach(function (s) {
                    var wall = new THREE.Mesh(new THREE.PlaneGeometry(len, H), sideWallMat(key, len / 4, H / 4));
                    wall.rotation.y = s * Math.PI / 2; wall.position.set(s * W / 2, H / 2, cz); scene.add(wall);
                    var corn = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.28, len), goldMat);
                    corn.position.set(s * (W / 2 - 0.09), H - 0.32, cz); scene.add(corn);
                    var base = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.5, len), darkWood);
                    base.position.set(s * (W / 2 - 0.08), 0.25, cz); scene.add(base);
                });
            }

            /* ── lustre ── */
            function chandelier(z) {
                var grp = new THREE.Group();
                var chain = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 1, 6), goldMat);
                chain.position.y = H - 0.5; grp.add(chain);
                var ring = new THREE.Mesh(new THREE.TorusGeometry(0.5, 0.06, 8, 24), goldMat);
                ring.rotation.x = Math.PI / 2; ring.position.y = H - 1; grp.add(ring);
                var core = new THREE.Mesh(new THREE.SphereGeometry(0.16, 12, 12), goldMat);
                core.position.y = H - 1; grp.add(core);
                var bulbMat = new THREE.MeshStandardMaterial({ color: GOLD_L, emissive: 0xffd98a, emissiveIntensity: 1.6, roughness: 0.4 });
                for (var b = 0; b < 8; b++) {
                    var a = (b / 8) * Math.PI * 2;
                    var bulb = new THREE.Mesh(new THREE.SphereGeometry(0.09, 8, 8), bulbMat);
                    bulb.position.set(Math.cos(a) * 0.5, H - 0.9, Math.sin(a) * 0.5); grp.add(bulb);
                }
                var light = new THREE.PointLight(0xffe2a8, 1.5, ROOM_D * 2.2, 1.6);
                light.position.set(0, H - 1, 0); grp.add(light);
                grp.position.z = z; scene.add(grp);
                return { grp: grp, light: light, base: 1.5, phase: Math.random() * 6.28 };
            }
            var chandeliers = [];
            for (var ci = 0; ci <= nBays; ci++) {
                var cz = (ci === 0) ? (zFront + zWall(0)) / 2 : zWall(ci - 1) - ROOM_D / 2;
                chandeliers.push(chandelier(cz));
            }

            /* ── murs transversaux à porte + œuvres ── */
            var paintingMeshes = [];
            var loadList = [];
            var texLoader = new THREE.TextureLoader(); texLoader.setCrossOrigin('anonymous');

            function doorWall(z, solid, key) {
                var shape = new THREE.Shape();
                shape.moveTo(-W / 2, 0); shape.lineTo(W / 2, 0); shape.lineTo(W / 2, H); shape.lineTo(-W / 2, H); shape.lineTo(-W / 2, 0);
                if (!solid) {
                    var hole = new THREE.Path();
                    hole.moveTo(-DOOR_W / 2, 0); hole.lineTo(DOOR_W / 2, 0); hole.lineTo(DOOR_W / 2, DOOR_H); hole.lineTo(-DOOR_W / 2, DOOR_H); hole.lineTo(-DOOR_W / 2, 0);
                    shape.holes.push(hole);
                }
                var m = new THREE.Mesh(new THREE.ShapeGeometry(shape), flatWallMat(key));
                m.position.z = z; scene.add(m);
                if (!solid) {
                    [-1, 1].forEach(function (s) {
                        var jamb = new THREE.Mesh(new THREE.BoxGeometry(0.16, DOOR_H + 0.2, 0.22), goldMat);
                        jamb.position.set(s * (DOOR_W / 2 + 0.06), (DOOR_H + 0.2) / 2, z + 0.02); scene.add(jamb);
                    });
                    var lint = new THREE.Mesh(new THREE.BoxGeometry(DOOR_W + 0.5, 0.18, 0.24), goldMat);
                    lint.position.set(0, DOOR_H + 0.1, z + 0.02); scene.add(lint);
                }
            }

            /* enseigne de salle (cartouche doré au-dessus de la porte) */
            function signTex(label) {
                var c = document.createElement('canvas'); c.width = 768; c.height = 128; var g = c.getContext('2d');
                g.fillStyle = '#fbf6ec'; g.fillRect(0, 0, 768, 128);
                g.strokeStyle = '#b5912f'; g.lineWidth = 7; g.strokeRect(10, 10, 748, 108);
                g.fillStyle = '#856321'; g.textAlign = 'center'; g.textBaseline = 'middle';
                var txt = (label || '').toUpperCase();
                var fs = 46; if (txt.length > 22) fs = 38; if (txt.length > 30) fs = 30;
                g.font = '600 ' + fs + 'px Georgia, "Times New Roman", serif';
                // léger espacement de lettres
                var letters = txt.split(''), total = 0, widths = [];
                g.font = '600 ' + fs + 'px Georgia, serif';
                for (var i = 0; i < letters.length; i++) { var w = g.measureText(letters[i]).width + 4; widths.push(w); total += w; }
                var x = 384 - total / 2;
                for (var j = 0; j < letters.length; j++) { g.fillText(letters[j], x + widths[j] / 2, 66); x += widths[j]; }
                var t = new THREE.CanvasTexture(c); t.colorSpace = THREE.SRGBColorSpace; return t;
            }
            function categorySign(z, label, key) {
                var stex = signTex(label);
                var sign = new THREE.Mesh(new THREE.PlaneGeometry(3.6, 0.6), new THREE.MeshStandardMaterial({ map: stex, emissive: 0xffffff, emissiveMap: stex, emissiveIntensity: 0.3, roughness: 0.85 }));
                sign.position.set(0, DOOR_H + 0.7, z + 0.05); scene.add(sign);
            }

            function labelTex(name, artist) {
                var c = document.createElement('canvas'); c.width = 512; c.height = 130; var g = c.getContext('2d');
                g.fillStyle = '#fbf6ec'; g.fillRect(0, 0, 512, 130);
                g.strokeStyle = '#b5912f'; g.lineWidth = 6; g.strokeRect(8, 8, 496, 114);
                g.fillStyle = '#2b2118'; g.textAlign = 'center'; g.textBaseline = 'middle';
                g.font = '600 40px Georgia, "Times New Roman", serif';
                var nm = name.length > 26 ? name.slice(0, 25) + '…' : name;
                g.fillText(nm, 256, artist ? 52 : 65);
                if (artist) { g.fillStyle = '#5a4b38'; g.font = 'italic 31px Georgia, serif'; var ar = artist.length > 32 ? artist.slice(0, 31) + '…' : artist; g.fillText(ar, 256, 94); }
                var t = new THREE.CanvasTexture(c); t.colorSpace = THREE.SRGBColorSpace; return t;
            }
            function painting(album, x, z) {
                var PW = 2.5, FR = 0.26;
                var grp = new THREE.Group();
                var frame = new THREE.Mesh(new THREE.BoxGeometry(PW + FR, PW + FR, 0.16), goldMat);
                frame.position.set(x, 3.3, z + 0.05); grp.add(frame);
                var mat = new THREE.MeshStandardMaterial({ color: STUC, roughness: 0.55, metalness: 0.02 });
                var plane = new THREE.Mesh(new THREE.PlaneGeometry(PW, PW), mat);
                plane.position.set(x, 3.3, z + 0.14);
                plane.userData = { name: album.name || '', artist: album.artist || '' };
                grp.add(plane); paintingMeshes.push(plane);
                var ltex = labelTex(album.name || '', album.artist || '');
                var plaque = new THREE.Mesh(new THREE.PlaneGeometry(1.35, 0.34), new THREE.MeshStandardMaterial({ map: ltex, emissive: 0xffffff, emissiveMap: ltex, emissiveIntensity: 0.28, roughness: 0.85 }));
                plaque.position.set(x, 1.62, z + 0.13); grp.add(plaque);
                var spot = new THREE.SpotLight(0xfff0d0, 2.4, 8, Math.PI / 6, 0.5, 1.2);
                spot.position.set(x, 6.2, z + 2.2);
                spot.target.position.set(x, 3.3, z); grp.add(spot); grp.add(spot.target);
                scene.add(grp);
                if (album.img) {
                    loadList.push(1);
                    texLoader.load(album.img, function (tex) {
                        tex.colorSpace = THREE.SRGBColorSpace;
                        mat.map = tex; mat.color.set(0xffffff);
                        mat.emissive = new THREE.Color(0xffffff); mat.emissiveMap = tex; mat.emissiveIntensity = 0.16;
                        mat.needsUpdate = true; texDone();
                    }, undefined, function () { texDone(); });
                }
            }

            for (var i = 0; i < nBays; i++) {
                var b = bayList[i];
                var z = zWall(i);
                var roomFront = (i === 0) ? zFront : zWall(i - 1);
                sideWalls(roomFront, z, b.key);
                doorWall(z, false, b.key);
                if (b.label) categorySign(z, b.label, b.key);
                if (b.a0) painting(b.a0, -3.05, z);
                if (b.a1) painting(b.a1, 3.05, z);
            }
            /* salle finale décorative (couleur de la dernière catégorie) */
            var lastKey = bayList[nBays - 1].key;
            sideWalls(zWall(nBays - 1), zEnd, lastKey);
            doorWall(zEnd, true, lastKey);
            var endFrame = new THREE.Mesh(new THREE.BoxGeometry(2.2, 3, 0.16), goldMat);
            endFrame.position.set(0, 3.4, zEnd + 0.05); scene.add(endFrame);
            var endArt = new THREE.Mesh(new THREE.PlaneGeometry(1.9, 2.7), new THREE.MeshStandardMaterial({ color: 0xb39e72, roughness: 0.7, emissive: 0x4a3a18, emissiveIntensity: 0.25 }));
            endArt.position.set(0, 3.4, zEnd + 0.14); scene.add(endArt);

            /* ── lumières d'ambiance ── */
            scene.add(new THREE.AmbientLight(0xfff1d8, 0.5));
            scene.add(new THREE.HemisphereLight(0xfff4e0, 0x3a332a, 0.5));
            var keyLight = new THREE.DirectionalLight(0xfff0d8, 0.45); keyLight.position.set(2, 8, 8); scene.add(keyLight);

            /* ── chargement / loader ── */
            var pending = loadList.length, hidden = false;
            function hideLoader() { if (hidden) return; hidden = true; if (loaderEl) loaderEl.classList.add('hidden'); }
            function texDone() { pending--; if (pending <= 0) hideLoader(); }
            if (pending === 0) setTimeout(hideLoader, 400);
            setTimeout(hideLoader, 6000);

            /* ── caméra / navigation ── */
            var camZ = 4, camZTarget = 4, camX = 0, camXTarget = 0;
            var minZ = zEnd + 4, maxZ = 5;
            var yaw = 0, pitch = 0, tYaw = 0, tPitch = 0;
            var pNX = 0, pNY = 0;
            var dragging = false, lastX = 0, lastY = 0, dragDist = 0;
            var focusActive = false, focusX = 0;
            var lastInteract = performance.now() / 1000, cruiseDir = -1, elapsed = 0;

            var ray = new THREE.Raycaster(), ndc = new THREE.Vector2(), hovered = null;
            function setCursor(c) { canvas.classList.toggle('grabbing', c === 'grab'); canvas.classList.toggle('pointing', c === 'point'); }
            function showCartel(p) { if (!cartelEl) return; cartelT.textContent = p.name; cartelA.textContent = p.artist; cartelEl.classList.add('show'); }
            function hideCartel() { if (cartelEl && !focusActive) cartelEl.classList.remove('show'); }

            function raycastAt(cx, cy) {
                ndc.x = (cx / window.innerWidth) * 2 - 1; ndc.y = -(cy / window.innerHeight) * 2 + 1;
                ray.setFromCamera(ndc, camera);
                var hit = ray.intersectObjects(paintingMeshes, false)[0];
                return hit ? hit.object : null;
            }

            canvas.addEventListener('pointerdown', function (e) {
                dragging = true; dragDist = 0; lastX = e.clientX; lastY = e.clientY;
                canvas.classList.add('grabbing'); lastInteract = performance.now() / 1000;
                if (canvas.setPointerCapture) try { canvas.setPointerCapture(e.pointerId); } catch (er) {}
            });
            canvas.addEventListener('pointermove', function (e) {
                pNX = (e.clientX / window.innerWidth - 0.5) * 2;
                pNY = (e.clientY / window.innerHeight - 0.5) * 2;
                if (dragging) {
                    var dx = e.clientX - lastX, dy = e.clientY - lastY;
                    dragDist += Math.abs(dx) + Math.abs(dy);
                    tYaw -= dx * 0.0035; tPitch -= dy * 0.0032;
                    tPitch = Math.max(-0.42, Math.min(0.42, tPitch));
                    tYaw = Math.max(-0.9, Math.min(0.9, tYaw));
                    lastX = e.clientX; lastY = e.clientY; lastInteract = performance.now() / 1000;
                } else {
                    var p = raycastAt(e.clientX, e.clientY);
                    if (p !== hovered) { hovered = p; if (p) { showCartel(p.userData); setCursor('point'); } else { hideCartel(); setCursor(''); } }
                }
            });
            function endDrag(e) {
                if (!dragging) return; dragging = false; canvas.classList.remove('grabbing');
                if (dragDist < 6) {
                    var p = raycastAt(e.clientX, e.clientY);
                    if (p) { focusOn(p); }
                    else { focusActive = false; camXTarget = 0; tYaw = 0; tPitch = 0; hideCartel(); }
                }
            }
            function focusOn(p) {
                var px = p.position.x, pz = p.position.z;
                camZTarget = pz + 4.6; camXTarget = px * 0.5; focusX = px * 0.5;
                tYaw = -Math.atan2(px - focusX, camZTarget - pz); tPitch = 0.02;
                focusActive = true; showCartel(p.userData); lastInteract = performance.now() / 1000;
            }
            canvas.addEventListener('pointerup', endDrag);
            canvas.addEventListener('pointercancel', function () { dragging = false; canvas.classList.remove('grabbing'); });
            canvas.addEventListener('wheel', function (e) {
                e.preventDefault();
                focusActive = false; camXTarget = 0;
                camZTarget -= e.deltaY * 0.01;
                camZTarget = Math.max(minZ, Math.min(maxZ, camZTarget));
                lastInteract = performance.now() / 1000;
            }, { passive: false });

            /* ── boucle ── */
            var running = false, raf = null, clock = new THREE.Clock();
            function update(dt) {
                elapsed += dt;
                var now = performance.now() / 1000;
                if (!reduced && !focusActive && (now - lastInteract) > 4.5) {
                    camZTarget += cruiseDir * 1.5 * dt;
                    if (camZTarget <= minZ) { camZTarget = minZ; cruiseDir = 1; }
                    if (camZTarget >= maxZ) { camZTarget = maxZ; cruiseDir = -1; }
                }
                camZ += (camZTarget - camZ) * 0.06;
                camXTarget = focusActive ? focusX : 0;
                camX += (camXTarget - camX) * 0.06;
                var subYaw = focusActive ? 0 : pNX * 0.16, subPitch = focusActive ? 0 : -pNY * 0.10;
                yaw += ((tYaw + subYaw) - yaw) * 0.08;
                pitch += ((tPitch + subPitch) - pitch) * 0.08;
                var bob = reduced ? 0 : Math.sin(elapsed * 1.1) * 0.04;
                var swayX = (reduced || focusActive) ? 0 : Math.sin(elapsed * 0.5) * 0.12;
                camera.position.set(camX + swayX, 2.75 + bob, camZ);
                camera.rotation.set(pitch, yaw, reduced ? 0 : Math.sin(elapsed * 0.4) * 0.004);
                for (var k = 0; k < chandeliers.length; k++) {
                    var ch = chandeliers[k];
                    if (!reduced) ch.grp.rotation.z = Math.sin(elapsed * 0.8 + ch.phase) * 0.03;
                    ch.light.intensity = ch.base * (1 + (reduced ? 0 : Math.sin(elapsed * 7 + ch.phase) * 0.06));
                }
            }
            function loop() { if (!running) return; raf = requestAnimationFrame(loop); update(clock.getDelta()); renderer.render(scene, camera); }
            function resize() { var w = window.innerWidth, h = window.innerHeight; renderer.setSize(w, h, false); camera.aspect = w / h; camera.updateProjectionMatrix(); }
            window.addEventListener('resize', function () { if (running) resize(); });

            return {
                resume: function () { resize(); if (!running) { running = true; clock.getDelta(); loop(); } },
                pause: function () { running = false; if (raf) cancelAnimationFrame(raf); }
            };
        }
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
