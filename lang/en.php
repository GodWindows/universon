<?php
/* ---------------------------------------------------------------------------
   Universon — text content, locale "en". Mirrors lang/fr.php key for key.

   Casual second person throughout, matching the French tutoiement.
   --------------------------------------------------------------------------- */

return [

    /* --- Brand -------------------------------------------------------- */
    'brand.wordmark'            => 'universon',
    'brand.dot'                 => '.',
    'brand.domain'              => 'universon.fr',

    /* --- Navigation --------------------------------------------------- */
    'nav.explore'               => 'Explore',
    'nav.login'                 => 'Log in',
    'nav.logout'                => 'Log out',
    'nav.logging_out'           => 'Logging out...',

    /* --- Language ----------------------------------------------------- */
    'lang.switch'               => 'Change language',

    /* --- Metadata (tab titles, SEO) ------------------------------------- */
    'meta.og_locale'            => 'en_US',
    'meta.landing.title'        => 'Universon — Show and share your music universe',
    'meta.landing.description'  => 'Create your music profile, show what you listen to and share your favorite albums in a single link. Universon puts your music universe on display.',
    'meta.landing.og_description' => 'Create your music profile and share your favorite albums in a single link.',
    'meta.landing.keywords'     => 'music profile, show my music, share my music, music collection, album collection, favorite albums, music universe',
    'meta.login.title'          => 'Log in',
    'meta.login.description'    => 'Log in to Universon to build and manage your personal music collection. Sort your favorite albums and share your music profile.',
    'meta.login.og_description' => 'Build your personal music universe with Universon.',
    'meta.login.keywords'       => 'universon, log in, login, music, music collection, music profile',
    'meta.dashboard.title'      => 'My profile',
    'meta.profile.description'  => "Discover @{pseudo}'s music collection on Universon.",
    'meta.profile.bio_fallback' => 'Discover my music collection on Universon',
    'meta.profile.keywords'     => 'music profile, {name}, @{pseudo}, music collection, favorite albums, share my music, music universe',

    /* --- Hero ---------------------------------------------------------- */
    'hero.eyebrow'              => 'Letterboxd for albums',
    'hero.title'                => 'Your album wall, in one link.',
    'hero.lede'                 => "Add the records you listen to, sort them onto four shelves, drop your @ in your bio. That's it.",
    'hero.cta.primary'          => 'Build my wall',
    'hero.cta.secondary'        => 'See @godwin →',
    'hero.img_alt'              => 'A phone showing @godwin\'s Universon profile on a street at sunset',
    'hero.img2_alt'             => 'Top-down view: a phone showing @Max\'s profile among vinyl records and headphones',

    /* --- Counters ------------------------------------------------------ */
    'counters.shelves.value'    => '4',
    'counters.shelves.label'    => 'Shelves',
    'counters.albums.value'     => '∞',
    'counters.albums.label'     => 'Albums',
    'counters.link.value'       => '1',
    'counters.link.label'       => 'Link to share',
    'counters.algo.value'       => '0',
    'counters.algo.label'       => 'Algorithm',

    /* --- Three steps --------------------------------------------------- */
    'steps.title'               => "Three steps,\ntwo minutes.",
    'steps.aside'               => 'How it works',
    'steps.1.index'             => '01',
    'steps.1.title'             => 'Sign in',
    'steps.1.body'              => 'With Google. No password to remember, no form to fill.',
    'steps.2.index'             => '02',
    'steps.2.title'             => 'Fill the wall',
    'steps.2.body'              => 'Type three letters, the cover shows up. Sort into favorites, most played, guilty pleasures, first loves.',
    'steps.3.index'             => '03',
    'steps.3.title'             => 'Share your @',
    'steps.3.body'              => 'universon.fr/@you. Visible without an account, readable anywhere.',
    'steps.1.img_alt'           => 'A laptop showing a Universon profile next to a turntable',
    'steps.2.img_alt'           => 'Someone typing \'blonde\' to add an album, with covers showing up',
    'steps.3.img_alt'           => 'Two friends looking at a Universon profile on a phone',

    /* --- Vitrine et détails -------------------------------- */
    'showcase.aside'            => 'Public profiles',
    'showcase.title'            => "Real walls,\nreal people.",
    'showcase.body'             => 'Every profile is a public page, readable without an account. Go see what they\'re listening to.',
    'showcase.alt'              => '@{pseudo}\'s Universon profile on a phone',
    'details.title'             => "Sorted like\na record crate.",
    'details.wall_alt'          => 'A row of eight covers edge to edge on a Universon profile',
    'details.wall_caption'      => 'Your covers, edge to edge. Nothing else.',
    'details.toggle.title'      => 'Public when you want.',
    'details.toggle.body'       => 'One switch: your wall opens at universon.fr/@you, or stays just for you.',
    'details.toggle_alt'        => '\'Public profile\' switch, turned on',

    /* --- Profile ------------------------------------------------------- */
    'profile.share'             => 'Copy link',
    'profile.enlarge'           => 'See it big',
    'profile.stat.albums'       => 'Albums',
    'profile.stat.shelves'      => 'Shelves',
    'profile.stat.since'        => 'Since',
    'profile.avatar.alt'        => 'Profile picture',
    'profile.bio.title'         => 'Bio',
    'profile.bio.empty'         => 'No bio yet.',
    'profile.bio.placeholder'   => 'Talk about what you listen to.',
    'profile.bio.edit'          => 'Edit',
    'profile.bio.save'          => 'Save',
    'profile.bio.saving'        => 'Saving...',
    'profile.bio.cancel'        => 'Cancel',

    /* --- Categories ---------------------------------------------------- */
    'category.favorites'        => 'Favorites',
    'category.most_played'      => 'Most played',
    'category.guilty_pleasure'  => 'Guilty pleasures',
    'category.firstloves'       => 'My very first loves',
    'category.meta'             => '{count} albums · shelf {n}',
    'category.empty'            => "This shelf is empty, someone's struggling to own it...",
    'category.add'              => 'Add an album',

    /* --- Dashboard ----------------------------------------------------- */
    'dashboard.visibility.title'   => 'Public profile',
    'dashboard.visibility.hint'    => 'universon.fr/@{pseudo}',
    'dashboard.visibility.public'  => 'Public',
    'dashboard.visibility.private' => 'Private',
    'dashboard.add.title'          => 'Add an album',
    'dashboard.add.context'        => 'Shelf · {category}',
    'dashboard.add.label'          => 'Album name',
    'dashboard.add.placeholder'    => 'Album name',
    'dashboard.add.cancel'         => 'Cancel',
    'dashboard.add.confirm'        => 'Add',
    'dashboard.add.saving'         => 'Adding...',
    'dashboard.add.select'         => 'Select',
    'dashboard.add.empty'          => 'Type an album name.',
    'dashboard.add.too_long'       => 'That album name is too long.',
    'dashboard.remove'             => 'Remove',
    'dashboard.remove.confirm'     => 'Remove this album from {category}?',

    /* --- Username ------------------------------------------------------ */
    'pseudo.title'              => 'Pick your username',
    'pseudo.body'               => 'To make your profile public, you need a unique username.',
    'pseudo.label'              => 'Username',
    'pseudo.placeholder'        => 'your_username',
    'pseudo.save'               => 'Save',
    'pseudo.saving'             => 'Saving...',
    'pseudo.cancel'             => 'Cancel',
    'pseudo.checking'           => 'Checking...',
    'pseudo.available'          => 'Username available!',
    'pseudo.taken'              => 'Username already taken',
    'pseudo.too_short'          => 'Your username needs at least 3 characters',
    'pseudo.too_long'           => "Your username can't be longer than 45 characters",
    'pseudo.check_error'        => 'Could not check the username',
    'pseudo.saved'              => 'Username saved!',

    /* --- Notifications -------------------------------------------------- */
    'notify.added'              => 'Album added to {category}',
    'notify.removed'            => 'Album removed from {category}',
    'notify.bio_saved'          => 'Bio updated!',
    'notify.link_copied'        => 'Profile link copied!',
    'notify.link_prompt'        => 'Copy the link',
    'notify.visibility'         => 'Profile is now {state}!',
    'notify.offline'            => 'Connection error',
    'notify.error'              => 'Something went wrong, try again.',
    'notify.close'              => 'Close',

    /* --- Error states ---------------------------------------------------- */
    'error.notfound.title'      => 'Profile not found',
    'error.notfound.body'       => "The profile @{pseudo} doesn't exist or is no longer available.",
    'error.private.title'       => 'Private profile',
    'error.private.body'        => "This profile is private. Its owner hasn't opened it up yet.",
    'error.badrequest.title'    => 'Invalid request',
    'error.badrequest.body'     => "This address doesn't match any profile.",
    'error.back'                => 'Back to home',

    /* --- Login ------------------------------------------------------------ */
    'login.tagline'             => 'Your album wall, in one link.',
    'login.google'              => 'Continue with Google',
    'login.terms'               => 'By signing in, you accept the terms of use.',

    /* --- Footer ------------------------------------------------------------ */
    'footer.big'                => "Your music universe,\nin one link",
    'footer.rights'             => '© {year} Universon',
];
