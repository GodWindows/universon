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
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --ink:    #09090A;
            --ink-2:  #111113;
            --ivory:  #F0EBE3;
            --copper: #C87941;
            --copper-dim: rgba(200, 121, 65, 0.12);
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

        /* ─── GRAIN OVERLAY ─────────────────────── */
        body::after {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
            opacity: 0.035;
            pointer-events: none;
            z-index: 9990;
        }

        /* ─── CUSTOM CURSOR ─────────────────────── */
        .cursor {
            position: fixed;
            width: 7px; height: 7px;
            background: var(--copper);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            transform: translate(-50%, -50%);
            transition: transform 0.08s, width 0.2s, height 0.2s, background 0.2s;
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

        .cursor--hover { width: 14px; height: 14px; background: var(--ivory); }
        .cursor-ring--hover { width: 60px; height: 60px; border-color: rgba(240, 235, 227, 0.3); }

        /* ─── HEADER ────────────────────────────── */
        .header {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 200;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.75rem 2.5rem;
            transition: padding 0.4s, background 0.4s, border-color 0.4s;
        }

        .header.scrolled {
            padding: 1rem 2.5rem;
            background: rgba(9, 9, 10, 0.85);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--line);
        }

        .logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.3rem;
            letter-spacing: 0.2em;
            color: var(--ivory);
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .logo:hover { opacity: 0.55; }

        .header-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--ivory);
            color: var(--ink);
            padding: 0.6rem 1.4rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-decoration: none;
            transition: background 0.25s, color 0.25s, transform 0.25s;
        }
        .header-btn:hover { background: var(--copper); color: var(--ivory); transform: scale(1.04); }
        .header-btn .g-icon { width: 16px; height: 16px; flex-shrink: 0; }

        /* ─── HERO ──────────────────────────────── */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2rem 2.5rem 4rem;
            position: relative;
            overflow: hidden;
        }

        /* Vinyl BG */
        .vinyl-bg {
            position: absolute;
            right: -80px; top: 50%;
            transform: translateY(-50%);
            width: 580px; height: 580px;
            pointer-events: none;
            animation: spinVinyl 60s linear infinite;
        }
        @keyframes spinVinyl { to { transform: translateY(-50%) rotate(360deg); } }

        .vinyl-ring {
            position: absolute;
            border-radius: 50%;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            border: 1px solid var(--copper-dim);
        }
        .vr1 { width: 100%; height: 100%; }
        .vr2 { width: 78%; height: 78%; border-color: rgba(200,121,65,0.16); }
        .vr3 { width: 58%; height: 58%; border-color: rgba(200,121,65,0.22); }
        .vr4 { width: 40%; height: 40%; border-color: rgba(200,121,65,0.3); background: rgba(200,121,65,0.03); }
        .vr5 { width: 20%; height: 20%; border-color: rgba(200,121,65,0.4); }
        .vinyl-dot {
            position: absolute;
            width: 5%; height: 5%;
            background: var(--copper);
            border-radius: 50%;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.6;
        }

        /* Hero content */
        .hero-eyebrow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.7rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 1.75rem;
            position: relative;
            z-index: 2;
            opacity: 0;
            animation: fadeUp 0.7s ease forwards 0.1s;
        }

        .hero-title-overflow { overflow: hidden; position: relative; z-index: 2; }

        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(5.5rem, 18.5vw, 22rem);
            line-height: 0.83;
            letter-spacing: -0.015em;
            color: var(--ivory);
            transform: translateY(105%);
            animation: slideUp 1.1s cubic-bezier(0.16, 1, 0.3, 1) forwards 0.25s;
        }
        @keyframes slideUp { to { transform: translateY(0); } }

        .hero-sub-overflow { overflow: hidden; position: relative; z-index: 2; margin-top: 0.5rem; }

        .hero-sub {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(0.9rem, 2.2vw, 1.4rem);
            font-weight: 300;
            color: rgba(240,235,227,0.55);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            transform: translateY(100%);
            animation: slideUp 1.1s cubic-bezier(0.16, 1, 0.3, 1) forwards 0.45s;
        }

        .hero-bottom {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px solid var(--line);
            padding-top: 1.75rem;
            margin-top: 3rem;
            position: relative; z-index: 2;
            opacity: 0;
            animation: fadeUp 0.8s ease forwards 0.85s;
        }

        .hero-desc {
            max-width: 340px;
            font-size: 0.95rem;
            line-height: 1.7;
            color: var(--muted);
            margin: 0;
        }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.9rem;
            color: var(--ivory);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.04em;
            transition: gap 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .hero-cta:hover { gap: 1.4rem; }

        .hero-cta .cta-circle {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: var(--copper);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: background 0.25s, transform 0.25s;
        }
        .hero-cta:hover .cta-circle { background: var(--ivory); transform: rotate(45deg); }
        .hero-cta .cta-circle svg { width: 18px; height: 18px; color: var(--ivory); transition: color 0.25s; }
        .hero-cta:hover .cta-circle svg { color: var(--ink); }

        /* ─── TICKER ────────────────────────────── */
        .ticker {
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            background: var(--ink-2);
            padding: 1.1rem 0;
            overflow: hidden;
        }

        .ticker-track {
            display: flex;
            gap: 0;
            animation: marquee 25s linear infinite;
            white-space: nowrap;
            width: max-content;
        }

        .ticker-item {
            display: inline-flex;
            align-items: center;
            gap: 2rem;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.05rem;
            letter-spacing: 0.18em;
            color: rgba(240,235,227,0.3);
            padding: 0 2rem;
        }

        .ticker-sep { color: var(--copper); font-size: 0.85em; }

        @keyframes marquee {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }

        /* ─── MANIFESTO ─────────────────────────── */
        .manifesto {
            padding: 9rem 2.5rem 8rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .manifesto-inner {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 4rem;
            align-items: end;
        }

        .manifesto-text {
            font-size: clamp(2rem, 4.5vw, 3.75rem);
            line-height: 1.25;
            font-weight: 300;
            color: rgba(240,235,227,0.88);
        }

        .manifesto-text em {
            font-style: normal;
            color: var(--copper);
        }

        .manifesto-aside {
            font-size: 1rem;
            line-height: 1.75;
            color: var(--muted);
            padding-bottom: 0.5rem;
            border-left: 1px solid rgba(200,121,65,0.3);
            padding-left: 1.5rem;
        }

        /* ─── FEATURES ──────────────────────────── */
        .features {
            padding: 0 2.5rem 9rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .features-label {
            font-size: 0.68rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: rgba(240,235,227,0.28);
            margin-bottom: 2.5rem;
        }

        .feature {
            display: grid;
            grid-template-columns: 110px 1fr;
            gap: 2.5rem;
            align-items: center;
            padding: 2.25rem 0;
            border-top: 1px solid var(--line);
            transition: border-color 0.35s;
            cursor: default;
        }
        .feature:last-child { border-bottom: 1px solid var(--line); }
        .feature:hover { border-color: rgba(200,121,65,0.38); }

        .feature-num {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3.75rem;
            line-height: 1;
            color: rgba(240,235,227,0.12);
            transition: color 0.35s;
        }
        .feature:hover .feature-num { color: var(--copper); }

        .feature-title {
            font-size: clamp(1.25rem, 2.5vw, 1.75rem);
            font-weight: 700;
            color: var(--ivory);
            margin-bottom: 0.5rem;
            transition: color 0.35s;
        }
        .feature:hover .feature-title { color: var(--copper); }

        .feature-desc {
            font-size: 0.95rem;
            line-height: 1.7;
            color: var(--muted);
            max-width: 560px;
            margin: 0;
        }

        /* ─── STEPS ─────────────────────────────── */
        .steps {
            background: var(--ink-2);
            border-top: 1px solid var(--line);
            padding: 7rem 2.5rem 8rem;
        }

        .steps-inner {
            max-width: 1400px;
            margin: 0 auto;
        }

        .steps-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 5rem;
        }

        .steps-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(3rem, 7vw, 6rem);
            line-height: 0.9;
            color: var(--ivory);
        }

        .steps-label {
            font-size: 0.68rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: var(--muted);
            padding-bottom: 0.5rem;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3rem;
        }

        .step {
            padding-top: 2rem;
            border-top: 1px solid var(--line);
        }

        .step-num {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1rem;
            letter-spacing: 0.1em;
            color: var(--copper);
            margin-bottom: 1.5rem;
        }

        .step-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--ivory);
            margin-bottom: 0.75rem;
        }

        .step-desc {
            font-size: 0.92rem;
            line-height: 1.75;
            color: var(--muted);
            margin: 0;
        }

        /* ─── FINAL CTA ─────────────────────────── */
        .final-cta {
            min-height: 85vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 6rem 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .final-cta::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 60% 60% at 50% 50%, rgba(200,121,65,0.08) 0%, transparent 75%);
            pointer-events: none;
        }

        .final-label {
            font-size: 0.68rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: rgba(240,235,227,0.3);
            margin-bottom: 2.5rem;
        }

        .final-title-wrap { overflow: hidden; }

        .final-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(6rem, 20vw, 24rem);
            line-height: 0.82;
            color: var(--ivory);
            transform: translateY(100%);
            transition: transform 1.1s cubic-bezier(0.16, 1, 0.3, 1);
            margin-bottom: 3.5rem;
        }

        .final-title.is-revealed { transform: translateY(0); }

        .final-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.9rem;
            background: var(--copper);
            color: var(--ivory);
            padding: 1.15rem 2.75rem;
            border-radius: 999px;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-decoration: none;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .final-btn:hover {
            background: var(--ivory);
            color: var(--ink);
            transform: scale(1.05);
            box-shadow: 0 16px 48px rgba(200,121,65,0.25);
        }

        .final-btn .g-icon { width: 20px; height: 20px; flex-shrink: 0; }
        .final-btn .user-icon { width: 20px; height: 20px; flex-shrink: 0; color: currentColor; }

        /* ─── FOOTER ────────────────────────────── */
        .footer {
            padding: 1.75rem 2.5rem;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            color: rgba(240,235,227,0.25);
        }

        /* ─── SCROLL REVEAL ─────────────────────── */
        .js-reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1),
                        transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .js-reveal.is-revealed { opacity: 1; transform: translateY(0); }

        .js-reveal-delay-1 { transition-delay: 0.1s; }
        .js-reveal-delay-2 { transition-delay: 0.2s; }
        .js-reveal-delay-3 { transition-delay: 0.3s; }

        /* ─── ANIMATIONS ────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── RESPONSIVE ────────────────────────── */
        @media (max-width: 1024px) {
            .manifesto-inner {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .manifesto-aside { border-left: none; padding-left: 0; border-top: 1px solid rgba(200,121,65,0.3); padding-top: 1.5rem; }
            .steps-grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
        }

        @media (max-width: 768px) {
            body { cursor: auto; }
            .cursor, .cursor-ring { display: none; }

            .header { padding: 1.25rem 1.25rem; }
            .header.scrolled { padding: 0.9rem 1.25rem; }

            .hero { padding: 2rem 1.25rem 3rem; }
            .hero-eyebrow { font-size: 0.6rem; }

            .hero-bottom {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.75rem;
            }

            .manifesto { padding: 5rem 1.25rem 4rem; }
            .features { padding: 0 1.25rem 5rem; }
            .feature { grid-template-columns: 70px 1fr; gap: 1.5rem; }
            .feature-num { font-size: 2.5rem; }

            .steps { padding: 5rem 1.25rem 5rem; }
            .steps-header { flex-direction: column; align-items: flex-start; gap: 1rem; margin-bottom: 3rem; }
            .steps-grid { grid-template-columns: 1fr; gap: 2rem; }

            .final-cta { padding: 5rem 1.25rem; min-height: 70vh; }

            .footer { flex-direction: column; gap: 0.5rem; text-align: center; padding: 1.5rem 1.25rem; }
        }

        @media (max-width: 480px) {
            .hero-bottom { margin-top: 2rem; padding-top: 1.25rem; }
            .header-btn span { display: none; }
            .header-btn { padding: 0.6rem 1rem; }
        }
    </style>
</head>
<body>

    <!-- Custom Cursor -->
    <div class="cursor" id="cursor"></div>
    <div class="cursor-ring" id="cursorRing"></div>

    <!-- Header -->
    <header class="header" id="header">
        <a href="/" class="logo">UNIVERSON</a>
        <a href="<?= htmlspecialchars($buttonUrl) ?>" class="header-btn">
            <?php if ($showGoogleIcon): ?>
            <svg class="g-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            <?php endif; ?>
            <span><?= htmlspecialchars($buttonText) ?></span>
        </a>
    </header>

    <!-- Hero -->
    <section class="hero">

        <!-- Vinyl record background -->
        <div class="vinyl-bg" aria-hidden="true">
            <div class="vinyl-ring vr1"></div>
            <div class="vinyl-ring vr2"></div>
            <div class="vinyl-ring vr3"></div>
            <div class="vinyl-ring vr4"></div>
            <div class="vinyl-ring vr5"></div>
            <div class="vinyl-dot"></div>
        </div>

        <div class="hero-eyebrow">
            <span>Votre musée musical personnel</span>
            <span><?= date('Y') ?></span>
        </div>

        <div class="hero-title-overflow">
            <h1 class="hero-title">UNIVERSON</h1>
        </div>

        <div class="hero-sub-overflow">
            <p class="hero-sub">Exposez vos albums comme des œuvres d'art</p>
        </div>

        <div class="hero-bottom">
            <p class="hero-desc">Transformez votre collection musicale<br>en une exposition artistique captivante.</p>
            <a href="<?= htmlspecialchars($buttonUrl) ?>" class="hero-cta">
                <span>Commencer</span>
                <div class="cta-circle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
        </div>
    </section>

    <!-- Ticker marquee -->
    <div class="ticker" aria-hidden="true">
        <div class="ticker-track">
            <span class="ticker-item">EXPOSER <span class="ticker-sep">×</span></span>
            <span class="ticker-item">COLLECTER <span class="ticker-sep">×</span></span>
            <span class="ticker-item">PARTAGER <span class="ticker-sep">×</span></span>
            <span class="ticker-item">DÉCOUVRIR <span class="ticker-sep">×</span></span>
            <span class="ticker-item">ARCHIVER <span class="ticker-sep">×</span></span>
            <span class="ticker-item">EXPOSER <span class="ticker-sep">×</span></span>
            <span class="ticker-item">COLLECTER <span class="ticker-sep">×</span></span>
            <span class="ticker-item">PARTAGER <span class="ticker-sep">×</span></span>
            <span class="ticker-item">DÉCOUVRIR <span class="ticker-sep">×</span></span>
            <span class="ticker-item">ARCHIVER <span class="ticker-sep">×</span></span>
        </div>
    </div>

    <!-- Manifesto -->
    <section class="manifesto">
        <div class="manifesto-inner">
            <p class="manifesto-text js-reveal">
                Chaque album est une <em>œuvre d'art.</em><br>
                Chaque collection raconte<br>
                une <em>histoire unique.</em>
            </p>
            <p class="manifesto-aside js-reveal js-reveal-delay-2">
                Universon est l'espace où votre passion musicale prend vie. Organisez, exposez et partagez vos albums préférés dans une galerie personnelle qui vous ressemble.
            </p>
        </div>
    </section>

    <!-- Features -->
    <section class="features">
        <div class="features-label js-reveal">— FONCTIONNALITÉS</div>

        <div class="feature js-reveal">
            <span class="feature-num">01</span>
            <div>
                <h3 class="feature-title">Galerie Personnelle</h3>
                <p class="feature-desc">Présentez vos albums dans une exposition élégante et immersive, comme des tableaux dans un musée d'art contemporain.</p>
            </div>
        </div>

        <div class="feature js-reveal">
            <span class="feature-num">02</span>
            <div>
                <h3 class="feature-title">Intégration Spotify</h3>
                <p class="feature-desc">Accédez à des millions d'albums et enrichissez votre collection musicale grâce à la bibliothèque Spotify.</p>
            </div>
        </div>

        <div class="feature js-reveal">
            <span class="feature-num">03</span>
            <div>
                <h3 class="feature-title">Profil Public</h3>
                <p class="feature-desc">Obtenez votre URL personnalisée (@username) et partagez votre univers musical avec qui vous voulez, quand vous voulez.</p>
            </div>
        </div>

        <div class="feature js-reveal">
            <span class="feature-num">04</span>
            <div>
                <h3 class="feature-title">Personnalisation Totale</h3>
                <p class="feature-desc">Organisez votre collection par coups de cœur, albums les plus écoutés, et ajoutez vos notes personnelles.</p>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="steps">
        <div class="steps-inner">
            <div class="steps-header">
                <h2 class="steps-title js-reveal">COMMENT<br>ÇA MARCHE</h2>
                <span class="steps-label js-reveal">— TROIS ÉTAPES</span>
            </div>
            <div class="steps-grid">
                <div class="step js-reveal">
                    <div class="step-num">— 01</div>
                    <h3 class="step-title">Connectez-vous</h3>
                    <p class="step-desc">Créez votre compte en quelques secondes avec Google. Simple, rapide, sécurisé.</p>
                </div>
                <div class="step js-reveal js-reveal-delay-1">
                    <div class="step-num">— 02</div>
                    <h3 class="step-title">Ajoutez vos albums</h3>
                    <p class="step-desc">Recherchez vos albums favoris via Spotify et construisez votre collection unique.</p>
                </div>
                <div class="step js-reveal js-reveal-delay-2">
                    <div class="step-num">— 03</div>
                    <h3 class="step-title">Partagez votre univers</h3>
                    <p class="step-desc">Votre profil public est prêt. Inspirez d'autres passionnés de musique avec vos découvertes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta">
        <div class="final-label js-reveal">— REJOINDRE UNIVERSON</div>
        <div class="final-title-wrap">
            <h2 class="final-title" id="finalTitle">CRÉEZ<br>VOTRE<br>MUSÉE</h2>
        </div>
        <a href="<?= htmlspecialchars($buttonUrl) ?>" class="final-btn js-reveal js-reveal-delay-1">
            <?php if ($showGoogleIcon): ?>
            <svg class="g-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            <?php else: ?>
            <svg class="user-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <?php endif; ?>
            <span><?= htmlspecialchars($buttonText) ?></span>
        </a>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <span>© <?= date('Y') ?> Universon</span>
        <span>Transformez votre passion musicale en art</span>
    </footer>

    <script>
        /* ── Custom cursor ── */
        const cur = document.getElementById('cursor');
        const ring = document.getElementById('cursorRing');
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

        /* ── Scroll reveal ── */
        const reveals = document.querySelectorAll('.js-reveal');
        const revealObs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('is-revealed');
                    revealObs.unobserve(e.target);
                }
            });
        }, { threshold: 0.12 });
        reveals.forEach(el => revealObs.observe(el));

        /* Final title uses a separate observer (clip-path variant) */
        const finalTitle = document.getElementById('finalTitle');
        const titleObs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    finalTitle.classList.add('is-revealed');
                    titleObs.unobserve(e.target);
                }
            });
        }, { threshold: 0.1 });
        if (finalTitle) titleObs.observe(finalTitle);

        /* ── Sticky header ── */
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 60);
        }, { passive: true });
    </script>
</body>
</html>
