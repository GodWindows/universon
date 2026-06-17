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
    <meta name="theme-color" content="#09090A">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="../img/logo.ico">
    <title><?= htmlspecialchars($site_title) ?> — Mon Musée</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- No link to styles.css — all needed styles are in the block below -->
    <style>
        /* ─── RESET OVERRIDES ─── */
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --ink:    #09090A;
            --ink-2:  #111113;
            --ivory:  #F0EBE3;
            --copper: #C87941;
            --line:   rgba(240, 235, 227, 0.1);
            --muted:  rgba(240, 235, 227, 0.45);
            --font-display: 'Bebas Neue', sans-serif;
            --font-body:    'Space Grotesk', sans-serif;
        }

        body {
            font-family: var(--font-body);
            background: var(--ink);
            color: var(--ivory);
            overflow-x: hidden;
            cursor: none;
            margin: 0;
            padding: 0;
        }

        body::after {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
            opacity: 0.03;
            pointer-events: none;
            z-index: 9990;
        }

        h1, h2, h3 { margin: 0; }
        p { margin: 0; }
        a { text-decoration: none; }
        button { cursor: none; border: none; background: none; font-family: var(--font-body); }

        /* ─── CUSTOM CURSOR ─── */
        .cursor {
            position: fixed;
            width: 7px; height: 7px;
            background: var(--copper);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            transform: translate(-50%, -50%);
        }
        .cursor-ring {
            position: fixed;
            width: 38px; height: 38px;
            border: 1px solid rgba(200, 121, 65, 0.5);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9998;
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s, border-color 0.3s;
        }
        .cursor--hover { background: var(--ivory); width: 12px; height: 12px; }
        .cursor-ring--hover { width: 56px; height: 56px; border-color: rgba(240,235,227,0.25); }

        /* ─── HEADER ─── */
        .dash-header {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 200;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.75rem 2.5rem;
            transition: padding 0.4s, background 0.4s, border-color 0.4s;
        }
        .dash-header.scrolled {
            padding: 1rem 2.5rem;
            background: rgba(9, 9, 10, 0.9);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--line);
        }

        .dash-logo {
            font-family: var(--font-display);
            font-size: 1.3rem;
            letter-spacing: 0.2em;
            color: var(--ivory);
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .dash-logo:hover { opacity: 0.5; }

        .dash-logout {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: rgba(240,235,227,0.5);
            padding: 0.55rem 1.1rem;
            border: 1px solid rgba(240,235,227,0.15);
            border-radius: 999px;
            transition: all 0.25s;
            cursor: none;
        }
        .dash-logout:hover {
            color: var(--ivory);
            border-color: rgba(240,235,227,0.4);
            background: rgba(240,235,227,0.06);
        }

        /* ─── PROFILE SECTION ─── */
        .dash-profile {
            padding: 10rem 2.5rem 5rem;
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 3.5rem;
            align-items: start;
            border-bottom: 1px solid var(--line);
        }

        .dash-avatar-wrap { padding-top: 0.5rem; }

        .profile-avatar {
            width: 120px; height: 120px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            border: 2px solid rgba(200,121,65,0.3);
            transition: border-color 0.3s;
        }
        .profile-avatar:hover { border-color: var(--copper); }

        .dash-profile-right { display: flex; flex-direction: column; gap: 1.75rem; }

        .dash-name {
            font-family: var(--font-display);
            font-size: clamp(3rem, 7vw, 6.5rem);
            line-height: 0.85;
            color: var(--ivory);
            letter-spacing: -0.01em;
        }

        .dash-pseudo-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        /* JS queries .pseudo-display — keep class */
        .pseudo-display {
            font-family: var(--font-body);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            color: var(--copper);
            background: rgba(200,121,65,0.1);
            border: 1px solid rgba(200,121,65,0.3);
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
        }

        /* ─── BIO ─── */
        .dash-bio { max-width: 600px; }

        .dash-bio-top {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.6rem;
        }
        .dash-bio-label {
            font-size: 0.65rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: rgba(240,235,227,0.3);
        }

        /* JS queries #editBioBtn */
        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: var(--muted);
            padding: 0.25rem 0.6rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            transition: all 0.2s;
            cursor: none;
            background: none;
        }
        .btn-edit:hover { color: var(--ivory); border-color: rgba(240,235,227,0.3); }
        .btn-edit svg { width: 12px; height: 12px; }

        /* JS queries #bioContent */
        .bio-content p {
            font-size: 1.05rem;
            line-height: 1.7;
            color: rgba(240,235,227,0.65);
            margin: 0;
        }
        .dash-bio-empty { font-style: italic; opacity: 0.4; }

        /* JS queries #bioEditForm, #bioTextarea, #saveBioBtn, #cancelBioBtn */
        .bio-textarea {
            width: 100%;
            min-height: 90px;
            padding: 0.9rem 1rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: var(--ivory);
            font-family: var(--font-body);
            font-size: 1rem;
            line-height: 1.6;
            resize: vertical;
            transition: border-color 0.2s;
            display: block;
            margin-bottom: 0.75rem;
        }
        .bio-textarea:focus { outline: none; border-color: var(--copper); }
        .bio-textarea::placeholder { color: rgba(240,235,227,0.3); }

        .bio-actions {
            display: flex;
            gap: 0.75rem;
        }

        /* ─── VISIBILITY / SETTINGS ─── */
        .dash-settings {
            border-bottom: 1px solid var(--line);
            background: var(--ink-2);
        }
        .dash-settings-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.25rem 2.5rem;
        }
        .dash-settings-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
        }
        .dash-settings-left { display: flex; align-items: center; gap: 1.5rem; }

        .dash-settings-title {
            font-size: 0.72rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
        }

        /* JS queries #shareOwnProfileBtn */
        .dash-share-btn {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: var(--copper);
            padding: 0.3rem 0.75rem;
            border: 1px solid rgba(200,121,65,0.3);
            border-radius: 999px;
            transition: all 0.2s;
            cursor: none;
        }
        .dash-share-btn:hover { background: rgba(200,121,65,0.1); border-color: var(--copper); }

        .dash-visibility-right { display: flex; align-items: center; gap: 1rem; }

        /* JS queries .switch-label and .switch-text — keep classes */
        .switch-label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
        }
        .switch-label svg { width: 14px; height: 14px; }

        /* Toggle switch — JS queries #visibilityToggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px; height: 26px;
            flex-shrink: 0;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: none;
            inset: 0;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 999px;
            transition: 0.3s;
        }
        .slider::before {
            content: '';
            position: absolute;
            width: 18px; height: 18px;
            left: 3px; bottom: 3px;
            background: rgba(240,235,227,0.4);
            border-radius: 50%;
            transition: 0.3s;
        }
        input:checked + .slider { background: var(--copper); border-color: var(--copper); }
        input:checked + .slider::before { transform: translateX(22px); background: var(--ivory); }

        /* ─── ALBUMS MAIN ─── */
        .dash-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2.5rem 6rem;
        }

        /* ─── CATEGORY SECTION ─── */
        .dash-category {
            padding: 3.5rem 0;
            border-bottom: 1px solid var(--line);
        }
        .dash-category:last-child { border-bottom: none; }

        .dash-category-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2rem;
            gap: 1rem;
        }

        .dash-category-title {
            font-family: var(--font-display);
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            line-height: 0.85;
            color: var(--ivory);
            letter-spacing: -0.01em;
        }

        /* JS queries [id^="add"][id$="Btn"] */
        .dash-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: var(--ivory);
            padding: 0.6rem 1.1rem 0.6rem 1.3rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            transition: all 0.25s;
            cursor: none;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .dash-add-btn:hover {
            background: var(--copper);
            border-color: var(--copper);
            color: var(--ivory);
        }
        .dash-add-circle {
            width: 22px; height: 22px;
            background: rgba(240,235,227,0.12);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            line-height: 1;
            transition: background 0.25s;
        }
        .dash-add-btn:hover .dash-add-circle { background: rgba(9,9,10,0.2); }

        /* ─── ALBUM SCROLL ─── */
        /* JS may reference .albums-horizontal-scroll — keep class */
        .albums-horizontal-scroll {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 0.75rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(200,121,65,0.3) transparent;
        }
        .albums-horizontal-scroll::-webkit-scrollbar { height: 3px; }
        .albums-horizontal-scroll::-webkit-scrollbar-thumb { background: rgba(200,121,65,0.3); border-radius: 99px; }

        /* ─── ALBUM CARDS ─── */
        /* JS references .album-card-horizontal for structure */
        .album-card-horizontal {
            flex: 0 0 180px;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        /* JS references .album-cover */
        .album-cover {
            position: relative;
            width: 180px; height: 180px;
            border-radius: 4px;
            overflow: hidden;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }
        .album-cover img {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s cubic-bezier(0.4,0,0.2,1);
        }
        .album-card-horizontal:hover .album-cover img { transform: scale(1.04); }

        .album-cover-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(9,9,10,0.92) 0%, rgba(9,9,10,0.4) 50%, transparent 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 0.75rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .album-card-horizontal:hover .album-cover-overlay { opacity: 1; }

        .album-cover-overlay .album-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--ivory);
            margin: 0 0 0.15rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .album-cover-overlay .album-artist {
            font-size: 0.72rem;
            color: rgba(240,235,227,0.6);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* JS references .remove-album-btn */
        .remove-album-btn {
            position: absolute;
            top: 0.5rem; right: 0.5rem;
            width: 26px; height: 26px;
            background: rgba(9,9,10,0.7);
            border: 1px solid rgba(240,235,227,0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0;
            transition: opacity 0.2s, background 0.2s;
            cursor: none;
        }
        .remove-album-btn svg { width: 12px; height: 12px; color: var(--ivory); }
        .album-card-horizontal:hover .remove-album-btn { opacity: 1; }
        .remove-album-btn:hover { background: #ef4444; border-color: #ef4444; }

        /* Below-card info — visible on mobile / fallback */
        .album-info-horizontal { display: none; }

        /* JS references .album-title, .album-artist (for renderSuggestions) */
        .album-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--ivory);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .album-artist {
            font-size: 0.72rem;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ─── NO ALBUMS ─── */
        /* JS references .no-albums */
        .no-albums {
            padding: 3rem 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            color: rgba(240,235,227,0.2);
        }
        .no-albums svg, .no-albums i { width: 2rem; height: 2rem; color: rgba(200,121,65,0.25); }
        .no-albums p { font-size: 0.85rem; letter-spacing: 0.06em; text-transform: uppercase; }

        /* ─── FOOTER ─── */
        .dash-footer {
            border-top: 1px solid var(--line);
            padding: 1.75rem 2.5rem;
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            color: rgba(240,235,227,0.2);
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ─── SHARED BUTTONS (used by bio form) ─── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.6rem 1.2rem;
            font-family: var(--font-body);
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            border-radius: 999px;
            transition: all 0.25s;
            cursor: none;
            white-space: nowrap;
        }
        .btn svg { width: 14px; height: 14px; }
        .btn-primary { background: var(--copper); color: var(--ivory); }
        .btn-primary:hover { background: #d88947; transform: scale(1.02); }
        .btn-secondary { background: rgba(255,255,255,0.08); color: rgba(240,235,227,0.7); border: 1px solid var(--line); }
        .btn-secondary:hover { background: rgba(255,255,255,0.14); color: var(--ivory); }

        /* ─── MODAL OVERRIDES ─── */
        /* The pseudo modal and add-album modals use classes from styles.css — just tweak visuals */
        .modal {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(12px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .modal[style*="flex"] { display: flex !important; }

        .modal-content {
            background: #111113;
            border: 1px solid rgba(200,121,65,0.2);
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 460px;
            width: 100%;
        }
        .modal-header h3 { font-family: var(--font-display); font-size: 2rem; color: var(--ivory); letter-spacing: 0.05em; margin-bottom: 0.4rem; }
        .modal-header p { font-size: 0.88rem; color: var(--muted); margin-bottom: 1.75rem; }

        .input-group { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.25rem; }
        .input-group label { font-size: 0.72rem; letter-spacing: 0.15em; text-transform: uppercase; color: var(--muted); }
        .pseudo-input-container { position: relative; display: flex; align-items: center; }
        .pseudo-prefix { position: absolute; left: 1rem; color: var(--copper); font-weight: 700; z-index: 1; }

        #pseudoInput {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            color: var(--ivory);
            font-family: var(--font-body);
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        #pseudoInput:focus { outline: none; border-color: var(--copper); }
        #pseudoInput::placeholder { color: rgba(240,235,227,0.25); }

        .feedback { font-size: 0.78rem; min-height: 18px; display: flex; align-items: center; gap: 0.3rem; }
        .feedback.available { color: #10b981; }
        .feedback.unavailable { color: #ef4444; }
        .feedback.checking { color: rgba(240,235,227,0.5); }

        .modal-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; }

        /* ─── ADD ALBUM MODAL (created by JS) ─── */
        .add-album-modal {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(12px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .add-album-modal.show { display: flex; }
        .add-album-content {
            background: #111113;
            border: 1px solid rgba(200,121,65,0.2);
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 520px;
            width: 100%;
        }
        .add-album-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        .add-album-header h3 { font-family: var(--font-display); font-size: 2rem; color: var(--ivory); letter-spacing: 0.05em; }
        .close-btn { color: var(--muted); font-size: 1.25rem; transition: color 0.2s; cursor: none; }
        .close-btn:hover { color: var(--ivory); }

        .album-input-group { position: relative; margin-bottom: 1.25rem; }
        .album-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            color: var(--ivory) !important;
            font-family: var(--font-body);
            font-size: 1rem;
            height: auto;
            transition: border-color 0.2s;
        }
        .album-input:focus { outline: none; border-color: var(--copper); }
        .album-input::placeholder { color: rgba(240,235,227,0.3); }

        .album-suggestions {
            position: absolute;
            top: calc(100% + 6px);
            left: 0; right: 0;
            z-index: 30;
            background: #1a1a1c;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            max-height: 300px;
            overflow: auto;
        }
        .album-suggestion-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.85rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .album-suggestion-item:hover { background: rgba(255,255,255,0.05); }
        .album-suggestion-cover { width: 40px; height: 40px; border-radius: 4px; overflow: hidden; background: rgba(255,255,255,0.06); flex-shrink: 0; }
        .album-suggestion-cover img { width: 100%; height: 100%; object-fit: cover; }
        .album-suggestion-info { flex: 1; min-width: 0; }
        .album-suggestion-title { font-size: 0.85rem; font-weight: 600; color: var(--ivory); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .album-suggestion-artist { font-size: 0.75rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .album-suggestion-select { color: var(--muted); display: flex; align-items: center; }
        .album-suggestion-select svg { width: 14px; height: 14px; }
        .album-modal-actions { display: flex; gap: 0.75rem; }

        /* ─── NOTIFICATIONS ─── */
        .notification {
            position: fixed;
            top: 1.25rem; right: 1.25rem;
            background: #111113;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 0.85rem 1.25rem;
            color: var(--ivory);
            font-size: 0.85rem;
            font-weight: 500;
            z-index: 9000;
            transform: translateX(120%);
            opacity: 0;
            transition: transform 0.35s cubic-bezier(0.16,1,0.3,1), opacity 0.35s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            max-width: 320px;
        }
        .notification.show { transform: translateX(0); opacity: 1; }
        .notification-success { border-left: 3px solid #10b981; }
        .notification-error { border-left: 3px solid #ef4444; }
        .notification-info { border-left: 3px solid var(--copper); }
        .notification-close { background: none; border: none; color: var(--muted); cursor: none; margin-left: auto; display: flex; align-items: center; }
        .notification-close svg { width: 14px; height: 14px; }
        .notification-close:hover { color: var(--ivory); }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 768px) {
            body { cursor: auto; }
            .cursor, .cursor-ring { display: none; }
            button { cursor: pointer; }
            a { cursor: pointer; }

            .dash-header { padding: 1.25rem; }
            .dash-header.scrolled { padding: 0.9rem 1.25rem; }

            .dash-profile {
                grid-template-columns: 1fr;
                padding: 8rem 1.25rem 3rem;
                gap: 1.75rem;
            }
            .dash-avatar-wrap { display: flex; align-items: center; gap: 1.25rem; }
            .profile-avatar { width: 80px; height: 80px; }

            .dash-settings-inner { padding: 1rem 1.25rem; }
            .dash-settings-row { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .dash-settings-left { flex-wrap: wrap; }

            .dash-main { padding: 0 1.25rem 4rem; }
            .dash-category { padding: 2.5rem 0; }
            .dash-category-header { align-items: center; }
            .dash-category-title { font-size: clamp(2rem, 8vw, 3rem); }

            .album-card-horizontal { flex: 0 0 140px; }
            .album-cover { width: 140px; height: 140px; }
            .album-info-horizontal { display: block; }

            .dash-footer { flex-direction: column; gap: 0.4rem; text-align: center; padding: 1.5rem 1.25rem; }
        }

        @media (max-width: 480px) {
            .dash-name { font-size: 2.75rem; }
        }

        /* Animate-spin for JS loading states */
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <!-- Custom cursor -->
    <div class="cursor" id="cursor"></div>
    <div class="cursor-ring" id="cursorRing"></div>

    <!-- Music elements — hidden but kept for JS compatibility -->
    <div class="music-elements" style="display:none;" aria-hidden="true">
        <i data-lucide="music" class="music-note"></i>
        <i data-lucide="music-2" class="music-note"></i>
        <i data-lucide="music-3" class="music-note"></i>
        <i data-lucide="music-4" class="music-note"></i>
    </div>

    <!-- Header -->
    <header class="dash-header" id="header">
        <a href="/" class="dash-logo">UNIVERSON</a>
        <button id="logoutBtn" class="dash-logout">
            <i data-lucide="log-out" style="width:13px;height:13px;"></i>
            Déconnexion
        </button>
    </header>

    <!-- Profile -->
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

    <!-- Settings / visibility — JS queries: #visibilityToggle, .switch-label, .switch-text, #shareOwnProfileBtn, .pseudo-display -->
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

    <!-- Pseudo modal — JS queries: #pseudoModal, #pseudoInput, #pseudoFeedback, #savePseudoBtn, #cancelPseudoBtn -->
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

    <!-- Album Categories — JS queries [id^="add"][id$="Btn"] -->
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
                <p>Aucun album dans cette catégorie</p>
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
        /* Custom cursor */
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

        /* Sticky header */
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 60);
        }, { passive: true });
    </script>
</body>
</html>
