// ---------------------------------------------------------------------------
// Universon — onglets du profil « Listes » / « Avis » (reviews.md R3).
//
// Amélioration progressive : sans script, les onglets sont des ancres et les
// deux panneaux restent visibles. L'onglet courant vit dans le fragment de
// l'URL (#lists, #reviews), sans entrée d'historique (ux-rules.md U2).
// ---------------------------------------------------------------------------
(function () {
    const list = document.querySelector('.profile-tabs');
    if (!list) return;

    const tabs = Array.from(list.querySelectorAll('[role="tab"]'));
    const panelOf = tab => document.getElementById(tab.getAttribute('aria-controls'));

    function select(tab, focus, updateUrl) {
        tabs.forEach(t => {
            const selected = t === tab;
            t.setAttribute('aria-selected', String(selected));
            t.tabIndex = selected ? 0 : -1;
            panelOf(t).hidden = !selected;
        });
        if (focus) tab.focus();

        const hash = '#' + tab.getAttribute('aria-controls');
        if (updateUrl) history.replaceState(null, '', hash);
        // Changer de langue garde l'onglet (ux-rules.md U12).
        if (updateUrl || location.hash === hash) document.querySelectorAll('.lang-switch-menu a').forEach(a => {
            a.href = a.href.split('#')[0] + hash;
        });
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', event => {
            event.preventDefault();
            select(tab, false, true);
        });
        tab.addEventListener('keydown', event => {
            const last = tabs.length - 1;
            const target = {
                ArrowRight: index === last ? 0 : index + 1,
                ArrowLeft: index === 0 ? last : index - 1,
                Home: 0,
                End: last,
                ' ': index
            }[event.key];
            if (target === undefined) return;
            event.preventDefault();
            select(tabs[target], true, true);
        });
    });

    const fromHash = tabs.find(t => '#' + t.getAttribute('aria-controls') === location.hash);
    // Sans fragment, l'URL reste propre : elle n'est réécrite qu'au changement d'onglet.
    select(fromHash || tabs[0], false, false);
})();
