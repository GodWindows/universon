<?php
/* ---------------------------------------------------------------------------
   Universon — contenu textuel, locale « fr » (docs/DESIGN.md § 9.4).

   Convention de clé : zone.element. Une clé absente rend « [ma.cle] » : le trou
   est visible à l'écran, jamais silencieux.

   Variables nommées entre accolades simples ; la position n'est jamais
   significative. Les retours à la ligne des titres vivent ici, sous forme de
   \n convertis en <br> par e() : aucun <br> n'est écrit dans un gabarit.

   Tutoiement partout. Aucun emoji. Aucun tiret cadratin employé comme liant.
   --------------------------------------------------------------------------- */

return [

    /* --- Marque ------------------------------------------------------- */
    'brand.wordmark'            => 'universon',
    'brand.dot'                 => '.',
    'brand.domain'              => 'universon.fr',

    /* --- Navigation --------------------------------------------------- */
    'nav.explore'               => 'Explorer',
    'nav.login'                 => 'Connexion',
    'nav.logout'                => 'Déconnexion',
    'nav.logging_out'           => 'Déconnexion...',

    /* --- Bandeau d'accueil -------------------------------------------- */
    'hero.eyebrow'              => 'Le Letterboxd des albums',
    'hero.title'                => "Ton mur d'albums, en un lien.",
    'hero.lede'                 => "Ajoute les disques que tu écoutes, range-les en trois rayons, balance ton @ en bio. C'est tout.",
    'hero.cta.primary'          => 'Créer mon mur',
    'hero.cta.secondary'        => 'Voir @godwin →',

    /* --- Compteurs ----------------------------------------------------- */
    'counters.shelves.value'    => '3',
    'counters.shelves.label'    => 'Rayons',
    'counters.albums.value'     => '∞',
    'counters.albums.label'     => 'Albums',
    'counters.link.value'       => '1',
    'counters.link.label'       => 'Lien à partager',
    'counters.algo.value'       => '0',
    'counters.algo.label'       => 'Algorithme',

    /* --- Trois étapes -------------------------------------------------- */
    'steps.title'               => "Trois étapes,\ndeux minutes.",
    'steps.aside'               => 'Comment ça marche',
    'steps.1.index'             => '01',
    'steps.1.title'             => 'Connecte-toi',
    'steps.1.body'              => 'Avec Google. Pas de mot de passe à retenir, pas de formulaire.',
    'steps.2.index'             => '02',
    'steps.2.title'             => 'Remplis le mur',
    'steps.2.body'              => 'Tape trois lettres, la pochette arrive. Range en coups de cœur, plus écoutés, plaisirs coupables.',
    'steps.3.index'             => '03',
    'steps.3.title'             => 'Partage ton @',
    'steps.3.body'              => 'universon.fr/@toi. Visible sans compte, lisible partout.',

    /* --- Profil -------------------------------------------------------- */
    'profile.share'             => 'Copier le lien',
    'profile.enlarge'           => 'Voir en grand',
    'profile.stat.albums'       => 'Albums',
    'profile.stat.shelves'      => 'Rayons',
    'profile.stat.since'        => 'Depuis',
    'profile.avatar.alt'        => 'Photo de profil',
    'profile.bio.title'         => 'Bio',
    'profile.bio.empty'         => "Aucune bio pour l'instant.",
    'profile.bio.placeholder'   => 'Parle de ce que tu écoutes.',
    'profile.bio.edit'          => 'Modifier',
    'profile.bio.save'          => 'Sauvegarder',
    'profile.bio.saving'        => 'Sauvegarde...',
    'profile.bio.cancel'        => 'Annuler',

    /* --- Catégories ---------------------------------------------------- */
    'category.favorites'         => 'Coups de cœur',
    'category.most_played'      => 'Les plus écoutés',
    'category.guilty_pleasure'  => 'Plaisirs coupables',
    'category.firstloves'  => 'Mes tout premiers amours',
    'category.meta'             => '{count} albums · rayon {n}',
    'category.empty'            => 'Ce rayon est vide, quelqu’un a du mal à assumer...',
    'category.add'              => 'Ajouter un album',

    /* --- Tableau de bord ----------------------------------------------- */
    'dashboard.visibility.title'   => 'Profil public',
    'dashboard.visibility.hint'    => 'universon.fr/@{pseudo}',
    'dashboard.visibility.public'  => 'Public',
    'dashboard.visibility.private' => 'Privé',
    'dashboard.add.title'          => 'Ajouter un album',
    'dashboard.add.context'        => 'Rayon · {category}',
    'dashboard.add.label'          => "Nom de l'album",
    'dashboard.add.placeholder'    => "Nom de l'album",
    'dashboard.add.cancel'         => 'Annuler',
    'dashboard.add.confirm'        => 'Ajouter',
    'dashboard.add.saving'         => 'Ajout...',
    'dashboard.add.select'         => 'Sélectionner',
    'dashboard.add.empty'          => "Tape le nom d'un album.",
    'dashboard.add.too_long'       => "Ce nom d'album est trop long.",
    'dashboard.remove'             => 'Retirer',
    'dashboard.remove.confirm'     => 'Retirer cet album des {category} ?',

    /* --- Pseudo -------------------------------------------------------- */
    'pseudo.title'              => 'Choisis ton pseudo',
    'pseudo.body'               => "Pour rendre ton profil public, il te faut un pseudo unique.",
    'pseudo.label'              => 'Pseudo',
    'pseudo.placeholder'        => 'ton_pseudo',
    'pseudo.save'               => 'Enregistrer',
    'pseudo.saving'             => 'Enregistrement...',
    'pseudo.cancel'             => 'Annuler',
    'pseudo.checking'           => 'Vérification...',
    'pseudo.available'          => 'Pseudo disponible !',
    'pseudo.taken'              => 'Pseudo déjà pris',
    'pseudo.too_short'          => 'Le pseudo doit contenir au moins 3 caractères',
    'pseudo.too_long'           => 'Le pseudo ne peut pas dépasser 45 caractères',
    'pseudo.check_error'        => 'Erreur de vérification',
    'pseudo.saved'              => 'Pseudo enregistré !',

    /* --- Notifications -------------------------------------------------- */
    'notify.added'              => 'Album ajouté aux {category}',
    'notify.removed'            => 'Album retiré des {category}',
    'notify.bio_saved'          => 'Bio mise à jour !',
    'notify.link_copied'        => 'Lien du profil copié !',
    'notify.link_prompt'        => 'Copie le lien',
    'notify.visibility'         => 'Profil maintenant {state} !',
    'notify.offline'            => 'Erreur de connexion',
    'notify.error'              => 'Erreur, réessaie.',
    'notify.close'              => 'Fermer',

    /* --- États d'erreur -------------------------------------------------- */
    'error.notfound.title'      => 'Profil introuvable',
    'error.notfound.body'       => "Le profil @{pseudo} n'existe pas ou n'est plus disponible.",
    'error.private.title'       => 'Profil privé',
    'error.private.body'        => "Ce profil est privé. Son propriétaire ne l'a pas encore ouvert.",
    'error.badrequest.title'    => 'Requête invalide',
    'error.badrequest.body'     => "Cette adresse ne correspond à aucun profil.",
    'error.back'                => "Retour à l'accueil",

    /* --- Connexion ------------------------------------------------------- */
    'login.tagline'             => "Ton mur d'albums, en un lien.",
    'login.google'              => 'Continuer avec Google',
    'login.terms'               => "En te connectant, tu acceptes les conditions d'utilisation.",

    /* --- Pied de page ----------------------------------------------------- */
    'footer.big'                => "Ton univers musical,\nen un lien",
    'footer.rights'             => '© {year} Universon',
];
