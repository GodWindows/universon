<?php
/* ---------------------------------------------------------------------------
   Universon — rendu du contenu textuel (docs/DESIGN.md § 9.2).

   Aucune chaîne visible n'est écrite dans un gabarit : tout passe par t()/e().
   Traduire l'application consiste à dupliquer lang/fr.php et à en traduire les
   valeurs, sans rouvrir une seule balise.
   --------------------------------------------------------------------------- */

function t(string $key, array $vars = []): string
{
    static $strings = null;
    if ($strings === null) {
        $locale  = defined('APP_LOCALE') ? APP_LOCALE : 'fr';
        $path    = __DIR__ . '/../lang/' . $locale . '.php';
        $strings = is_file($path) ? require $path : [];
    }

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
