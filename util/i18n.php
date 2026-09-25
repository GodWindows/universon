<?php
/* ---------------------------------------------------------------------------
   Universon — rendu du contenu textuel (docs/DESIGN.md § 9.2).

   Aucune chaîne visible n'est écrite dans un gabarit : tout passe par t()/e().
   Traduire l'application consiste à dupliquer lang/fr.php et à en traduire les
   valeurs, puis à déclarer la locale dans APP_LOCALES, sans rouvrir une seule
   balise.
   --------------------------------------------------------------------------- */

/* Locales disponibles : code => nom de la langue, écrit dans cette langue. */
const APP_LOCALES = [
    'fr'  => 'Français',
    'en'  => 'English',
    'fon' => 'Fɔ̀ngbè',
];
const APP_DEFAULT_LOCALE = 'fr';

/* La locale vient de ?lang= (mémorisée dans un cookie un an), sinon du cookie,
   sinon de la locale par défaut. Ce fichier est inclus avant toute sortie :
   le cookie peut encore être posé. */
if (!defined('APP_LOCALE')) {
    $requested = $_GET['lang'] ?? null;
    if (is_string($requested) && isset(APP_LOCALES[$requested])) {
        setcookie('lang', $requested, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'samesite' => 'Lax',
        ]);
        define('APP_LOCALE', $requested);
    } elseif (isset($_COOKIE['lang']) && isset(APP_LOCALES[$_COOKIE['lang']])) {
        define('APP_LOCALE', $_COOKIE['lang']);
    } else {
        define('APP_LOCALE', APP_DEFAULT_LOCALE);
    }
}

function i18n_strings(): array
{
    static $strings = null;
    if ($strings === null) {
        $path    = __DIR__ . '/../lang/' . APP_LOCALE . '.php';
        $strings = is_file($path) ? require $path : [];
    }
    return $strings;
}

function t(string $key, array $vars = []): string
{
    $strings = i18n_strings();
    if (!array_key_exists($key, $strings)) {
        return '[' . $key . ']';
    }

    $value = $strings[$key];
    foreach ($vars as $name => $replacement) {
        $value = str_replace('{' . $name . '}', (string) $replacement, $value);
    }
    return $value;
}

/** Rendu échappé, avec les sauts de ligne convertis en <br>. */
function e(string $key, array $vars = []): string
{
    return nl2br(htmlspecialchars(t($key, $vars), ENT_QUOTES, 'UTF-8'), false);
}

/** Chaînes dont la clé commence par l'un des préfixes, en JSON prêt pour un
    <script> (window.UNIVERSON_I18N, lu par s() dans js/app.js). */
function i18n_json(array $prefixes): string
{
    $subset = array_filter(i18n_strings(), function ($key) use ($prefixes) {
        foreach ($prefixes as $prefix) {
            if (strncmp($key, $prefix, strlen($prefix)) === 0) {
                return true;
            }
        }
        return false;
    }, ARRAY_FILTER_USE_KEY);

    return json_encode($subset ?: new stdClass(),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/** Bouton langue (文A) : ouvre la liste des langues. Chaque lien recharge la page
    courante avec ?lang=, les autres paramètres de l'URL étant conservés. */
function lang_switcher(): string
{
    $path  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $items = '';
    foreach (APP_LOCALES as $code => $name) {
        $href    = $path . '?' . http_build_query(array_merge($_GET, ['lang' => $code]));
        $current = $code === APP_LOCALE ? ' aria-current="true"' : '';
        $items  .= '<li><a href="' . htmlspecialchars($href) . '" hreflang="' . $code . '" lang="' . $code . '"' . $current . '>'
                 . htmlspecialchars($name) . '</a></li>';
    }

    return '<details class="lang-switch">'
         . '<summary class="btn btn-line btn--sm" aria-label="' . htmlspecialchars(t('lang.switch')) . '" title="' . htmlspecialchars(t('lang.switch')) . '">'
         . '<svg class="lang-switch-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . '<path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/>'
         . '<path d="m22 22-5-10-5 10"/><path d="M14 18h6"/>'
         . '</svg>'
         . '<span>' . htmlspecialchars(strtoupper(APP_LOCALE)) . '</span>'
         . '</summary>'
         . '<ul class="lang-switch-menu">' . $items . '</ul>'
         . '</details>'
         . '<script>'
         . 'document.addEventListener("click",function(e){document.querySelectorAll("details.lang-switch[open]").forEach(function(d){if(!d.contains(e.target))d.removeAttribute("open");});});'
         . 'document.addEventListener("keydown",function(e){if(e.key==="Escape")document.querySelectorAll("details.lang-switch[open]").forEach(function(d){d.removeAttribute("open");});});'
         . '</script>';
}
