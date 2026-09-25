/* ============================================================================
   Universon — contenu textuel, locale « fr ».

   Toute chaîne visible par l'utilisateur vit ici, et nulle part ailleurs.
   Traduire l'application = dupliquer ce fichier en `strings.en.js`, traduire
   les valeurs, et changer le <script> chargé. Aucune balise à rouvrir.

   Convention de clés : <zone>.<élément>. Les valeurs peuvent contenir des
   variables entre accolades — {count}, {n}, {pseudo} — remplies par t().
   ========================================================================= */

window.UNIVERSON_STRINGS = {

    /* ---- Marque ---------------------------------------------------- */
    'brand.wordmark'        : 'universon',
    'brand.dot'             : '.',
    'brand.domain'          : 'universon.fr',

    /* ---- Navigation ------------------------------------------------ */
    'nav.explore'           : 'Explorer',
    'nav.login'             : 'Connexion',
    'nav.logout'            : 'Déconnexion',

    /* ---- Accueil : bandeau d'ouverture ------------------------------ */
    'hero.eyebrow'          : 'Le Letterboxd des albums',
    'hero.title'            : "Ton mur d'albums, en un lien.",
    'hero.lede'             : "Ajoute les disques que tu écoutes, range-les en trois rayons, balance ton @ en bio. C'est tout.",
    'hero.cta.primary'      : 'Créer mon mur',
    'hero.cta.secondary'    : 'Voir @godwin →',

    /* ---- Accueil : compteurs ---------------------------------------- */
    'counters.shelves.value': '3',
    'counters.shelves.label': 'Rayons',
    'counters.albums.value' : '∞',
    'counters.albums.label' : 'Albums',
    'counters.link.value'   : '1',
    'counters.link.label'   : 'Lien à partager',
    'counters.algo.value'   : '0',
    'counters.algo.label'   : 'Algorithme',

    /* ---- Accueil : mode d'emploi ------------------------------------ */
    'steps.title'           : 'Trois étapes,\ndeux minutes.',
    'steps.aside'           : 'Comment ça marche',
    'steps.1.index'         : '01',
    'steps.1.title'         : 'Connecte-toi',
    'steps.1.body'          : 'Avec Google. Pas de mot de passe à retenir, pas de formulaire.',
    'steps.2.index'         : '02',
    'steps.2.title'         : 'Remplis le mur',
    'steps.2.body'          : 'Tape trois lettres, la pochette arrive. Range en coups de cœur, plus écoutés, plaisirs coupables.',
    'steps.3.index'         : '03',
    'steps.3.title'         : 'Partage ton @',
    'steps.3.body'          : 'universon.fr/@toi. Visible sans compte, lisible partout.',

    /* ---- Profil ----------------------------------------------------- */
    'profile.share'         : 'Copier le lien',
    'profile.enlarge'       : 'Voir en grand',
    'profile.stat.albums'   : 'Albums',
    'profile.stat.shelves'  : 'Rayons',
    'profile.stat.since'    : 'Depuis',

    /* ---- Rayons (miroir de la table `album_categories`) -------------- */
    'category.favorite'         : 'Coups de cœur',
    'category.most_played'      : 'Les plus écoutés',
    'category.guilty_pleasure'  : 'Plaisirs coupables',
    'category.meta'             : '{count} albums · rayon {n}',
    'category.empty'            : 'Ce rayon est vide — assume, ajoute quelque chose',

    /* ---- Tableau de bord -------------------------------------------- */
    'dashboard.visibility.title': 'Profil public',
    'dashboard.visibility.hint' : 'universon.fr/@{pseudo}',
    'dashboard.add.title'       : 'Ajouter un album',
    'dashboard.add.context'     : 'Rayon · {category}',
    'dashboard.add.placeholder' : "Nom de l'album",
    'dashboard.add.cancel'      : 'Annuler',
    'dashboard.add.confirm'     : 'Ajouter',
    'dashboard.remove'          : 'Retirer',

    /* ---- Messages de statut ----------------------------------------- */
    'notify.added'          : 'Album ajouté aux {category}',
    'notify.removed'        : 'Album retiré des {category}',
    'notify.offline'        : 'Erreur de connexion',
    'notify.close'          : 'Fermer',

    /* ---- Pied de page ------------------------------------------------ */
    'footer.big'            : 'Ton univers musical,\nen un lien',
    'footer.rights'         : '© {year} Universon'
};

/* ----------------------------------------------------------------------------
   t(clé, variables) — rend une chaîne.

   t('hero.title')                              → "Ton mur d'albums, en un lien."
   t('category.meta', { count: 16, n: '01' })   → "16 albums · rayon 01"

   Une clé absente renvoie la clé elle-même entre crochets : le trou est
   visible à l'écran plutôt que silencieux.
   ------------------------------------------------------------------------- */
window.t = function (key, vars) {
    var raw = window.UNIVERSON_STRINGS[key];
    if (raw === undefined) { return '[' + key + ']'; }
    if (!vars) { return raw; }
    return raw.replace(/\{(\w+)\}/g, function (match, name) {
        return Object.prototype.hasOwnProperty.call(vars, name) ? vars[name] : match;
    });
};

/* ----------------------------------------------------------------------------
   applyStrings(racine) — remplit le DOM.

   <h1 data-t="hero.title"></h1>
   <span data-t="category.meta" data-t-count="16" data-t-n="01"></span>

   Les attributs data-t-* deviennent les variables. Un saut de ligne dans la
   chaîne devient un <br>, ce qui laisse la césure des titres au fichier de
   contenu plutôt qu'au gabarit.
   ------------------------------------------------------------------------- */
window.applyStrings = function (root) {
    var scope = root || document;
    var nodes = scope.querySelectorAll('[data-t]');
    for (var i = 0; i < nodes.length; i++) {
        var node = nodes[i];
        var vars = {};
        for (var j = 0; j < node.attributes.length; j++) {
            var attr = node.attributes[j];
            if (attr.name.indexOf('data-t-') === 0) {
                vars[attr.name.slice(7)] = attr.value;
            }
        }
        var value = window.t(node.getAttribute('data-t'), vars);
        node.innerHTML = value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/\n/g, '<br>');
    }
};

document.addEventListener('DOMContentLoaded', function () { window.applyStrings(); });
