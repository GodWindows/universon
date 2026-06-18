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
    <meta name="theme-color" content="#F1E9DA">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="../img/logo.ico">
    <title><?= htmlspecialchars($site_title) ?> — Mon Musée</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..900;1,9..144,400..900&family=EB+Garamond:ital,wght@0,400..600;1,400..600&display=swap" rel="stylesheet">
    <!-- Charte « Salon Doré » — styles autonomes -->
    <style>
        *, *::before, *::after { box-sizing: border-box; }

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
            --font-display:'Fraunces',serif;
            --font-body:'EB Garamond',serif;
        }

        body {
            font-family: var(--font-body);
            background: var(--craie);
            background-image: var(--verriere);
            background-attachment: fixed;
            color: var(--sepia);
            overflow-x: hidden;
            cursor: none;
            margin: 0; padding: 0;
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

        h1, h2, h3, h4 { margin: 0; }
        p { margin: 0; }
        a { text-decoration: none; }
        button { cursor: none; border: none; background: none; font-family: var(--font-body); }

        /* ─── CURSEUR : FEUILLE D'OR + LAURIER ─── */
        .cursor {
            position: fixed; width: 9px; height: 9px;
            background: var(--or); border-radius: 1px;
            pointer-events: none; z-index: 9999;
            transform: translate(-50%, -50%) rotate(45deg);
            transition: width .2s, height .2s, background .2s;
        }
        .cursor-ring {
            position: fixed; width: 36px; height: 36px;
            border: 1px solid var(--filet-or); border-radius: 50%;
            pointer-events: none; z-index: 9998;
            transform: translate(-50%, -50%);
            transition: width .3s, height .3s, border-color .3s;
        }
        .cursor--hover { background: var(--grenat); width: 14px; height: 14px; }
        .cursor-ring--hover { width: 56px; height: 56px; border-color: rgba(181,145,47,.6); }

        /* ─── HEADER / VITRINE ─── */
        .dash-header {
            position: fixed; top: 0; left: 0; right: 0; z-index: 200;
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.6rem 2.5rem;
            transition: padding .4s, background .4s, border-color .4s;
        }
        .dash-header.scrolled {
            padding: .9rem 2.5rem;
            background: rgba(251,246,236,.82);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--filet-or);
        }
        .dash-logo {
            display: inline-flex; align-items: center; gap: .55rem;
            font-family: var(--font-display); font-weight: 600;
            font-size: 1.35rem; letter-spacing: 0.16em;
            color: var(--sepia); text-decoration: none; transition: opacity .2s;
        }
        .dash-logo:hover { opacity: .6; }
        .dash-logo .lys { width: 16px; height: 20px; color: var(--or); }

        .dash-logout {
            display: inline-flex; align-items: center; gap: .4rem;
            font-size: .9rem; font-weight: 600; letter-spacing: 0.04em;
            color: var(--sepia-doux);
            padding: .55rem 1.1rem;
            border: 1px solid var(--filet); border-radius: var(--r-medaillon);
            transition: all .25s; cursor: none;
        }
        .dash-logout:hover { color: var(--velin); background: var(--grenat); border-color: var(--grenat); }

        /* ─── PORTRAIT / IDENTITÉ ─── */
        .dash-profile {
            padding: 10rem 2.5rem 4.5rem; max-width: 1400px; margin: 0 auto;
            display: grid; grid-template-columns: 150px 1fr; gap: 3.5rem; align-items: start;
            border-bottom: 1px solid var(--filet-or);
        }
        .dash-avatar-wrap { padding-top: .5rem; }
        .profile-avatar {
            width: 130px; height: 130px; border-radius: 50%; object-fit: cover; display: block;
            border: 3px solid var(--velin);
            box-shadow: 0 0 0 2px var(--or), 0 14px 34px var(--ombre-cadre);
            transition: box-shadow .3s, filter .3s;
        }
        .profile-avatar:hover { box-shadow: 0 0 0 2px var(--or-clair), 0 16px 40px var(--ombre-cadre); filter: brightness(1.04); }

        .dash-profile-right { display: flex; flex-direction: column; gap: 1.75rem; }
        .dash-name {
            font-family: var(--font-display); font-weight: 600;
            font-size: clamp(3rem, 7vw, 6rem); line-height: 0.9; color: var(--sepia); letter-spacing: -0.01em;
        }
        .dash-pseudo-wrap { display: flex; align-items: center; gap: .75rem; margin-top: .6rem; }
        /* JS queries .pseudo-display — plaque nominative */
        .pseudo-display {
            font-family: var(--font-body); font-size: .85rem; font-weight: 600; letter-spacing: 0.08em;
            color: var(--or-ombre); background: var(--velin);
            border: 1px solid var(--or); padding: .3rem .8rem; border-radius: var(--r-medaillon);
        }

        /* ─── BIO / CARTEL MURAL ─── */
        .dash-bio { max-width: 600px; }
        .dash-bio-top { display: flex; align-items: center; gap: .75rem; margin-bottom: .6rem; }
        .dash-bio-label {
            font-size: .72rem; letter-spacing: 0.22em; text-transform: uppercase; color: var(--or-ombre);
        }
        /* JS queries #editBioBtn */
        .btn-edit {
            display: inline-flex; align-items: center; gap: .25rem;
            font-size: .8rem; font-weight: 600; letter-spacing: 0.04em; color: var(--sepia-doux);
            padding: .25rem .7rem; border: 1px solid var(--filet); border-radius: var(--r-medaillon);
            transition: all .2s; cursor: none; background: none;
        }
        .btn-edit:hover { color: var(--or-ombre); border-color: var(--filet-or); }
        .btn-edit svg { width: 12px; height: 12px; }
        /* JS queries #bioContent */
        .bio-content p { font-size: 1.1rem; line-height: 1.75; color: var(--sepia-doux); margin: 0; }
        .dash-bio-empty { font-style: italic; opacity: .55; }

        .bio-textarea {
            width: 100%; min-height: 90px; padding: .9rem 1rem;
            background: var(--velin); border: 1px solid var(--filet); border-radius: var(--r-sm);
            color: var(--sepia); font-family: var(--font-body); font-size: 1.05rem; line-height: 1.6;
            resize: vertical; transition: border-color .2s, box-shadow .2s; display: block; margin-bottom: .75rem;
        }
        .bio-textarea:focus { outline: none; border-color: var(--or); box-shadow: 0 0 0 3px var(--halo); }
        .bio-textarea::placeholder { color: var(--muted); }
        .bio-actions { display: flex; gap: .75rem; }

        /* ─── BANDEAU DE RÉGIE / VISIBILITÉ ─── */
        .dash-settings { border-bottom: 1px solid var(--filet-or); border-top: 1px solid var(--filet-or); background: var(--stuc); }
        .dash-settings-inner { max-width: 1400px; margin: 0 auto; padding: 1.25rem 2.5rem; }
        .dash-settings-row { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
        .dash-settings-left { display: flex; align-items: center; gap: 1.5rem; }
        .dash-settings-title { font-size: .72rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--or-ombre); }
        /* JS queries #shareOwnProfileBtn */
        .dash-share-btn {
            font-size: .8rem; font-weight: 600; letter-spacing: 0.04em; color: var(--or-ombre);
            padding: .3rem .8rem; border: 1px solid var(--filet-or); border-radius: var(--r-medaillon);
            background: var(--velin); transition: all .2s; cursor: none;
        }
        .dash-share-btn:hover { background: var(--craie); border-color: var(--or); box-shadow: 0 0 0 3px var(--halo); }
        .dash-visibility-right { display: flex; align-items: center; gap: 1rem; }
        /* JS queries .switch-label, .switch-text */
        .switch-label { display: flex; align-items: center; gap: .4rem; font-size: .85rem; font-weight: 600; color: var(--sepia-doux); }
        .switch-label svg { width: 14px; height: 14px; color: var(--or-ombre); }
        /* Interrupteur médaillon — JS queries #visibilityToggle */
        .switch { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: none; inset: 0;
            background: var(--velin); border: 1px solid var(--filet-or); border-radius: var(--r-medaillon); transition: .3s;
        }
        .slider::before {
            content: ''; position: absolute; width: 18px; height: 18px; left: 3px; bottom: 3px;
            background: var(--marbre); border-radius: 50%; transition: .3s; box-shadow: 0 1px 3px var(--ombre-cadre);
        }
        input:checked + .slider { background: var(--velours); border-color: var(--grenat-fonce); }
        input:checked + .slider::before { transform: translateX(22px); background: var(--dorure); }

        /* ─── SALLES D'EXPOSITION ─── */
        .dash-main { max-width: 1400px; margin: 0 auto; padding: 0 2.5rem 6rem; }
        .dash-category { padding: 3.5rem 0; border-bottom: 1px solid var(--filet); }
        .dash-category:last-child { border-bottom: none; }
        .dash-category-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; gap: 1rem; }
        .dash-category-title {
            font-family: var(--font-display); font-weight: 600;
            font-size: clamp(2.25rem, 5vw, 4rem); line-height: 0.9; color: var(--sepia); letter-spacing: -0.01em;
            position: relative; padding-top: 1.4rem;
        }
        /* Coquille rocaille au-dessus du titre de salle */
        .dash-category-title::before {
            content: '\10086'; position: absolute; top: 0; left: 0;
            font-size: 1rem; color: var(--or);
        }
        /* JS queries [id^="add"][id$="Btn"] */
        .dash-add-btn {
            display: inline-flex; align-items: center; gap: .5rem;
            font-size: .85rem; font-weight: 600; letter-spacing: 0.04em; color: var(--sepia);
            padding: .6rem 1.1rem .6rem 1.3rem;
            border: 1px solid var(--filet-or); border-radius: var(--r-medaillon);
            background: var(--velin); transition: all .25s; cursor: none; white-space: nowrap; flex-shrink: 0;
        }
        .dash-add-btn:hover { background: var(--grenat); border-color: var(--grenat); color: var(--velin); }
        .dash-add-circle {
            width: 22px; height: 22px; background: var(--dorure); color: var(--velin);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1rem; line-height: 1; transition: background .25s;
        }

        /* ─── CIMAISE (SCROLL) ─── */
        .albums-horizontal-scroll {
            display: flex; gap: 1.25rem; overflow-x: auto; overflow-y: hidden; padding-bottom: .75rem;
            scrollbar-width: thin; scrollbar-color: var(--or) transparent;
        }
        .albums-horizontal-scroll::-webkit-scrollbar { height: 3px; }
        .albums-horizontal-scroll::-webkit-scrollbar-thumb { background: var(--dorure); border-radius: 99px; }

        /* ─── ŒUVRES ENCADRÉES ─── */
        .album-card-horizontal { flex: 0 0 180px; display: flex; flex-direction: column; gap: .6rem; }
        /* Cadre doré à gorge + biseau */
        .album-cover {
            position: relative; width: 180px; height: 180px; border-radius: var(--r-cartel); overflow: hidden;
            background: var(--stuc); flex-shrink: 0;
            border: 5px solid var(--velin);
            box-shadow: 0 0 0 2px var(--or), 0 14px 30px var(--ombre-cadre), inset 0 2px 6px rgba(0,0,0,.18);
            transition: box-shadow .35s, filter .35s;
        }
        .album-card-horizontal:hover .album-cover { box-shadow: 0 0 0 2px var(--or-clair), 0 18px 40px var(--ombre-cadre); filter: brightness(1.05) saturate(1.04); }
        .album-cover img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .4s cubic-bezier(0.4,0,0.2,1); }

        /* Cartel posé en bas du cadre */
        .album-cover-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(43,33,24,.88) 0%, rgba(43,33,24,.35) 48%, transparent 100%);
            display: flex; flex-direction: column; justify-content: flex-end; padding: .75rem;
            opacity: 0; transition: opacity .3s;
        }
        .album-card-horizontal:hover .album-cover-overlay { opacity: 1; }
        .album-cover-overlay .album-title {
            font-family: var(--font-body); font-size: .9rem; font-weight: 600; color: var(--velin); margin: 0 0 .15rem; line-height: 1.3;
            display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .album-cover-overlay .album-artist { font-style: italic; font-size: .8rem; color: rgba(251,246,236,.7); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* JS references .remove-album-btn — sceau de cire */
        .remove-album-btn {
            position: absolute; top: .5rem; right: .5rem; width: 26px; height: 26px;
            background: rgba(43,33,24,.7); border: 1px solid var(--filet-or); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; opacity: 0;
            transition: opacity .2s, background .2s, border-color .2s; cursor: none;
        }
        .remove-album-btn svg { width: 12px; height: 12px; color: var(--velin); }
        .album-card-horizontal:hover .remove-album-btn { opacity: 1; }
        .remove-album-btn:hover { background: var(--grenat); border-color: var(--grenat); }

        .album-info-horizontal { display: none; }
        .album-title { font-family: var(--font-body); font-size: .9rem; font-weight: 600; color: var(--sepia); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .album-artist { font-style: italic; font-size: .8rem; color: var(--sepia-doux); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ─── CIMAISE VIDE ─── */
        .no-albums { padding: 3rem 0; display: flex; flex-direction: column; align-items: center; gap: .75rem; color: var(--muted); }
        .no-albums svg, .no-albums i { width: 2rem; height: 2rem; color: var(--filet-or); }
        .no-albums p { font-size: .9rem; letter-spacing: 0.06em; text-transform: uppercase; }

        /* ─── SOCLE (FOOTER) ─── */
        .dash-footer {
            border-top: 1px solid var(--filet-or); background: var(--marbre);
            padding: 1.75rem 2.5rem; display: flex; justify-content: space-between;
            font-size: .8rem; letter-spacing: 0.04em; color: var(--sepia-doux); max-width: 1400px; margin: 0 auto;
        }

        /* ─── BOUTONS PARTAGÉS ─── */
        .btn {
            display: inline-flex; align-items: center; gap: .4rem; padding: .65rem 1.3rem;
            font-family: var(--font-body); font-size: .9rem; font-weight: 600; letter-spacing: 0.02em;
            border-radius: var(--r-medaillon); transition: all .25s; cursor: none; white-space: nowrap; border: 1px solid transparent;
        }
        .btn svg { width: 14px; height: 14px; }
        .btn-primary { background: var(--velours); color: var(--velin); border-color: var(--grenat-fonce); }
        .btn-primary:hover { filter: brightness(1.08); transform: scale(1.02); }
        .btn-secondary { background: var(--velin); color: var(--sepia-doux); border: 1px solid var(--filet); }
        .btn-secondary:hover { border-color: var(--filet-or); color: var(--sepia); }

        /* ─── VITRINE / MODALES ─── */
        .modal {
            position: fixed; inset: 0; background: rgba(43,33,24,.55); backdrop-filter: blur(10px);
            z-index: 1000; display: none; align-items: center; justify-content: center; padding: 1.5rem;
        }
        .modal[style*="flex"] { display: flex !important; }
        .modal-content, .add-album-content {
            position: relative; background: var(--velin); border: 1px solid var(--or);
            border-radius: var(--r-panneau); padding: 2.5rem; max-width: 460px; width: 100%;
            box-shadow: 0 30px 80px rgba(43,33,24,.45);
        }
        /* Volutes d'angle (cartouche) */
        .modal-content::before, .modal-content::after,
        .add-album-content::before, .add-album-content::after {
            content: ''; position: absolute; top: 12px; width: 34px; height: 34px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Cpath d='M38 2C18 2 2 18 2 38' fill='none' stroke='%23B5912F' stroke-width='1.4'/%3E%3Cpath d='M2 38c0-8 6-14 14-14 6 0 10 4 10 9' fill='none' stroke='%23B5912F' stroke-width='1.4'/%3E%3Ccircle cx='29' cy='11' r='2' fill='%23B5912F'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-size: contain; opacity: .8; pointer-events: none;
        }
        .modal-content::before, .add-album-content::before { left: 12px; }
        .modal-content::after, .add-album-content::after { right: 12px; transform: scaleX(-1); }

        .modal-header h3 { font-family: var(--font-display); font-weight: 600; font-size: 2rem; color: var(--sepia); letter-spacing: 0.01em; margin-bottom: .4rem; text-align: center; }
        .modal-header p { font-size: .95rem; color: var(--sepia-doux); margin-bottom: 1.75rem; text-align: center; }
        .input-group { display: flex; flex-direction: column; gap: .5rem; margin-bottom: 1.25rem; }
        .input-group label { font-size: .72rem; letter-spacing: 0.15em; text-transform: uppercase; color: var(--or-ombre); }
        .pseudo-input-container { position: relative; display: flex; align-items: center; }
        .pseudo-prefix { position: absolute; left: 1rem; color: var(--or); font-family: var(--font-display); font-weight: 700; z-index: 1; }
        #pseudoInput {
            width: 100%; padding: .75rem 1rem .75rem 2rem; background: var(--craie);
            border: 1px solid var(--filet); border-radius: var(--r-sm); color: var(--sepia);
            font-family: var(--font-body); font-size: 1.05rem; transition: border-color .2s, box-shadow .2s;
        }
        #pseudoInput:focus { outline: none; border-color: var(--or); box-shadow: 0 0 0 3px var(--halo); }
        #pseudoInput::placeholder { color: var(--muted); }
        .feedback { font-size: .82rem; min-height: 18px; display: flex; align-items: center; gap: .3rem; }
        .feedback.available { color: var(--patine); }
        .feedback.unavailable { color: var(--grenat); }
        .feedback.checking { color: var(--sepia-doux); }
        .modal-actions { display: flex; gap: .75rem; margin-top: 1.5rem; justify-content: center; }

        /* ─── VITRINE AJOUT D'ALBUM (créée par JS) ─── */
        .add-album-modal {
            position: fixed; inset: 0; background: rgba(43,33,24,.55); backdrop-filter: blur(10px);
            z-index: 1000; display: none; align-items: center; justify-content: center; padding: 1.5rem;
        }
        .add-album-modal.show { display: flex; }
        .add-album-content { max-width: 520px; }
        .add-album-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
        .add-album-header h3 { font-family: var(--font-display); font-weight: 600; font-size: 2rem; color: var(--sepia); letter-spacing: 0.01em; }
        .close-btn { color: var(--sepia-doux); font-size: 1.25rem; transition: color .2s, transform .3s; cursor: none; }
        .close-btn:hover { color: var(--grenat); transform: rotate(90deg); }

        .album-input-group { position: relative; margin-bottom: 1.25rem; }
        .album-input {
            width: 100%; padding: .75rem 1rem; background: var(--craie);
            border: 1px solid var(--filet); border-radius: var(--r-sm); color: var(--sepia) !important;
            font-family: var(--font-body); font-size: 1.05rem; height: auto; transition: border-color .2s, box-shadow .2s;
        }
        .album-input:focus { outline: none; border-color: var(--or); box-shadow: 0 0 0 3px var(--halo); }
        .album-input::placeholder { color: var(--muted); }

        /* Réserve du musée (suggestions) */
        .album-suggestions {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 30;
            background: var(--velin); border: 1px solid var(--filet-or); border-radius: var(--r-sm);
            max-height: 300px; overflow: auto; box-shadow: 0 16px 40px var(--ombre-cadre);
        }
        .album-suggestion-item { display: flex; align-items: center; gap: .75rem; padding: .6rem .85rem; cursor: pointer; transition: background .15s; }
        .album-suggestion-item:hover { background: var(--craie); }
        .album-suggestion-cover {
            width: 42px; height: 42px; border-radius: var(--r-cartel); overflow: hidden; background: var(--stuc);
            flex-shrink: 0; border: 2px solid var(--velin); box-shadow: 0 0 0 1px var(--or);
        }
        .album-suggestion-cover img { width: 100%; height: 100%; object-fit: cover; }
        .album-suggestion-cover i { width: 18px; height: 18px; color: var(--or); margin: 12px; }
        .album-suggestion-info { flex: 1; min-width: 0; }
        .album-suggestion-title { font-size: .9rem; font-weight: 600; color: var(--sepia); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .album-suggestion-artist { font-style: italic; font-size: .8rem; color: var(--sepia-doux); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .album-suggestion-select { color: var(--or-ombre); display: flex; align-items: center; cursor: none; }
        .album-suggestion-select svg { width: 14px; height: 14px; }
        .album-modal-actions { display: flex; gap: .75rem; }

        /* ─── CARTELS VOLANTS (NOTIFICATIONS) ─── */
        .notification {
            position: fixed; top: 1.25rem; right: 1.25rem; background: var(--velin);
            border: 1px solid var(--filet-or); border-radius: var(--r-sm); padding: .85rem 1.25rem;
            color: var(--sepia); font-size: .9rem; font-weight: 500; z-index: 9000;
            transform: translateX(120%); opacity: 0;
            transition: transform .35s cubic-bezier(0.16,1,0.3,1), opacity .35s;
            display: flex; align-items: center; gap: .75rem; max-width: 320px; box-shadow: 0 14px 36px var(--ombre-cadre);
        }
        .notification.show { transform: translateX(0); opacity: 1; }
        .notification-success { border-left: 3px solid var(--patine); }
        .notification-error { border-left: 3px solid var(--grenat); }
        .notification-info { border-left: 3px solid var(--or); }
        .notification-close { background: none; border: none; color: var(--sepia-doux); cursor: none; margin-left: auto; display: flex; align-items: center; }
        .notification-close svg { width: 14px; height: 14px; }
        .notification-close:hover { color: var(--grenat); }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 768px) {
            body { cursor: auto; }
            .cursor, .cursor-ring { display: none; }
            button { cursor: pointer; }
            a { cursor: pointer; }
            .dash-header { padding: 1.25rem; }
            .dash-header.scrolled { padding: .9rem 1.25rem; }
            .dash-profile { grid-template-columns: 1fr; padding: 8rem 1.25rem 3rem; gap: 1.75rem; }
            .dash-avatar-wrap { display: flex; align-items: center; gap: 1.25rem; }
            .profile-avatar { width: 84px; height: 84px; }
            .dash-settings-inner { padding: 1rem 1.25rem; }
            .dash-settings-row { flex-direction: column; align-items: flex-start; gap: .75rem; }
            .dash-settings-left { flex-wrap: wrap; }
            .dash-main { padding: 0 1.25rem 4rem; }
            .dash-category { padding: 2.5rem 0; }
            .dash-category-header { align-items: center; }
            .dash-category-title { font-size: clamp(2rem, 8vw, 3rem); }
            .album-card-horizontal { flex: 0 0 140px; }
            .album-cover { width: 140px; height: 140px; }
            .album-info-horizontal { display: block; }
            .dash-footer { flex-direction: column; gap: .4rem; text-align: center; padding: 1.5rem 1.25rem; }
        }
        @media (max-width: 480px) { .dash-name { font-size: 2.75rem; } }

        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <!-- Curseur feuille d'or -->
    <div class="cursor" id="cursor"></div>
    <div class="cursor-ring" id="cursorRing"></div>

    <!-- Éléments décoratifs — cachés mais conservés pour la compatibilité JS -->
    <div class="music-elements" style="display:none;" aria-hidden="true">
        <i data-lucide="music" class="music-note"></i>
        <i data-lucide="music-2" class="music-note"></i>
        <i data-lucide="music-3" class="music-note"></i>
        <i data-lucide="music-4" class="music-note"></i>
    </div>

    <!-- Header -->
    <header class="dash-header" id="header">
        <a href="/" class="dash-logo">
            <svg class="lys" viewBox="0 0 24 30" fill="currentColor" aria-hidden="true">
                <path d="M12 0c1.6 2.3 1.6 4.7 0 7-1.6-2.3-1.6-4.7 0-7zM12 7c2.4 1.2 3.6 3.2 3.4 6.2 2.2-1.4 4.4-1 6.6 1.2-3 .4-4.6 2-4.8 4.8-1.6-1.8-3.4-2.4-5.2-1.8v8.2c2-.4 3.8-.2 5.4 1.4H8.6c1.6-1.6 3.4-1.8 5.4-1.4v-8.2c-1.8-.6-3.6 0-5.2 1.8-.2-2.8-1.8-4.4-4.8-4.8 2.2-2.2 4.4-2.6 6.6-1.2C8.4 10.2 9.6 8.2 12 7z"/>
            </svg>
            UNIVERSON
        </a>
        <button id="logoutBtn" class="dash-logout">
            <i data-lucide="log-out" style="width:13px;height:13px;"></i>
            Déconnexion
        </button>
    </header>

    <!-- Portrait / Identité -->
    <section class="dash-profile">
        <div class="dash-avatar-wrap">
            <img src="<?= htmlspecialchars($user['picture'] ?? '') ?>" alt="Photo de profil" class="profile-avatar">
        </div>
        <div class="dash-profile-right">

            <div>
                <h1 class="dash-name"><?= htmlspecialchars($user['firstName'] ?? '') ?> <?= htmlspecialchars($user['lastName'] ?? '') ?></h1>
                <?php if (isset($user['pseudo']) && !empty($user['pseudo'])): ?>
                    <div class="dash-pseudo-wrap">
                        <span class="pseudo-display">@<?= htmlspecialchars($user['pseudo']) ?></span>
                    </div>
                <?php else: ?>
                    <div class="dash-pseudo-wrap">
                        <span class="pseudo-display" style="display:none;"></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bio section — JS queries: #editBioBtn, #bioContent, #bioEditForm, #bioTextarea, #saveBioBtn, #cancelBioBtn -->
            <div class="dash-bio">
                <div class="dash-bio-top">
                    <span class="dash-bio-label">Bio</span>
                    <button class="btn-edit" id="editBioBtn" title="Modifier la bio">
                        <i data-lucide="edit-3"></i>
                        Modifier
                    </button>
                </div>
                <div class="bio-content" id="bioContent">
                    <?php if (isset($user['bio']) && !empty($user['bio'])): ?>
                        <p><?= htmlspecialchars($user['bio']) ?></p>
                    <?php else: ?>
                        <p class="dash-bio-empty">Ajoutez une bio pour partager vos goûts musicaux...</p>
                    <?php endif; ?>
                </div>
                <div class="bio-edit-form" id="bioEditForm" style="display: none;">
                    <textarea id="bioTextarea" class="bio-textarea" placeholder="Parlez-nous de vos goûts musicaux..."><?= isset($user['bio']) ? htmlspecialchars($user['bio']) : '' ?></textarea>
                    <div class="bio-actions">
                        <button class="btn btn-primary" id="saveBioBtn">
                            <i data-lucide="save"></i>
                            <span>Sauvegarder</span>
                        </button>
                        <button class="btn btn-secondary" id="cancelBioBtn">
                            <i data-lucide="x"></i>
                            <span>Annuler</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Bandeau de régie / visibilité — JS queries: #visibilityToggle, .switch-label, .switch-text, #shareOwnProfileBtn, .pseudo-display -->
    <div class="dash-settings">
        <div class="dash-settings-inner">
            <div class="dash-settings-row">
                <div class="dash-settings-left">
                    <span class="dash-settings-title">Visibilité du profil</span>
                    <?php if (isset($user['pseudo']) && !empty($user['pseudo'])): ?>
                        <button id="shareOwnProfileBtn" class="dash-share-btn"
                                data-share-url="<?= htmlspecialchars($site_url) ?>/@<?= htmlspecialchars($user['pseudo']) ?>">
                            Copier le lien ↗
                        </button>
                    <?php else: ?>
                        <button id="shareOwnProfileBtn" class="dash-share-btn" title="Choisissez un pseudo pour partager">
                            Copier le lien ↗
                        </button>
                    <?php endif; ?>
                </div>
                <div class="dash-visibility-right">
                    <span class="switch-label">
                        <i data-lucide="<?= ($user['profile_visibility'] === 'public') ? 'globe' : 'lock' ?>"></i>
                        <span class="switch-text"><?= ($user['profile_visibility'] === 'public') ? 'Public' : 'Privé' ?></span>
                    </span>
                    <label class="switch">
                        <input type="checkbox" id="visibilityToggle" <?= ($user['profile_visibility'] === 'public') ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Vitrine pseudo — JS queries: #pseudoModal, #pseudoInput, #pseudoFeedback, #savePseudoBtn, #cancelPseudoBtn -->
    <div id="pseudoModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Choisir un pseudo</h3>
                <p>Pour rendre votre profil public, choisissez un pseudo unique.</p>
            </div>
            <div class="pseudo-form">
                <div class="input-group">
                    <label for="pseudoInput">Pseudo</label>
                    <div class="pseudo-input-container">
                        <span class="pseudo-prefix">@</span>
                        <input type="text" id="pseudoInput" placeholder="votre_pseudo" maxlength="45" minlength="3">
                    </div>
                    <div id="pseudoFeedback" class="feedback"></div>
                </div>
                <div class="modal-actions">
                    <button id="savePseudoBtn" class="btn btn-primary" disabled>
                        <i data-lucide="save"></i>
                        <span>Enregistrer</span>
                    </button>
                    <button id="cancelPseudoBtn" class="btn btn-secondary">
                        <i data-lucide="x"></i>
                        <span>Annuler</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Salles d'exposition — JS queries [id^="add"][id$="Btn"] -->
    <main class="dash-main">
        <?php foreach ($categories as $category):
            $categoryAlbums = $categoriesAlbums[$category['name']] ?? [];
            $btnId = 'add' . ucfirst(str_replace('_', '', $category['name'])) . 'Btn';
        ?>
        <section class="dash-category">
            <div class="dash-category-header">
                <h2 class="dash-category-title"><?= htmlspecialchars(strtoupper($category['description'])) ?></h2>
                <button class="dash-add-btn" id="<?= $btnId ?>">
                    Ajouter
                    <span class="dash-add-circle">+</span>
                </button>
            </div>

            <?php if (!empty($categoryAlbums)): ?>
            <div class="albums-horizontal-scroll">
                <?php foreach ($categoryAlbums as $album): ?>
                <div class="album-card-horizontal" data-album-id="<?= $album['id'] ?>">
                    <div class="album-cover">
                        <?php if (!empty($album['image_url_100']) || !empty($album['image_url_60'])): ?>
                            <img src="<?= htmlspecialchars($album['image_url_100'] ?: $album['image_url_60']) ?>"
                                 alt="<?= htmlspecialchars($album['name']) ?>"
                                 loading="lazy"
                                 onerror="this.remove();">
                        <?php endif; ?>
                        <div class="album-cover-overlay">
                            <h4 class="album-title"><?= htmlspecialchars($album['name']) ?></h4>
                            <?php if (!empty($album['artist_name'])): ?>
                            <p class="album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                            <?php endif; ?>
                        </div>
                        <button class="remove-album-btn"
                                title="Retirer"
                                onclick="removeAlbumFromCategory(<?= $album['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                    <!-- album-info-horizontal kept for JS compatibility -->
                    <div class="album-info-horizontal">
                        <h4 class="album-title"><?= htmlspecialchars($album['name']) ?></h4>
                        <?php if (!empty($album['artist_name'])): ?>
                        <p class="album-artist"><?= htmlspecialchars($album['artist_name']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-albums">
                <i data-lucide="disc-3"></i>
                <p>Salle en cours d'accrochage</p>
            </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    </main>

    <footer class="dash-footer">
        <span>© <?= date('Y') ?> Universon</span>
        <span>Votre musée musical personnel</span>
    </footer>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="/js/app.js"></script>
    <script>
        /* Curseur */
        const cur = document.getElementById('cursor');
        const ring = document.getElementById('cursorRing');
        if (cur && ring) {
            let mx = 0, my = 0, rx = 0, ry = 0;
            document.addEventListener('mousemove', e => {
                mx = e.clientX; my = e.clientY;
                cur.style.left = mx + 'px';
                cur.style.top  = my + 'px';
            });
            (function raf() {
                rx += (mx - rx) * 0.1;
                ry += (my - ry) * 0.1;
                ring.style.left = rx + 'px';
                ring.style.top  = ry + 'px';
                requestAnimationFrame(raf);
            })();
            document.querySelectorAll('a, button').forEach(el => {
                el.addEventListener('mouseenter', () => {
                    cur.classList.add('cursor--hover');
                    ring.classList.add('cursor-ring--hover');
                });
                el.addEventListener('mouseleave', () => {
                    cur.classList.remove('cursor--hover');
                    ring.classList.remove('cursor-ring--hover');
                });
            });
        }

        /* Header collé */
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 60);
        }, { passive: true });
    </script>
</body>
</html>
