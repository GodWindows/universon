// ---------------------------------------------------------------------------
// Universon — fiche d'écriture d'un avis, tableau de bord (reviews.md R5–R6).
//
// Une <dialog id="reviewSheet"> en deux étapes : « pick » (choisir un album par
// la recherche de js/app.js) puis « write » (note et texte). Les avis sont
// indépendants des listes. Après sauvegarde, le panneau « Avis » est mis à jour
// sur place (refreshProfile, ux-rules.md U15).
// ---------------------------------------------------------------------------
(function () {
    const sheet = document.getElementById('reviewSheet');
    if (!sheet) return;

    function formatRating(rating) {
        return String(rating / 2).replace('.', s('review.decimal'));
    }

    const cover = sheet.querySelector('.review-cover');
    const title = sheet.querySelector('#reviewTitle');
    const artist = sheet.querySelector('.review-artist');
    const pickInput = sheet.querySelector('#reviewAlbumInput');
    const pickSuggestions = sheet.querySelector('#reviewAlbumSuggestions');
    const starsRow = sheet.querySelector('.rating-stars');
    const valueOut = sheet.querySelector('.rating-value');
    const clearBtn = sheet.querySelector('.rating-clear');
    const radios = Array.from(sheet.querySelectorAll('input[name="rating"]'));
    const body = sheet.querySelector('#reviewBody');
    const count = sheet.querySelector('.review-count');
    const saveBtn = sheet.querySelector('.review-save');
    const cancelBtn = sheet.querySelector('.review-cancel');
    const deleteBtn = sheet.querySelector('.review-delete');

    let opener = null;
    let album = null;          // { id } pour un avis existant, { data } pour un album choisi
    let initial = { rating: null, review: '' };
    let rating = null;
    let dragging = false;

    /* ── Champ de note (R6) ──────────────────────────────────────────── */
    function paint(value) {
        Array.from(starsRow.children).forEach((star, index) => {
            const full = 2 * (index + 1);
            star.className = 'star' + (value >= full ? '' : value === full - 1 ? ' star--half' : ' star--off');
        });
        valueOut.textContent = value ? s('review.rating.out_of', { value: formatRating(value) }) : s('review.rating.none');
        valueOut.classList.toggle('is-empty', !value);
    }

    function setRating(value) {
        rating = value;
        radios.forEach(radio => { radio.checked = parseInt(radio.value, 10) === value; });
        clearBtn.hidden = !value;
        paint(value);
        refreshSave();
    }

    function valueFromPointer(event) {
        const rect = starsRow.getBoundingClientRect();
        return Math.min(10, Math.max(1, Math.ceil(((event.clientX - rect.left) / rect.width) * 10)));
    }

    starsRow.addEventListener('pointerdown', event => {
        dragging = true;
        starsRow.setPointerCapture(event.pointerId);
        setRating(valueFromPointer(event));
    });
    starsRow.addEventListener('pointermove', event => {
        if (dragging) {
            setRating(valueFromPointer(event));
        } else if (event.pointerType === 'mouse') {
            paint(valueFromPointer(event));
        }
    });
    starsRow.addEventListener('pointerup', () => { dragging = false; });
    starsRow.addEventListener('pointercancel', () => { dragging = false; paint(rating); });
    starsRow.addEventListener('pointerleave', () => { if (!dragging) paint(rating); });

    radios.forEach(radio => {
        radio.addEventListener('change', () => setRating(parseInt(radio.value, 10)));
    });
    clearBtn.addEventListener('click', () => {
        setRating(null);
        radios[0].focus();
    });

    /* ── Texte ───────────────────────────────────────────────────────── */
    function isDirty() {
        return sheet.dataset.step === 'write' && (rating !== initial.rating || body.value.trim() !== initial.review);
    }

    function refreshSave() {
        saveBtn.disabled = !isDirty();
    }

    function refreshCount() {
        count.textContent = s('review.body.count', { count: body.value.length });
    }

    body.addEventListener('input', () => {
        refreshCount();
        refreshSave();
    });

    /* ── Étapes (R5.2) ───────────────────────────────────────────────── */
    function showPick() {
        sheet.dataset.step = 'pick';
        album = null;
        title.textContent = s('review.new');
        artist.textContent = '';
        cover.hidden = true;
        deleteBtn.hidden = true;
        pickInput.value = '';
        hideSuggestions(pickSuggestions);
        refreshSave();
    }

    function showWrite(head, existing) {
        sheet.dataset.step = 'write';
        title.textContent = head.title;
        artist.textContent = head.artist;
        cover.hidden = !head.cover;
        cover.src = head.cover || '';
        initial = existing || { rating: null, review: '' };
        deleteBtn.hidden = !existing;
        body.value = initial.review;
        refreshCount();
        setRating(initial.rating);
        (radios.find(radio => radio.checked) || radios[0]).focus();
    }

    function fromEditButton(btn) {
        return {
            head: { title: btn.dataset.title, artist: btn.dataset.artist, cover: btn.dataset.cover },
            existing: {
                rating: btn.dataset.rating ? parseInt(btn.dataset.rating, 10) : null,
                review: btn.dataset.review || ''
            }
        };
    }

    attachSuggestionKeys(pickInput, pickSuggestions);
    pickInput.addEventListener('input', debounce(function () {
        const query = pickInput.value.trim();
        if (query.length < 2) {
            hideSuggestions(pickSuggestions);
            return;
        }
        fetchAlbumSuggestions(query, pickSuggestions, pickInput);
    }, 300));

    // Album choisi dans la recherche : s'il a déjà un avis, on le reprend (R5.2).
    pickInput.addEventListener('album-select', () => {
        const d = pickInput.dataset;
        const known = d.itunesCollectionId
            && document.querySelector('.review-edit[data-external-id="' + CSS.escape(d.itunesCollectionId) + '"]');
        if (known) {
            const found = fromEditButton(known);
            album = { id: known.dataset.albumId };
            showWrite(found.head, found.existing);
            return;
        }
        album = {
            data: {
                album_name: pickInput.value.trim(),
                external_album_id: d.itunesCollectionId || null,
                external_artist_id: d.itunesArtistId || null,
                artist_name: d.artistName || null,
                image_url_60: d.artwork60 || null,
                image_url_100: d.artwork100 || null
            }
        };
        showWrite({ title: album.data.album_name, artist: d.artistName || '', cover: d.artwork100 || '' }, null);
    });

    /* ── Ouverture et fermeture (R5.4) ───────────────────────────────── */
    // Délégation : « Écrire un avis » et « Modifier » vivent dans le panneau
    // remplacé par refreshProfile, leurs écouteurs doivent y survivre.
    document.addEventListener('click', event => {
        const newBtn = event.target.closest('#newReviewBtn');
        if (newBtn) {
            opener = newBtn;
            showPick();
            sheet.showModal();
            pickInput.focus();
            return;
        }
        const btn = event.target.closest('.review-edit');
        if (!btn) return;
        opener = btn;
        album = { id: btn.dataset.albumId };
        const found = fromEditButton(btn);
        sheet.showModal();
        showWrite(found.head, found.existing);
    });

    function requestClose() {
        if (isDirty() && !window.confirm(s('review.discard'))) return;
        sheet.close();
    }

    cancelBtn.addEventListener('click', requestClose);
    // Échap : le navigateur émet « cancel » avant de fermer.
    sheet.addEventListener('cancel', event => {
        event.preventDefault();
        requestClose();
    });
    // Clic sur le voile : la cible est la <dialog> elle-même, jamais son contenu.
    sheet.addEventListener('click', event => {
        if (event.target === sheet) requestClose();
    });
    sheet.addEventListener('close', () => {
        if (opener) opener.focus();
    });

    /* ── Sauvegarde et suppression ───────────────────────────────────── */
    function send(payloadRating, payloadReview) {
        const payload = { rating: payloadRating, review: payloadReview };
        if (album.id) {
            payload.album_id = album.id;
        } else {
            payload.album_data = album.data;
        }
        const deleting = !payloadRating && !payloadReview;
        saveBtn.disabled = true;
        saveBtn.textContent = s('review.saving');

        fetch('/api/save_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showNotification(s('notify.review_error'), 'error');
                    return;
                }
                initial = { rating: payloadRating, review: payloadReview };
                opener = null;
                sheet.close();
                showNotification(s(deleting ? 'notify.review_deleted' : 'notify.review_saved'), 'success');
                // Focus : l'avis enregistré, sinon « Écrire un avis » (reviews.md R5.4).
                const edited = '.review-edit[data-album-id="' + data.album_id + '"]';
                refreshProfile(deleting ? '#newReviewBtn' : edited).then(() => {
                    if (!document.querySelector(edited)) document.getElementById('newReviewBtn').focus();
                });
            })
            .catch(() => showNotification(s('notify.offline'), 'error'))
            .finally(() => {
                saveBtn.textContent = s('review.save');
                refreshSave();
            });
    }

    saveBtn.addEventListener('click', () => send(rating, body.value.trim()));
    deleteBtn.addEventListener('click', () => {
        if (window.confirm(s('review.delete.confirm'))) send(null, '');
    });
})();
