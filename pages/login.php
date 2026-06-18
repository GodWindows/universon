<?php
    require __DIR__.  '/../vendor/autoload.php';
    require __DIR__.  '/../env_data.php'; // create this file after fetching the github code and store your client-id, client-secret and redirect uri in it
    require_once __DIR__.  '/../util/functions.php';

    $client = new Google\Client;
    $client->setClientId($clientID);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirect_uri);
    $client->addScope("email");
    $client->addScope("profile ");

    $url = $client->createAuthUrl();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Connectez-vous à Universon pour créer et gérer votre collection musicale personnelle. Organisez vos albums préférés et partagez votre profil musical.">
    <meta name="keywords" content="universon, connexion, login, musique, collection musicale, profil musical">
    <meta name="author" content="Universon">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://universon.fr/pages/login.php">
    <meta property="og:title" content="Universon - Connexion">
    <meta property="og:description" content="Créez votre univers musical personnel avec Universon.">
    <meta property="og:site_name" content="Universon">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="https://universon.fr/pages/login.php">
    <meta name="twitter:title" content="Universon - Connexion">
    <meta name="twitter:description" content="Créez votre univers musical personnel avec Universon.">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#F1E9DA">

    <title><?= $site_title ?> — Connexion</title>
    <link rel="icon" href="/img/logo.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..900;1,9..144,400..900&family=EB+Garamond:ital,wght@0,400..600;1,400..600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --craie:#F1E9DA; --velin:#FBF6EC; --stuc:#E5DAC4; --marbre:#D8D2C4;
            --sepia:#2B2118; --sepia-doux:#5A4B38; --muted:rgba(43,33,24,.45);
            --or:#B5912F; --or-clair:#E6C86E; --or-ombre:#856321;
            --grenat:#7A2233; --grenat-fonce:#511522;
            --filet:rgba(43,33,24,.12); --filet-or:rgba(181,145,47,.35);
            --ombre-cadre:rgba(43,33,24,.18); --halo:rgba(230,200,110,.18);
            --r-sm:6px; --r-panneau:26px; --r-medaillon:999px;
            --dorure:linear-gradient(135deg,#856321,#B5912F 38%,#E6C86E 55%,#B5912F 72%,#856321);
            --velours:linear-gradient(180deg,#7A2233,#5E1A28);
            --verriere:radial-gradient(125% 90% at 50% -8%,#FBF6EC 0%,#F1E9DA 45%,#E5DAC4 100%);
            --spot:radial-gradient(ellipse 60% 55% at 50% 45%,var(--halo) 0%,transparent 70%);
            --font-display:'Fraunces',serif;
            --font-body:'EB Garamond',serif;
        }

        body {
            font-family: var(--font-body);
            background: var(--craie);
            background-image: var(--verriere);
            color: var(--sepia);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 1.5rem; overflow: hidden; position: relative;
        }

        /* Grain de plâtre */
        body::after {
            content: ''; position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.7' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
            opacity: 0.045; mix-blend-mode: multiply; pointer-events: none; z-index: 1;
        }
        body::before { content: ''; position: fixed; inset: 0; background: var(--spot); pointer-events: none; z-index: 0; }

        /* Volutes & fleurons flottants */
        .music-elements { position: fixed; inset: 0; pointer-events: none; z-index: 0; color: var(--or); }
        .music-note {
            position: absolute; opacity: .14; font-family: var(--font-display); font-style: italic;
            animation: flotte 16s ease-in-out infinite;
        }
        .music-note::before { content: '\10086'; font-size: inherit; }
        .music-note:nth-child(1) { top: 14%; left: 12%; font-size: 3.5rem; animation-delay: 0s; }
        .music-note:nth-child(2) { top: 68%; left: 18%; font-size: 2.5rem; animation-delay: 2s; }
        .music-note:nth-child(3) { top: 22%; right: 14%; font-size: 3rem; animation-delay: 4s; }
        .music-note:nth-child(4) { top: 72%; right: 16%; font-size: 4rem; animation-delay: 6s; }
        @keyframes flotte { 0%,100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-18px) rotate(8deg); } }

        /* ─── GUICHET / BILLETTERIE ─── */
        .login-container { position: relative; z-index: 2; width: 100%; max-width: 440px; }
        .login-card {
            position: relative; background: var(--velin);
            border: 1px solid var(--or); border-radius: var(--r-panneau);
            padding: 3rem 2.75rem; text-align: center;
            box-shadow: 0 30px 80px rgba(43,33,24,.28);
        }
        /* Volutes d'angle (cartouche) */
        .login-card::before, .login-card::after {
            content: ''; position: absolute; top: 14px; width: 36px; height: 36px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Cpath d='M38 2C18 2 2 18 2 38' fill='none' stroke='%23B5912F' stroke-width='1.4'/%3E%3Cpath d='M2 38c0-8 6-14 14-14 6 0 10 4 10 9' fill='none' stroke='%23B5912F' stroke-width='1.4'/%3E%3Ccircle cx='29' cy='11' r='2' fill='%23B5912F'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-size: contain; opacity: .85; pointer-events: none;
        }
        .login-card::before { left: 14px; }
        .login-card::after { right: 14px; transform: scaleX(-1); }

        .login-header { margin-bottom: 2.25rem; }
        .brand-icon {
            width: 76px; height: 76px; margin: 0 auto 1.5rem;
            border-radius: 50%; background: var(--craie);
            border: 1px solid var(--or);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 0 4px var(--velin), 0 10px 26px var(--ombre-cadre);
        }
        .brand-icon img { width: 2.5rem; height: 2.5rem; }

        .login-title {
            font-family: var(--font-display); font-weight: 600;
            font-size: 2.6rem; line-height: 1; letter-spacing: 0.01em; color: var(--sepia); margin-bottom: .75rem;
        }
        .login-subtitle { font-family: var(--font-display); font-style: italic; font-size: 1.05rem; color: var(--sepia-doux); }

        /* Ornement filet doré */
        .ornement { display: flex; align-items: center; justify-content: center; gap: .9rem; color: var(--or); margin: 1.5rem 0; }
        .ornement::before, .ornement::after { content: ''; height: 1px; width: 70px; }
        .ornement::before { background: linear-gradient(90deg, transparent, var(--or)); }
        .ornement::after { background: linear-gradient(90deg, var(--or), transparent); }

        /* Jeton billetterie */
        .btn-google {
            display: inline-flex; align-items: center; justify-content: center; gap: .7rem; width: 100%;
            background: var(--velin); color: var(--sepia);
            padding: .95rem 1.5rem; border: 1px solid var(--or); border-radius: var(--r-medaillon);
            font-family: var(--font-body); font-size: 1rem; font-weight: 600; letter-spacing: 0.02em;
            text-decoration: none; cursor: pointer; position: relative; overflow: hidden;
            box-shadow: 0 8px 22px var(--ombre-cadre); transition: transform .3s, box-shadow .3s, background .3s, color .3s;
        }
        .btn-google::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(110deg, transparent 30%, rgba(230,200,110,.4) 50%, transparent 70%);
            transform: translateX(-120%); transition: transform .7s;
        }
        .btn-google:hover { transform: translateY(-1px); box-shadow: 0 14px 34px var(--ombre-cadre); }
        .btn-google:hover::after { transform: translateX(120%); }
        .btn-google .g-jeton {
            width: 26px; height: 26px; flex-shrink: 0; background: var(--craie);
            border: 1px solid var(--filet-or); border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-google .g-jeton svg { width: 15px; height: 15px; }

        .login-terms { margin-top: 1.5rem; font-size: .82rem; color: var(--sepia-doux); display: flex; align-items: center; justify-content: center; gap: .4rem; }
        .login-terms .fleuron { color: var(--or); }

        @media (max-width: 480px) {
            .login-card { padding: 2.5rem 1.75rem; }
            .login-title { font-size: 2.1rem; }
        }
    </style>
</head>
<body>
    <!-- Ornements flottants (volutes / fleurons) -->
    <div class="music-elements" aria-hidden="true">
        <span class="music-note"></span>
        <span class="music-note"></span>
        <span class="music-note"></span>
        <span class="music-note"></span>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-icon">
                    <img src="/img/logo.ico" alt="Logo Universon">
                </div>
                <h1 class="login-title"><?= $site_title ?></h1>
                <p class="login-subtitle">Créez votre collection musicale personnalisée</p>
            </div>

            <div class="ornement" aria-hidden="true"><span>&#10086;</span></div>

            <a href="<?=$url?>" class="btn btn-google">
                <span class="g-jeton">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                </span>
                <span>Continuer avec Google</span>
            </a>

            <p class="login-terms">
                <span class="fleuron">&#10086;</span>
                En vous connectant, vous acceptez nos conditions d'utilisation
            </p>
        </div>
    </div>
</body>
</html>
