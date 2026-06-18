<?php
    require __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../env_data.php';
    require_once __DIR__ . '/../util/functions.php';

    $client = new Google\Client;
    $client->setClientId($clientID);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirect_uri);
    $client->addScope("email");
    $client->addScope("profile");

    $url = $client->createAuthUrl();

    $isLoggedIn = isset($_COOKIE['session_token']) && $_COOKIE['session_token'] !== '';
    $buttonUrl = $isLoggedIn ? '/pages/dashboard.php' : $url;
    $buttonText = $isLoggedIn ? 'Accéder à votre profil' : 'Se connecter avec Google';
    $showGoogleIcon = !$isLoggedIn;
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Universon transforme votre collection musicale en une exposition artistique. Créez votre musée musical personnel et partagez vos albums préférés comme des œuvres d'art.">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#F1E9DA">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $site_url ?>">
    <meta property="og:title" content="Universon — Votre Musée Musical Personnel">
    <meta property="og:description" content="Exposez vos albums comme des œuvres d'art.">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Universon — Votre Musée Musical Personnel">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/img/logo.ico">
    <title>Universon — Votre Musée Musical Personnel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..900;1,9..144,400..900&family=EB+Garamond:ital,wght@0,400..600;1,400..600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            /* Murs & encre */
            --craie:#F1E9DA; --velin:#FBF6EC; --stuc:#E5DAC4; --marbre:#D8D2C4;
            --sepia:#2B2118; --sepia-doux:#5A4B38; --muted:rgba(43,33,24,.45);
            /* Accents */
            --or:#B5912F; --or-clair:#E6C86E; --or-ombre:#856321;
            --grenat:#7A2233; --grenat-fonce:#511522;
            --bleu-nuit:#213A4C; --patine:#6E8275;
            /* Filets & matières */
            --filet:rgba(43,33,24,.12); --filet-or:rgba(181,145,47,.35);
            --ombre-cadre:rgba(43,33,24,.18); --halo:rgba(230,200,110,.18);
            /* Arrondis */
            --r-cartel:2px; --r-sm:6px; --r-cartouche:14px;
            --r-panneau:26px; --r-medaillon:999px;
            /* Dégradés */
            --dorure:linear-gradient(135deg,#856321,#B5912F 38%,#E6C86E 55%,#B5912F 72%,#856321);
            --velours:linear-gradient(180deg,#7A2233,#5E1A28);
            --verriere:radial-gradient(125% 90% at 50% -8%,#FBF6EC 0%,#F1E9DA 45%,#E5DAC4 100%);
            --spot:radial-gradient(ellipse 60% 55% at 50% 38%,var(--halo) 0%,transparent 70%);
            --font-display:'Fraunces',serif;
            --font-serif:'EB Garamond',serif;
            --gouttiere: clamp(1.25rem, 4vw, 3rem);
            --rythme: clamp(5rem, 10vh, 8rem);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-serif);
            background: var(--craie);
            background-image: var(--verriere);
            background-attachment: fixed;
            color: var(--sepia);
            overflow-x: hidden;
            min-height: 100vh;
            cursor: none;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ─── GRAIN DE PLÂTRE (chaud) ─────────────── */
        body::after {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='320' height='320'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 0.17 0 0 0 0 0.13 0 0 0 0 0.09 0 0 0 1 0'/%3E%3C/filter%3E%3Crect width='320' height='320' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: 0.04;
            mix-blend-mode: multiply;
            pointer-events: none;
            z-index: 9990;
        }

        /* ─── CURSEUR : FEUILLE D'OR + LAURIER ────── */
        .cursor {
            position: fixed; left: 0; top: 0;
            width: 9px; height: 9px;
            background: var(--or);
            border-radius: 1px;
            pointer-events: none;
            z-index: 9999;
            transform: translate(-50%, -50%) rotate(45deg);
            transition: width .2s, height .2s, background .2s;
        }
        .cursor-ring {
            position: fixed; left: 0; top: 0;
            width: 36px; height: 36px;
            border: 1px solid var(--filet-or);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9998;
            transform: translate(-50%, -50%);
            transition: width .3s, height .3s, border-color .3s;
        }
        .cursor--hover { width: 14px; height: 14px; background: var(--grenat); }
        .cursor-ring--hover { width: 58px; height: 58px; border-color: rgba(181,145,47,.55); }

        /* ─── HEADER (Bandeau de musée) ───────────── */
        .entete {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 200;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 1.5rem;
            padding: 1.15rem var(--gouttiere);
            transition: padding .4s, background .4s, border-color .4s, box-shadow .4s;
        }
        .entete.scrolled {
            padding: .7rem var(--gouttiere);
            background: rgba(251, 246, 236, 0.82);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--filet-or);
        }

        .logo {
            display: inline-flex; align-items: center; gap: .55rem;
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 1.25rem;
            letter-spacing: 0.18em;
            color: var(--sepia);
            text-decoration: none;
            transition: opacity .2s;
        }
        .logo:hover { opacity: .65; }
        .logo .lys { width: 16px; height: 20px; color: var(--or); flex-shrink: 0; }

        .entete-ornement {
            display: none;
            justify-self: center;
            color: var(--or);
            opacity: .5;
        }
        .entete-ornement svg { width: 84px; height: 14px; display: block; }

        .billet {
            display: inline-flex; align-items: center; gap: .55rem;
            background: var(--velin);
            color: var(--sepia);
            padding: .55rem 1.2rem;
            border: 1px solid var(--filet-or);
            border-radius: var(--r-medaillon);
            font-family: var(--font-serif);
            font-size: 0.92rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-decoration: none;
            box-shadow: 0 6px 18px var(--ombre-cadre);
            transition: background .3s, color .3s, transform .3s, border-color .3s;
            white-space: nowrap;
        }
        .billet:hover { background: var(--grenat); color: var(--velin); border-color: var(--grenat); transform: translateY(-1px); }
        .billet .g-jeton {
            width: 22px; height: 22px; flex-shrink: 0;
            background: var(--craie); border: 1px solid var(--filet-or); border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .billet .g-jeton svg { width: 13px; height: 13px; display: block; }

        /* ─── FRONTISPICE (Hero centré) ───────────── */
        .frontispice {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 7rem var(--gouttiere) 4.5rem;
            overflow: hidden;
            background: var(--spot);
        }

        .rosace {
            position: absolute;
            top: 50%; left: 50%;
            width: min(78vw, 720px);
            aspect-ratio: 1;
            color: var(--or);
            opacity: .14;
            pointer-events: none;
            transform-origin: center center;
            animation: tourne 90s linear infinite;
            z-index: 0;
        }
        @keyframes tourne {
            from { transform: translate(-50%, -52%) rotate(0deg); }
            to   { transform: translate(-50%, -52%) rotate(360deg); }
        }

        .frontispice > *:not(.rosace) { position: relative; z-index: 2; }

        .frontispice-cartouche {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
            padding: .45rem 1.25rem;
            background: rgba(251,246,236,.55);
            border: 1px solid var(--filet-or);
            border-radius: var(--r-cartel);
            font-family: var(--font-serif);
            font-size: .72rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--or-ombre);
            margin-bottom: 2rem;
            opacity: 0;
            animation: fadeUp .8s ease forwards .1s;
        }
        .frontispice-cartouche .sep { color: var(--or); font-size: .95rem; line-height: 1; }

        .frontispice-titre {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: clamp(3.5rem, 14vw, 12rem);
            line-height: .9;
            letter-spacing: -.01em;
            color: var(--sepia);
            margin-bottom: 1.5rem;
            opacity: 0;
            transform: translateY(28px);
            animation: slideUp 1.1s cubic-bezier(0.16,1,0.3,1) forwards .25s;
        }
        .frontispice-titre .gilt {
            font-style: italic;
            background: var(--dorure);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }
        @keyframes slideUp { to { opacity: 1; transform: translateY(0); } }

        .guirlande {
            display: block;
            width: clamp(180px, 35vw, 320px);
            height: auto;
            color: var(--or);
            margin: 0 auto 1.5rem;
            opacity: 0;
            animation: fadeUp .8s ease forwards .55s;
        }

        .frontispice-sous {
            font-family: var(--font-display);
            font-style: italic;
            font-size: clamp(1.1rem, 2.4vw, 1.6rem);
            font-weight: 400;
            color: var(--sepia-doux);
            max-width: 620px;
            margin: 0 auto 3rem;
            opacity: 0;
            animation: fadeUp .8s ease forwards .65s;
        }

        .frontispice-bas {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            max-width: 580px;
            margin: 0 auto;
            opacity: 0;
            animation: fadeUp .8s ease forwards .85s;
        }
        .frontispice-desc {
            font-family: var(--font-serif);
            font-size: 1rem;
            line-height: 1.75;
            color: var(--sepia-doux);
            max-width: 460px;
        }

        .cta-galerie {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            color: var(--sepia);
            text-decoration: none;
            font-family: var(--font-display);
            font-style: italic;
            font-weight: 500;
            font-size: 1.1rem;
            letter-spacing: .01em;
            padding: .55rem .65rem .55rem 1.5rem;
            border: 1px solid var(--filet-or);
            border-radius: var(--r-medaillon);
            background: rgba(251,246,236,.45);
            transition: gap .35s cubic-bezier(0.16,1,0.3,1), background .3s, border-color .3s;
        }
        .cta-galerie:hover { gap: 1.5rem; background: var(--velin); border-color: var(--or); }
        .cta-galerie .medaillon {
            width: 44px; height: 44px; border-radius: 50%;
            background: var(--dorure);
            display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: var(--velin);
            box-shadow: 0 6px 18px var(--ombre-cadre), inset 0 1px 1px rgba(255,255,255,.4);
            transition: transform .35s, filter .25s;
        }
        .cta-galerie:hover .medaillon { transform: rotate(45deg); filter: brightness(1.08); }
        .cta-galerie .medaillon svg { width: 18px; height: 18px; display: block; }

        /* ─── FRISE COURANTE (Ticker) ─────────────── */
        .frise {
            border-top: 1px solid var(--filet-or);
            border-bottom: 1px solid var(--filet-or);
            background: var(--stuc);
            padding: .9rem 0;
            overflow: hidden;
        }
        .frise-track {
            display: flex;
            gap: 0;
            animation: marquee 35s linear infinite;
            white-space: nowrap;
            width: max-content;
        }
        .frise-item {
            display: inline-flex; align-items: center; gap: 1.4rem;
            font-family: var(--font-display);
            font-size: 1.05rem; font-weight: 500;
            letter-spacing: .14em;
            color: var(--or-ombre);
            padding: 0 1.4rem;
        }
        .frise-sep { color: var(--or); font-size: 1rem; line-height: 1; }
        @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }

        /* ─── MANIFESTE (Cartouche central) ───────── */
        .manifeste {
            padding: var(--rythme) var(--gouttiere);
            max-width: 1100px;
            margin: 0 auto;
        }
        .manifeste-cartouche {
            position: relative;
            background: var(--velin);
            border: 1px solid var(--filet-or);
            border-radius: var(--r-panneau);
            padding: clamp(2.5rem, 6vw, 4.5rem) clamp(1.5rem, 5vw, 4rem);
            box-shadow: 0 18px 40px var(--ombre-cadre);
            text-align: center;
        }
        .manifeste-cartouche::before,
        .manifeste-cartouche::after {
            content: '';
            position: absolute;
            left: 18px; right: 18px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--or), transparent);
            opacity: .55;
            pointer-events: none;
        }
        .manifeste-cartouche::before { top: 18px; }
        .manifeste-cartouche::after  { bottom: 18px; }

        .volute {
            position: absolute;
            width: 42px; height: 42px;
            color: var(--or);
            opacity: .6;
            pointer-events: none;
        }
        .volute-tl { top: 8px; left: 8px; }
        .volute-tr { top: 8px; right: 8px; transform: scaleX(-1); }
        .volute-bl { bottom: 8px; left: 8px; transform: scaleY(-1); }
        .volute-br { bottom: 8px; right: 8px; transform: scale(-1, -1); }

        .manifeste-label {
            display: inline-block;
            font-family: var(--font-serif);
            font-size: .72rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: var(--or-ombre);
            margin-bottom: 2rem;
        }
        .manifeste-label .pet { color: var(--or); margin: 0 .5rem; }

        .manifeste-texte {
            font-family: var(--font-display);
            font-weight: 400;
            font-size: clamp(1.7rem, 4.2vw, 3.2rem);
            line-height: 1.2;
            color: var(--sepia);
            margin-bottom: 2.25rem;
        }
        .manifeste-texte em {
            font-style: italic;
            background: var(--dorure);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }
        .manifeste-fleuron {
            color: var(--or);
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
        }
        .manifeste-aside {
            font-family: var(--font-serif);
            font-style: italic;
            font-size: 1.05rem;
            line-height: 1.8;
            color: var(--sepia-doux);
            max-width: 600px;
            margin: 0 auto;
        }

        /* ─── CATALOGUE (Plan du musée 2×2) ───────── */
        .catalogue {
            padding: var(--rythme) var(--gouttiere);
            max-width: 1320px;
            margin: 0 auto;
        }
        .section-tete {
            text-align: center;
            margin-bottom: clamp(3rem, 6vh, 5rem);
        }
        .section-tete .coquille {
            display: block;
            width: 64px; height: auto;
            color: var(--or);
            opacity: .6;
            margin: 0 auto 1rem;
        }
        .cartel-label {
            display: block;
            font-family: var(--font-serif);
            font-size: .72rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: var(--or-ombre);
            margin-bottom: 1rem;
        }
        .cartel-label .pet { color: var(--or); margin: 0 .5rem; }
        .section-titre {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            line-height: 1;
            color: var(--sepia);
            letter-spacing: -.005em;
        }
        .section-titre em {
            font-style: italic;
            background: var(--dorure);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }

        .catalogue-grille {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: clamp(1.25rem, 3vw, 2.5rem);
        }

        .salle {
            position: relative;
            background: var(--velin);
            border: 1px solid var(--filet-or);
            border-radius: var(--r-cartouche);
            padding: clamp(2rem, 4vw, 3rem);
            box-shadow: 0 12px 28px var(--ombre-cadre);
            transition: transform .4s cubic-bezier(0.16,1,0.3,1), box-shadow .4s, border-color .4s;
            overflow: hidden;
        }
        .salle::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--dorure);
            opacity: .35;
            transition: opacity .35s;
        }
        .salle:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 50px var(--ombre-cadre), 0 0 60px var(--halo);
            border-color: var(--or);
        }
        .salle:hover::before { opacity: 1; }

        .salle-medaillon {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: var(--velin);
            border: 1px solid var(--or);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-display);
            font-style: italic;
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--or-ombre);
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 14px var(--ombre-cadre);
        }
        .salle-titre {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: clamp(1.35rem, 2.5vw, 1.75rem);
            color: var(--sepia);
            margin-bottom: .75rem;
            transition: color .35s;
        }
        .salle:hover .salle-titre { color: var(--or-ombre); }
        .salle-desc {
            font-family: var(--font-serif);
            font-size: 1rem;
            line-height: 1.7;
            color: var(--sepia-doux);
        }

        /* ─── PROMENADE (Cordon vertical) ─────────── */
        .promenade {
            background: var(--stuc);
            border-top: 1px solid var(--filet-or);
            border-bottom: 1px solid var(--filet-or);
            padding: var(--rythme) var(--gouttiere);
        }
        .promenade-inner {
            max-width: 1000px;
            margin: 0 auto;
        }
        .promenade-cordon {
            list-style: none;
            position: relative;
            margin: 0 auto;
            max-width: 760px;
        }
        .promenade-cordon::before {
            content: '';
            position: absolute;
            top: 28px; bottom: 28px;
            left: 28px;
            width: 3px;
            background: var(--velours);
            border-radius: 99px;
            opacity: .55;
            pointer-events: none;
        }
        .poteau {
            position: relative;
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 1.5rem;
            padding: clamp(1.75rem, 4vh, 3rem) 0;
        }
        .poteau:first-child { padding-top: 0; }
        .poteau:last-child  { padding-bottom: 0; }

        .medaillon-or {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: var(--velin);
            border: 1px solid var(--or);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-display);
            font-style: italic;
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--or-ombre);
            box-shadow: 0 4px 14px var(--ombre-cadre);
            position: relative;
            z-index: 1;
        }
        .poteau-cartel { padding-top: .35rem; min-width: 0; }
        .poteau-titre {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: clamp(1.3rem, 2.5vw, 1.6rem);
            color: var(--sepia);
            margin-bottom: .6rem;
        }
        .poteau-desc {
            font-family: var(--font-serif);
            font-size: 1rem;
            line-height: 1.8;
            color: var(--sepia-doux);
            max-width: 540px;
        }

        /* ─── CONSÉCRATION (Final CTA) ────────────── */
        .consecration {
            position: relative;
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: clamp(5rem, 12vh, 8rem) var(--gouttiere);
            overflow: hidden;
            background: var(--spot);
        }
        .consecration .coquille {
            display: block;
            width: 72px; height: auto;
            color: var(--or);
            opacity: .55;
            margin-bottom: 1.25rem;
        }
        .consecration-label {
            font-family: var(--font-serif);
            font-size: .72rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: var(--or-ombre);
            margin-bottom: 1.5rem;
        }
        .consecration-label .pet { color: var(--or); margin: 0 .5rem; }

        .consecration-titre {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: clamp(3.25rem, 13vw, 10.5rem);
            line-height: .92;
            color: var(--sepia);
            margin-bottom: 1.75rem;
            letter-spacing: -.005em;
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 1.1s cubic-bezier(0.16,1,0.3,1), transform 1.1s cubic-bezier(0.16,1,0.3,1);
        }
        .consecration-titre.is-revealed { opacity: 1; transform: translateY(0); }
        .consecration-titre em {
            font-style: italic;
            background: var(--dorure);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }
        .guirlande-large {
            width: clamp(220px, 40vw, 380px);
            margin: 0 auto 2.5rem;
        }

        .consecration-btn {
            display: inline-flex;
            align-items: center;
            gap: .8rem;
            background: var(--velours);
            color: var(--velin);
            padding: 1.1rem 2.5rem;
            border: 1px solid var(--grenat-fonce);
            border-radius: var(--r-medaillon);
            font-family: var(--font-serif);
            font-size: 1.02rem;
            font-weight: 600;
            letter-spacing: .02em;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px var(--ombre-cadre);
            transition: transform .35s cubic-bezier(0.4,0,0.2,1), box-shadow .35s;
        }
        .consecration-btn::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(110deg, transparent 30%, rgba(230,200,110,.35) 50%, transparent 70%);
            transform: translateX(-120%);
            transition: transform .7s;
            pointer-events: none;
        }
        .consecration-btn:hover { transform: scale(1.04); box-shadow: 0 16px 44px rgba(122,34,51,.3); }
        .consecration-btn:hover::after { transform: translateX(120%); }
        .consecration-btn .g-jeton {
            width: 24px; height: 24px; flex-shrink: 0;
            background: var(--craie);
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .consecration-btn .g-jeton svg { width: 14px; height: 14px; display: block; }
        .consecration-btn .user-icon { width: 20px; height: 20px; color: currentColor; }

        .fleuron-final {
            display: block;
            margin-top: 2.5rem;
            color: var(--or);
            font-size: 1.5rem;
            opacity: .65;
            line-height: 1;
        }

        /* ─── SOCLE (Footer) ──────────────────────── */
        .socle {
            padding: 2rem var(--gouttiere);
            border-top: 1px solid var(--filet-or);
            background: var(--marbre);
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1.5rem;
            font-family: var(--font-serif);
            font-size: .78rem;
            letter-spacing: .04em;
            color: var(--sepia-doux);
        }
        .socle-copy { justify-self: start; }
        .socle-fleuron { justify-self: center; color: var(--or); font-size: 1.1rem; line-height: 1; }
        .socle-tag { justify-self: end; }

        /* ─── LEVER DE VOILE (Reveal) ─────────────── */
        .js-reveal { opacity: 0; transform: translateY(28px); transition: opacity .9s cubic-bezier(0.16,1,0.3,1), transform .9s cubic-bezier(0.16,1,0.3,1); }
        .js-reveal.is-revealed { opacity: 1; transform: translateY(0); }
        .js-reveal-delay-1 { transition-delay: .1s; }
        .js-reveal-delay-2 { transition-delay: .2s; }
        .js-reveal-delay-3 { transition-delay: .3s; }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        /* ─── FOCUS A11Y ──────────────────────────── */
        a:focus-visible, button:focus-visible {
            outline: 2px solid var(--or);
            outline-offset: 3px;
            border-radius: 4px;
        }

        /* ─── RESPONSIVE ──────────────────────────── */
        @media (min-width: 992px) {
            .entete-ornement { display: inline-flex; }
        }

        @media (max-width: 991px) {
            .catalogue-grille { gap: 1.25rem; }
            .manifeste-cartouche::before { top: 14px; }
            .manifeste-cartouche::after { bottom: 14px; }
        }

        @media (max-width: 767px) {
            body { cursor: auto; }
            .cursor, .cursor-ring { display: none; }

            .entete { gap: .75rem; padding: .9rem 1.25rem; }
            .entete.scrolled { padding: .65rem 1.25rem; }
            .logo { font-size: 1.1rem; letter-spacing: .16em; }

            .frontispice { padding: 6.5rem 1.25rem 3.5rem; min-height: 92vh; }
            .frontispice-cartouche { font-size: .65rem; letter-spacing: .2em; padding: .35rem 1rem; gap: .65rem; }

            .frontispice-sous { margin-bottom: 2.25rem; }
            .frontispice-bas { gap: 1.5rem; }

            .catalogue-grille { grid-template-columns: 1fr; }
            .catalogue { padding: 5rem 1.25rem; }
            .manifeste { padding: 5rem 1.25rem; }
            .manifeste-cartouche { padding: 2.5rem 1.5rem; }
            .volute { width: 28px; height: 28px; }
            .volute-tl, .volute-tr { top: 6px; }
            .volute-bl, .volute-br { bottom: 6px; }
            .volute-tl, .volute-bl { left: 6px; }
            .volute-tr, .volute-br { right: 6px; }

            .promenade { padding: 5rem 1.25rem; }

            .consecration { padding: 5rem 1.25rem; min-height: 72vh; }
            .consecration .coquille { width: 60px; }
            .guirlande-large { margin-bottom: 2rem; }

            .socle {
                grid-template-columns: 1fr;
                text-align: center;
                gap: .55rem;
                padding: 1.75rem 1.25rem;
            }
            .socle-copy, .socle-tag { justify-self: center; }
        }

        @media (max-width: 499px) {
            .billet { padding: .5rem .55rem; }
            .billet-texte { display: none; }
            .billet .g-jeton { width: 24px; height: 24px; }
            .billet .g-jeton svg { width: 14px; height: 14px; }

            .cta-galerie { padding: .45rem .5rem .45rem 1.2rem; font-size: 1rem; gap: .75rem; }
            .cta-galerie .medaillon { width: 38px; height: 38px; }
            .cta-galerie .medaillon svg { width: 16px; height: 16px; }

            .consecration-btn { padding: .95rem 1.6rem; font-size: .95rem; }

            .salle { padding: 1.75rem 1.5rem; }
            .salle-medaillon { width: 48px; height: 48px; font-size: 1.1rem; margin-bottom: 1.25rem; }

            .poteau { grid-template-columns: 44px 1fr; gap: 1.1rem; }
            .medaillon-or { width: 44px; height: 44px; font-size: 1.05rem; }
            .promenade-cordon::before { left: 22px; top: 22px; bottom: 22px; }

            .frontispice-cartouche { gap: .55rem; padding: .3rem .85rem; }
        }

        @media (max-width: 379px) {
            .frontispice-cartouche .annee,
            .frontispice-cartouche .sep { display: none; }
            .socle-copy span.annee { display: inline; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
                scroll-behavior: auto !important;
            }
            .rosace { animation: none; }
        }
    </style>
</head>
<body>

    <!-- Curseur feuille d'or -->
    <div class="cursor" id="cursor" aria-hidden="true"></div>
    <div class="cursor-ring" id="cursorRing" aria-hidden="true"></div>

    <!-- ─── HEADER : Bandeau de musée ─── -->
    <header class="entete" id="entete">
        <a href="/" class="logo">
            <svg class="lys" viewBox="0 0 24 30" fill="currentColor" aria-hidden="true">
                <path d="M12 0c1.6 2.3 1.6 4.7 0 7-1.6-2.3-1.6-4.7 0-7zM12 7c2.4 1.2 3.6 3.2 3.4 6.2 2.2-1.4 4.4-1 6.6 1.2-3 .4-4.6 2-4.8 4.8-1.6-1.8-3.4-2.4-5.2-1.8v8.2c2-.4 3.8-.2 5.4 1.4H8.6c1.6-1.6 3.4-1.8 5.4-1.4v-8.2c-1.8-.6-3.6 0-5.2 1.8-.2-2.8-1.8-4.4-4.8-4.8 2.2-2.2 4.4-2.6 6.6-1.2C8.4 10.2 9.6 8.2 12 7z"/>
            </svg>
            UNIVERSON
        </a>

        <span class="entete-ornement" aria-hidden="true">
            <svg viewBox="0 0 84 14" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
                <line x1="0" y1="7" x2="34" y2="7"/>
                <line x1="50" y1="7" x2="84" y2="7"/>
                <circle cx="42" cy="7" r="2" fill="currentColor"/>
            </svg>
        </span>

        <a href="<?= htmlspecialchars($buttonUrl) ?>" class="billet">
            <?php if ($showGoogleIcon): ?>
            <span class="g-jeton">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
            </span>
            <?php endif; ?>
            <span class="billet-texte"><?= htmlspecialchars($buttonText) ?></span>
        </a>
    </header>

    <!-- ─── FRONTISPICE : Hero centré ─── -->
    <section class="frontispice" aria-label="Frontispice">

        <!-- Rosace de plafond -->
        <svg class="rosace" viewBox="0 0 200 200" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="0.7">
            <circle cx="100" cy="100" r="97"/>
            <circle cx="100" cy="100" r="80"/>
            <circle cx="100" cy="100" r="54"/>
            <circle cx="100" cy="100" r="28"/>
            <circle cx="100" cy="100" r="9" fill="currentColor" fill-opacity="0.3" stroke="none"/>
            <g>
                <ellipse cx="100" cy="40" rx="13" ry="40"/>
                <ellipse cx="100" cy="40" rx="13" ry="40" transform="rotate(30 100 100)"/>
                <ellipse cx="100" cy="40" rx="13" ry="40" transform="rotate(60 100 100)"/>
                <ellipse cx="100" cy="40" rx="13" ry="40" transform="rotate(90 100 100)"/>
                <ellipse cx="100" cy="40" rx="13" ry="40" transform="rotate(120 100 100)"/>
                <ellipse cx="100" cy="40" rx="13" ry="40" transform="rotate(150 100 100)"/>
            </g>
            <g stroke-width="0.5">
                <line x1="100" y1="4" x2="100" y2="196"/>
                <line x1="4" y1="100" x2="196" y2="100"/>
                <line x1="32" y1="32" x2="168" y2="168"/>
                <line x1="168" y1="32" x2="32" y2="168"/>
            </g>
        </svg>

        <div class="frontispice-cartouche">
            <span>Votre musée musical personnel</span>
            <span class="sep">&#10086;</span>
            <span class="annee"><?= date('Y') ?></span>
        </div>

        <h1 class="frontispice-titre">UNIVERS<span class="gilt">O</span>N</h1>

        <svg class="guirlande" viewBox="0 0 320 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
            <line x1="0" y1="12" x2="138" y2="12" stroke-opacity="0.7"/>
            <line x1="182" y1="12" x2="320" y2="12" stroke-opacity="0.7"/>
            <g transform="translate(160 12)">
                <circle r="3" fill="currentColor" stroke="none"/>
                <circle r="6.5" fill="none"/>
            </g>
            <g fill="currentColor" stroke="none" opacity="0.75">
                <ellipse cx="40" cy="6" rx="2" ry="4.5" transform="rotate(-20 40 6)"/>
                <ellipse cx="68" cy="5.5" rx="2" ry="4.5" transform="rotate(-14 68 5.5)"/>
                <ellipse cx="96" cy="6" rx="2" ry="4.5" transform="rotate(-8 96 6)"/>
                <ellipse cx="124" cy="7" rx="2" ry="4.5" transform="rotate(-3 124 7)"/>
                <ellipse cx="40" cy="18" rx="2" ry="4.5" transform="rotate(20 40 18)"/>
                <ellipse cx="68" cy="18.5" rx="2" ry="4.5" transform="rotate(14 68 18.5)"/>
                <ellipse cx="96" cy="18" rx="2" ry="4.5" transform="rotate(8 96 18)"/>
                <ellipse cx="124" cy="17" rx="2" ry="4.5" transform="rotate(3 124 17)"/>

                <ellipse cx="196" cy="7" rx="2" ry="4.5" transform="rotate(3 196 7)"/>
                <ellipse cx="224" cy="6" rx="2" ry="4.5" transform="rotate(8 224 6)"/>
                <ellipse cx="252" cy="5.5" rx="2" ry="4.5" transform="rotate(14 252 5.5)"/>
                <ellipse cx="280" cy="6" rx="2" ry="4.5" transform="rotate(20 280 6)"/>
                <ellipse cx="196" cy="17" rx="2" ry="4.5" transform="rotate(-3 196 17)"/>
                <ellipse cx="224" cy="18" rx="2" ry="4.5" transform="rotate(-8 224 18)"/>
                <ellipse cx="252" cy="18.5" rx="2" ry="4.5" transform="rotate(-14 252 18.5)"/>
                <ellipse cx="280" cy="18" rx="2" ry="4.5" transform="rotate(-20 280 18)"/>
            </g>
        </svg>

        <p class="frontispice-sous">Exposez vos albums comme des œuvres d'art</p>

        <div class="frontispice-bas">
            <p class="frontispice-desc">Transformez votre collection musicale en une exposition artistique, accrochée et éclairée comme au musée.</p>
            <a href="<?= htmlspecialchars($buttonUrl) ?>" class="cta-galerie">
                <span>Entrer dans la galerie</span>
                <span class="medaillon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </span>
            </a>
        </div>
    </section>

    <!-- ─── FRISE COURANTE ─── -->
    <div class="frise" aria-hidden="true">
        <div class="frise-track">
            <span class="frise-item">EXPOSER <span class="frise-sep">&#10086;</span></span>
            <span class="frise-item">COLLECTER <span class="frise-sep">&#10086;</span></span>
            <span class="frise-item">PARTAGER <span class="frise-sep">&#10086;</span></span>
            <span class="frise-item">DÉCOUVRIR <span class="frise-sep">&#10086;</span></span>
            <span class="frise-item">ARCHIVER <span class="frise-sep">&#10086;</span></span>
            <span class="frise-item">EXPOSER <span class="frise-sep">&#10086;</span></span>
            <span class="frise-item">COLLECTER <span class="frise-sep">&#10086;</span></span>
            <span class="frise-item">PARTAGER <span class="frise-sep">&#10086;</span></span>
            <span class="frise-item">DÉCOUVRIR <span class="frise-sep">&#10086;</span></span>
            <span class="frise-item">ARCHIVER <span class="frise-sep">&#10086;</span></span>
        </div>
    </div>

    <!-- ─── MANIFESTE : Cartouche central ─── -->
    <section class="manifeste" aria-labelledby="manifeste-label">
        <div class="manifeste-cartouche js-reveal">

            <svg class="volute volute-tl" viewBox="0 0 40 40" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
                <path d="M2 38 Q 2 10, 24 10 Q 32 10, 32 18 Q 32 24, 26 24 Q 20 24, 20 18"/>
                <circle cx="20" cy="18" r="1.5" fill="currentColor" stroke="none"/>
            </svg>
            <svg class="volute volute-tr" viewBox="0 0 40 40" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
                <path d="M2 38 Q 2 10, 24 10 Q 32 10, 32 18 Q 32 24, 26 24 Q 20 24, 20 18"/>
                <circle cx="20" cy="18" r="1.5" fill="currentColor" stroke="none"/>
            </svg>
            <svg class="volute volute-bl" viewBox="0 0 40 40" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
                <path d="M2 38 Q 2 10, 24 10 Q 32 10, 32 18 Q 32 24, 26 24 Q 20 24, 20 18"/>
                <circle cx="20" cy="18" r="1.5" fill="currentColor" stroke="none"/>
            </svg>
            <svg class="volute volute-br" viewBox="0 0 40 40" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
                <path d="M2 38 Q 2 10, 24 10 Q 32 10, 32 18 Q 32 24, 26 24 Q 20 24, 20 18"/>
                <circle cx="20" cy="18" r="1.5" fill="currentColor" stroke="none"/>
            </svg>

            <span class="manifeste-label" id="manifeste-label"><span class="pet">&#10086;</span>Manifeste<span class="pet">&#10086;</span></span>

            <blockquote class="manifeste-texte">
                Chaque album est une <em>œuvre d'art.</em><br>
                Chaque collection raconte<br>
                une <em>histoire unique.</em>
            </blockquote>

            <div class="manifeste-fleuron" aria-hidden="true">&#10086;</div>

            <p class="manifeste-aside">
                Universon est la salle où votre passion musicale prend vie. Organisez, accrochez et partagez vos albums préférés dans une galerie personnelle qui vous ressemble.
            </p>
        </div>
    </section>

    <!-- ─── CATALOGUE : Plan du musée 2×2 ─── -->
    <section class="catalogue" aria-labelledby="catalogue-titre">
        <header class="section-tete js-reveal">
            <svg class="coquille" viewBox="0 0 64 36" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
                <path d="M4 34 Q 32 0, 60 34"/>
                <path d="M32 34 L 32 4"/>
                <path d="M32 34 L 14 10"/>
                <path d="M32 34 L 50 10"/>
                <path d="M32 34 L 22 6"/>
                <path d="M32 34 L 42 6"/>
                <path d="M32 34 L 8 22"/>
                <path d="M32 34 L 56 22"/>
                <line x1="4" y1="34" x2="60" y2="34"/>
            </svg>
            <span class="cartel-label"><span class="pet">&#10086;</span>Fonctionnalités<span class="pet">&#10086;</span></span>
            <h2 class="section-titre" id="catalogue-titre">Plan du <em>musée</em></h2>
        </header>

        <div class="catalogue-grille">
            <article class="salle js-reveal">
                <div class="salle-medaillon">I</div>
                <h3 class="salle-titre">Galerie Personnelle</h3>
                <p class="salle-desc">Présentez vos albums dans une exposition élégante et immersive, comme des tableaux sertis dans leurs cadres dorés.</p>
            </article>

            <article class="salle js-reveal js-reveal-delay-1">
                <div class="salle-medaillon">II</div>
                <h3 class="salle-titre">Intégration Spotify</h3>
                <p class="salle-desc">Accédez à des millions d'albums et enrichissez votre collection musicale grâce à la bibliothèque Spotify.</p>
            </article>

            <article class="salle js-reveal js-reveal-delay-2">
                <div class="salle-medaillon">III</div>
                <h3 class="salle-titre">Profil Public</h3>
                <p class="salle-desc">Obtenez votre URL personnalisée (@username) et partagez votre univers musical avec qui vous voulez, quand vous voulez.</p>
            </article>

            <article class="salle js-reveal js-reveal-delay-3">
                <div class="salle-medaillon">IV</div>
                <h3 class="salle-titre">Personnalisation Totale</h3>
                <p class="salle-desc">Organisez votre collection par coups de cœur, albums les plus écoutés, et ajoutez vos notes personnelles.</p>
            </article>
        </div>
    </section>

    <!-- ─── PROMENADE : Cordon vertical ─── -->
    <section class="promenade" aria-labelledby="promenade-titre">
        <div class="promenade-inner">
            <header class="section-tete js-reveal">
                <span class="cartel-label"><span class="pet">&#10086;</span>Trois étapes<span class="pet">&#10086;</span></span>
                <h2 class="section-titre" id="promenade-titre">La <em>promenade</em></h2>
            </header>

            <ol class="promenade-cordon">
                <li class="poteau js-reveal">
                    <div class="medaillon-or">I</div>
                    <div class="poteau-cartel">
                        <h3 class="poteau-titre">Connectez-vous</h3>
                        <p class="poteau-desc">Créez votre compte en quelques secondes avec Google. Simple, rapide, sécurisé.</p>
                    </div>
                </li>
                <li class="poteau js-reveal js-reveal-delay-1">
                    <div class="medaillon-or">II</div>
                    <div class="poteau-cartel">
                        <h3 class="poteau-titre">Ajoutez vos albums</h3>
                        <p class="poteau-desc">Recherchez vos albums favoris via Spotify et construisez votre collection unique.</p>
                    </div>
                </li>
                <li class="poteau js-reveal js-reveal-delay-2">
                    <div class="medaillon-or">III</div>
                    <div class="poteau-cartel">
                        <h3 class="poteau-titre">Partagez votre univers</h3>
                        <p class="poteau-desc">Votre profil public est prêt. Inspirez d'autres passionnés de musique avec vos découvertes.</p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <!-- ─── CONSÉCRATION : Final CTA ─── -->
    <section class="consecration" aria-labelledby="consecration-titre">
        <svg class="coquille js-reveal" viewBox="0 0 64 36" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
            <path d="M4 34 Q 32 0, 60 34"/>
            <path d="M32 34 L 32 4"/>
            <path d="M32 34 L 14 10"/>
            <path d="M32 34 L 50 10"/>
            <path d="M32 34 L 22 6"/>
            <path d="M32 34 L 42 6"/>
            <path d="M32 34 L 8 22"/>
            <path d="M32 34 L 56 22"/>
            <line x1="4" y1="34" x2="60" y2="34"/>
        </svg>

        <span class="consecration-label js-reveal"><span class="pet">&#10086;</span>Rejoindre Universon<span class="pet">&#10086;</span></span>

        <h2 class="consecration-titre" id="finalTitre">Créez votre <em>musée</em></h2>

        <svg class="guirlande guirlande-large js-reveal" viewBox="0 0 320 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
            <line x1="0" y1="12" x2="138" y2="12" stroke-opacity="0.7"/>
            <line x1="182" y1="12" x2="320" y2="12" stroke-opacity="0.7"/>
            <g transform="translate(160 12)">
                <circle r="3" fill="currentColor" stroke="none"/>
                <circle r="6.5" fill="none"/>
            </g>
            <g fill="currentColor" stroke="none" opacity="0.75">
                <ellipse cx="40" cy="6" rx="2" ry="4.5" transform="rotate(-20 40 6)"/>
                <ellipse cx="68" cy="5.5" rx="2" ry="4.5" transform="rotate(-14 68 5.5)"/>
                <ellipse cx="96" cy="6" rx="2" ry="4.5" transform="rotate(-8 96 6)"/>
                <ellipse cx="124" cy="7" rx="2" ry="4.5" transform="rotate(-3 124 7)"/>
                <ellipse cx="40" cy="18" rx="2" ry="4.5" transform="rotate(20 40 18)"/>
                <ellipse cx="68" cy="18.5" rx="2" ry="4.5" transform="rotate(14 68 18.5)"/>
                <ellipse cx="96" cy="18" rx="2" ry="4.5" transform="rotate(8 96 18)"/>
                <ellipse cx="124" cy="17" rx="2" ry="4.5" transform="rotate(3 124 17)"/>

                <ellipse cx="196" cy="7" rx="2" ry="4.5" transform="rotate(3 196 7)"/>
                <ellipse cx="224" cy="6" rx="2" ry="4.5" transform="rotate(8 224 6)"/>
                <ellipse cx="252" cy="5.5" rx="2" ry="4.5" transform="rotate(14 252 5.5)"/>
                <ellipse cx="280" cy="6" rx="2" ry="4.5" transform="rotate(20 280 6)"/>
                <ellipse cx="196" cy="17" rx="2" ry="4.5" transform="rotate(-3 196 17)"/>
                <ellipse cx="224" cy="18" rx="2" ry="4.5" transform="rotate(-8 224 18)"/>
                <ellipse cx="252" cy="18.5" rx="2" ry="4.5" transform="rotate(-14 252 18.5)"/>
                <ellipse cx="280" cy="18" rx="2" ry="4.5" transform="rotate(-20 280 18)"/>
            </g>
        </svg>

        <a href="<?= htmlspecialchars($buttonUrl) ?>" class="consecration-btn js-reveal js-reveal-delay-1">
            <?php if ($showGoogleIcon): ?>
            <span class="g-jeton">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
            </span>
            <?php else: ?>
            <svg class="user-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <?php endif; ?>
            <span><?= htmlspecialchars($buttonText) ?></span>
        </a>

        <span class="fleuron-final js-reveal js-reveal-delay-2" aria-hidden="true">&#10086;</span>
    </section>

    <!-- ─── SOCLE (Footer) ─── -->
    <footer class="socle">
        <span class="socle-copy">© <?= date('Y') ?> Universon</span>
        <span class="socle-fleuron" aria-hidden="true">&#10086;</span>
        <span class="socle-tag">Transformez votre passion musicale en art</span>
    </footer>

    <script>
        /* ── Curseur feuille d'or ── */
        (function () {
            const cur  = document.getElementById('cursor');
            const ring = document.getElementById('cursorRing');
            if (!cur || !ring) return;
            if (window.matchMedia('(max-width: 767px)').matches) return;

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
        })();

        /* ── Lever de voile (reveal) ── */
        (function () {
            const reveals = document.querySelectorAll('.js-reveal');
            const obs = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('is-revealed');
                        obs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.12 });
            reveals.forEach(el => obs.observe(el));

            const finalTitre = document.getElementById('finalTitre');
            if (finalTitre) {
                const titleObs = new IntersectionObserver((entries) => {
                    entries.forEach(e => {
                        if (e.isIntersecting) {
                            finalTitre.classList.add('is-revealed');
                            titleObs.unobserve(e.target);
                        }
                    });
                }, { threshold: 0.1 });
                titleObs.observe(finalTitre);
            }
        })();

        /* ── Header collé ── */
        (function () {
            const entete = document.getElementById('entete');
            if (!entete) return;
            const onScroll = () => entete.classList.toggle('scrolled', window.scrollY > 60);
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        })();
    </script>
</body>
</html>
