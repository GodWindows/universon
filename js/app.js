// Universon — application logic.
//
// This file is behaviour only: data fetching, form handling and DOM state.
// It deliberately contains no styling, animation or decoration.

// Contenu textuel : les chaînes visibles viennent de lang/fr.php, injectées
// par le gabarit dans window.UNIVERSON_I18N. Une clé absente rend « [ma.cle] »,
// exactement comme t() côté PHP : le trou est visible, jamais silencieux.
function s(key, vars) {
    const table = window.UNIVERSON_I18N || {};
    let value = Object.prototype.hasOwnProperty.call(table, key) ? table[key] : '[' + key + ']';
    if (vars) {
        Object.keys(vars).forEach(name => {
            value = value.split('{' + name + '}').join(vars[name]);
        });
    }
    return value;
}

// Libellé d'un rayon. Le fichier de langue fait foi à l'affichage ; la colonne
// album_categories.description reste la source pour les scripts et l'API.
function categoryLabel(name) {
    const table = window.UNIVERSON_I18N || {};
    const key = 'category.' + name;
    if (Object.prototype.hasOwnProperty.call(table, key)) return table[key];
    return categories[name] || name;
}

document.addEventListener('DOMContentLoaded', async function () {
    // Load categories first
    await loadCategories();

    // Share own profile handler: if no pseudo, open modal instead of copying
    const shareOwn = document.getElementById('shareOwnProfileBtn');
    if (shareOwn) {
        shareOwn.addEventListener('click', function (e) {
            const pseudoDisplay = document.querySelector('.pseudo-display');
            const hasPseudo = pseudoDisplay && pseudoDisplay.textContent.trim() !== '';
            if (!hasPseudo) {
                e.preventDefault();
                const pseudoModal = document.getElementById('pseudoModal');
                if (pseudoModal) {
                    pseudoModal.style.display = 'block';
                    const pseudoInput = document.getElementById('pseudoInput');
                    if (pseudoInput) pseudoInput.focus();
                }
                return;
            }
            const url = shareOwn.getAttribute('data-share-url');
            if (!url) return;
            copyToClipboard(url);
        });
    }

    // Initialize the application
    initApp();
});

function initApp() {
    initLogout();
    initBioEditing();
    initProfileVisibility();
    initAlbumsManagement();
}

function copyToClipboard(url) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            showNotification(s('notify.link_copied'), 'success');
        }).catch(() => {
            prompt(s('notify.link_prompt'), url);
        });
    } else {
        prompt(s('notify.link_prompt'), url);
    }
}

function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();
            this.disabled = true;
            this.textContent = s('nav.logging_out');
            window.location.href = '/api/logout.php?redirect=/index.php';
        });
    }
}

function initBioEditing() {
    const editBioBtn = document.getElementById('editBioBtn');
    const bioContent = document.getElementById('bioContent');
    const bioEditForm = document.getElementById('bioEditForm');
    const bioTextarea = document.getElementById('bioTextarea');
    const saveBioBtn = document.getElementById('saveBioBtn');
    const cancelBioBtn = document.getElementById('cancelBioBtn');

    if (!editBioBtn || !bioContent || !bioEditForm || !bioTextarea || !saveBioBtn || !cancelBioBtn) {
        return;
    }

    let originalBio = bioTextarea.value;

    // Show edit form
    editBioBtn.addEventListener('click', function () {
        bioContent.style.display = 'none';
        bioEditForm.style.display = 'block';
        bioTextarea.focus();
    });

    // Cancel editing
    cancelBioBtn.addEventListener('click', function () {
        bioEditForm.style.display = 'none';
        bioContent.style.display = 'block';
        bioTextarea.value = originalBio;
    });

    // Save bio
    saveBioBtn.addEventListener('click', function () {
        const newBio = bioTextarea.value.trim();

        if (newBio === originalBio) {
            bioEditForm.style.display = 'none';
            bioContent.style.display = 'block';
            return;
        }

        this.textContent = s('profile.bio.saving');
        this.disabled = true;

        fetch('/api/update_bio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ bio: newBio })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update bio content
                    bioContent.innerHTML = `<p>${escapeHtml(newBio)}</p>`;
                    originalBio = newBio;

                    // Hide form
                    bioEditForm.style.display = 'none';
                    bioContent.style.display = 'block';

                    showNotification(s('notify.bio_saved'), 'success');
                } else {
                    showNotification(data.error || s('notify.error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(s('notify.offline'), 'error');
            })
            .finally(() => {
                this.textContent = s('profile.bio.save');
                this.disabled = false;
            });
    });

    // Ctrl+Enter shortcut for saving
    bioTextarea.addEventListener('keydown', function (e) {
        if (e.ctrlKey && e.key === 'Enter') {
            saveBioBtn.click();
        }
    });
}

function initProfileVisibility() {
    const visibilityToggle = document.getElementById('visibilityToggle');
    const pseudoModal = document.getElementById('pseudoModal');
    const pseudoInput = document.getElementById('pseudoInput');
    const pseudoFeedback = document.getElementById('pseudoFeedback');
    const savePseudoBtn = document.getElementById('savePseudoBtn');
    const cancelPseudoBtn = document.getElementById('cancelPseudoBtn');

    if (!visibilityToggle || !pseudoModal || !pseudoInput || !pseudoFeedback || !savePseudoBtn || !cancelPseudoBtn) {
        return;
    }

    const switchLabelText = document.querySelector('.switch-text');

    let pseudoCheckTimeout;

    // Le message de statut porte son état en classe : la couleur est prescrite
    // par la feuille d'habillage (§ 5.19), jamais posée ici.
    function setFeedback(text, state) {
        pseudoFeedback.textContent = text;
        pseudoFeedback.classList.toggle('is-ok', state === 'ok');
        pseudoFeedback.classList.toggle('is-error', state === 'error');
    }

    // Handle visibility toggle
    visibilityToggle.addEventListener('change', function () {
        const newVisibility = this.checked ? 'public' : 'private';

        // If trying to make public without pseudo, show modal
        if (newVisibility === 'public' && !hasPseudo()) {
            this.checked = false; // Revert toggle
            showPseudoModal();
            return;
        }

        updateProfileVisibility(newVisibility);
    });

    // Pseudo input validation
    pseudoInput.addEventListener('input', function () {
        const pseudo = this.value.trim();

        clearTimeout(pseudoCheckTimeout);

        setFeedback('');
        savePseudoBtn.disabled = true;

        if (pseudo.length < 3) {
            setFeedback(s('pseudo.too_short'));
            return;
        }

        if (pseudo.length > 45) {
            setFeedback(s('pseudo.too_long'));
            return;
        }

        // Check pseudo availability after delay
        pseudoCheckTimeout = setTimeout(() => {
            checkPseudoAvailability(pseudo);
        }, 500);
    });

    // Save pseudo button
    savePseudoBtn.addEventListener('click', function () {
        const pseudo = pseudoInput.value.trim();

        if (pseudo.length < 3 || pseudo.length > 45) {
            return;
        }

        this.textContent = s('pseudo.saving');
        this.disabled = true;

        updatePseudo(pseudo);
    });

    // Cancel pseudo button
    cancelPseudoBtn.addEventListener('click', function () {
        hidePseudoModal();
        // Revert visibility toggle
        visibilityToggle.checked = false;
        updateSwitchLabel('private');
    });

    // Close modal on escape key
    document.addEventListener('keydown', function (e) {
        // Seulement si la modale est ouverte : son display initial est '' (masquée par base.css).
        if (e.key === 'Escape' && pseudoModal.style.display === 'block') {
            hidePseudoModal();
            // Revert visibility toggle
            visibilityToggle.checked = false;
            updateSwitchLabel('private');
        }
    });

    // Helper functions
    function hasPseudo() {
        const pseudoDisplay = document.querySelector('.pseudo-display');
        return pseudoDisplay && pseudoDisplay.textContent.trim() !== '';
    }

    function showPseudoModal() {
        pseudoModal.style.display = 'block';
        pseudoInput.focus();
        pseudoInput.value = '';
        setFeedback('');
        savePseudoBtn.disabled = true;
    }

    function hidePseudoModal() {
        pseudoModal.style.display = 'none';
    }

    function updateSwitchLabel(visibility) {
        if (!switchLabelText) return;
        switchLabelText.textContent = visibility === 'public'
            ? s('dashboard.visibility.public')
            : s('dashboard.visibility.private');
    }

    function checkPseudoAvailability(pseudo) {
        setFeedback(s('pseudo.checking'));

        fetch('/api/check_pseudo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ pseudo: pseudo })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.available) {
                        setFeedback(s('pseudo.available'), 'ok');
                        savePseudoBtn.disabled = false;
                    } else {
                        setFeedback(s('pseudo.taken'), 'error');
                        savePseudoBtn.disabled = true;
                    }
                } else {
                    setFeedback(s('pseudo.check_error'), 'error');
                    savePseudoBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                setFeedback(s('notify.offline'), 'error');
                savePseudoBtn.disabled = true;
            });
    }

    function updatePseudo(pseudo) {
        fetch('/api/update_pseudo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ pseudo: pseudo })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePseudoDisplay(pseudo);
                    hidePseudoModal();

                    // Now update visibility to public
                    visibilityToggle.checked = true;
                    updateProfileVisibility('public');

                    showNotification(s('pseudo.saved'), 'success');
                } else {
                    setFeedback(data.error || s('notify.error'), 'error');
                    savePseudoBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                setFeedback(s('notify.offline'), 'error');
                savePseudoBtn.disabled = true;
            })
            .finally(() => {
                savePseudoBtn.textContent = s('pseudo.save');
                savePseudoBtn.disabled = false;
            });
    }

    function updatePseudoDisplay(pseudo) {
        const pseudoDisplay = document.querySelector('.pseudo-display');
        if (pseudoDisplay) {
            pseudoDisplay.textContent = `@${pseudo}`;
            pseudoDisplay.removeAttribute('hidden');
        }
    }

    function updateProfileVisibility(visibility) {
        fetch('/api/update_profile_visibility.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ visibility: visibility })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateSwitchLabel(visibility);
                    showNotification(s('notify.visibility', {
                        state: visibility === 'public'
                            ? s('dashboard.visibility.public')
                            : s('dashboard.visibility.private')
                    }), 'success');
                } else {
                    // Revert toggle on error
                    visibilityToggle.checked = !visibilityToggle.checked;
                    showNotification(data.error || s('notify.error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert toggle on error
                visibilityToggle.checked = !visibilityToggle.checked;
                showNotification(s('notify.offline'), 'error');
            });
    }
}

// Global variables for search functionality
let albumSuggestions, albumNameInput;

// Global categories data
let categories = {};

// Load categories from database
async function loadCategories() {
    try {
        const response = await fetch('/api/get_categories.php');
        const data = await response.json();

        if (data.success) {
            // Convert array to object for easy lookup
            categories = {};
            data.categories.forEach(category => {
                categories[category.name] = category.description;
            });
        } else {
            console.error('Failed to load categories:', data.error);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

function hideSuggestions(suggestionsElement = null) {
    let targetElement = suggestionsElement;

    if (!targetElement && typeof albumSuggestions !== 'undefined' && albumSuggestions) {
        targetElement = albumSuggestions;
    }

    if (!targetElement) {
        targetElement = document.getElementById('albumSuggestions');
    }

    if (targetElement) {
        targetElement.style.display = 'none';
        targetElement.innerHTML = '';
        const input = targetElement.id && document.querySelector('[aria-controls="' + targetElement.id + '"]');
        if (input) {
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
        }
    }
}

/* Recherche au clavier, modèle combobox (components.md § 5.15) :
   ↓ ↑ parcourent, Entrée choisit, Échap ferme la liste seulement. */
function attachSuggestionKeys(input, list) {
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', list.id);
    input.setAttribute('aria-expanded', 'false');
    list.setAttribute('role', 'listbox');

    input.addEventListener('keydown', function (e) {
        const rows = Array.from(list.querySelectorAll('.album-suggestion-item'));
        if (list.style.display === 'none' || rows.length === 0) return;
        const current = rows.findIndex(row => row.classList.contains('is-active'));

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const next = e.key === 'ArrowDown'
                ? (current + 1) % rows.length
                : (current <= 0 ? rows.length - 1 : current - 1);
            rows.forEach((row, i) => {
                row.classList.toggle('is-active', i === next);
                row.setAttribute('aria-selected', String(i === next));
            });
            input.setAttribute('aria-activedescendant', rows[next].id);
            rows[next].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && current >= 0) {
            e.preventDefault();
            rows[current].click();
        } else if (e.key === 'Escape') {
            // Ferme la liste sans fermer la modale ni la fiche autour.
            e.preventDefault();
            e.stopPropagation();
            hideSuggestions(list);
        }
    });
}

// Global shared search functions
function fetchAlbumSuggestions(query, suggestionsElement = null, inputElement = null, abortController = null) {
    try {
        if (abortController) {
            abortController.abort();
        }
        const controller = new AbortController();
        if (abortController) {
            abortController = controller;
        }

        const params = new URLSearchParams({
            q: query,
            type: 'album',
            limit: '8',
        });
        const url = `../api/search_albums.php?${params.toString()}`;
        fetch(url, { signal: controller.signal })
            .then(r => r.json())
            .then(data => {
                const results = Array.isArray(data.results) ? data.results : [];
                const formattedResults = results.map(r => ({
                    title: r.collectionName || '',
                    artist: r.artistName || '',
                    cover: r.artworkUrl100 || '',
                    collectionId: r.collectionId || '',
                    artistId: r.artistId || '',
                }));

                if (suggestionsElement && inputElement) {
                    renderSuggestions(formattedResults, suggestionsElement, inputElement);
                } else {
                    renderSuggestions(formattedResults);
                }
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    if (suggestionsElement) {
                        hideSuggestions(suggestionsElement);
                    } else {
                        hideSuggestions();
                    }
                }
            });
    } catch (_) {
        if (suggestionsElement) {
            hideSuggestions(suggestionsElement);
        } else {
            hideSuggestions();
        }
    }
}

function renderSuggestions(items, suggestionsElement = null, inputElement = null) {
    const targetElement = suggestionsElement || albumSuggestions;
    const targetInput = inputElement || albumNameInput;

    targetElement.innerHTML = '';
    if (!items || items.length === 0) {
        if (suggestionsElement) {
            hideSuggestions(suggestionsElement);
        } else {
            hideSuggestions();
        }
        return;
    }
    items.forEach((item, index) => {
        const row = document.createElement('div');
        row.className = 'album-suggestion-item';
        row.id = targetElement.id + '-option-' + index;
        row.setAttribute('role', 'option');
        row.setAttribute('aria-selected', 'false');
        const coverHtml = item.cover ? '<img src="' + item.cover + '" alt="">' : '';
        row.innerHTML = `
            ${coverHtml}
            <span class="album-suggestion-title">${escapeHtml(item.title)}</span>
            <span class="album-suggestion-artist">${escapeHtml(item.artist)}</span>
            <button type="button" class="album-suggestion-select">${escapeHtml(s('dashboard.add.select'))}</button>
        `;
        row.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (item && item.title) {
                targetInput.value = item.title;
            }
            // Attach selection metadata to form for submission
            targetInput.dataset.itunesCollectionId = item.collectionId || '';
            targetInput.dataset.itunesArtistId = item.artistId || '';
            targetInput.dataset.artistName = item.artist || '';
            targetInput.dataset.artwork60 = (item.cover || '').replace('100x100bb.jpg', '60x60bb.jpg');
            targetInput.dataset.artwork100 = item.cover || '';
            targetInput.dispatchEvent(new CustomEvent('album-select'));

            // Hide suggestions after selection
            if (suggestionsElement) {
                hideSuggestions(suggestionsElement);
            } else {
                hideSuggestions();
            }
        });
        targetElement.appendChild(row);
    });
    targetElement.style.display = 'block';
    targetInput.setAttribute('aria-expanded', 'true');
    targetInput.removeAttribute('aria-activedescendant');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.setAttribute('role', 'status');

    const text = document.createElement('span');
    text.textContent = message;
    notification.appendChild(text);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'notification-close';
    closeBtn.textContent = s('notify.close');
    closeBtn.addEventListener('click', () => hideNotification(notification));
    notification.appendChild(closeBtn);

    document.body.appendChild(notification);

    // Auto-hide after 3 seconds
    setTimeout(() => {
        hideNotification(notification);
    }, 3000);
}

function hideNotification(notification) {
    if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
    }
}

// Debounce helper used by the album search inputs
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function initAlbumsManagement() {
    initDynamicCategoryButtons();
}

// Délégation : les boutons survivent au remplacement des panneaux (refreshProfile).
function initDynamicCategoryButtons() {
    document.addEventListener('click', function (e) {
        const button = e.target.closest('button[data-category]');
        if (button) createDynamicModal(button.dataset.category);
    });
}

/* Mise à jour sur place après une action (ux-rules.md U15) : la page est
   redemandée au serveur, seules les zones du profil sont remplacées. Défilement,
   onglet, notification et focus sont conservés. Repli : rechargement complet. */
function refreshProfile(focusSelector) {
    return fetch(window.location.pathname + window.location.search, { credentials: 'same-origin' })
        .then(response => {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.text();
        })
        .then(html => {
            const next = new DOMParser().parseFromString(html, 'text/html');
            ['#lists', '#reviews', '.stats-row'].forEach(selector => {
                const current = document.querySelector(selector);
                const fresh = next.querySelector(selector);
                if (current && fresh) current.innerHTML = fresh.innerHTML;
            });
            // Les onglets gardent leur état : seuls les compteurs changent.
            document.querySelectorAll('.profile-tab').forEach(tab => {
                const fresh = next.getElementById(tab.id);
                if (fresh) tab.querySelector('.tab-count').textContent = fresh.querySelector('.tab-count').textContent;
            });
            const target = focusSelector && document.querySelector(focusSelector);
            if (target) target.focus();
        })
        .catch(() => window.location.reload());
}

function createDynamicModal(categoryName) {
    const modalId = `add${categoryName.charAt(0).toUpperCase() + categoryName.slice(1)}Modal`;
    const inputId = `${categoryName}Input`;
    const suggestionsId = `${categoryName}Suggestions`;

    // Check if modal already exists
    let modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        const input = document.getElementById(inputId);
        if (input) {
            input.focus();
            input.value = '';
        }
        return;
    }

    // Create modal dynamically
    modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'add-album-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', s('dashboard.add.title'));
    modal.innerHTML = `
        <div class="modal-head">
            <div>
                <h2>${escapeHtml(s('dashboard.add.title'))}</h2>
                <span class="mo modal-context">${escapeHtml(s('dashboard.add.context', { category: categoryLabel(categoryName) }))}</span>
            </div>
            <button type="button" class="btn btn-line btn--sm close-btn" onclick="closeDynamicModal('${modalId}')">${escapeHtml(s('notify.close'))}</button>
        </div>
        <form id="${categoryName}Form">
            <div class="modal-body">
                <div class="album-input-group">
                    <label for="${inputId}" class="field-label">${escapeHtml(s('dashboard.add.label'))}</label>
                    <input type="text" id="${inputId}" class="album-input" name="album_name" placeholder="${escapeHtml(s('dashboard.add.placeholder'))}" maxlength="255" required autocomplete="off">
                    <div class="album-suggestions" id="${suggestionsId}" style="display:none;"></div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-line" onclick="closeDynamicModal('${modalId}')">${escapeHtml(s('dashboard.add.cancel'))}</button>
                <button type="submit" class="btn">${escapeHtml(s('dashboard.add.confirm'))}</button>
            </div>
        </form>
    `;

    document.body.appendChild(modal);
    modal.classList.add('show');

    // Initialize the input functionality
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);
    const form = document.getElementById(`${categoryName}Form`);

    if (input && suggestions && form) {
        attachSuggestionKeys(input, suggestions);
        input.addEventListener('input', debounce(function () {
            const query = input.value.trim();
            if (query.length < 2) {
                hideSuggestions(suggestions);
                return;
            }
            fetchAlbumSuggestions(query, suggestions, input);
        }, 300));

        input.addEventListener('focus', function () {
            if (suggestions.children.length > 0) {
                suggestions.style.display = 'block';
            }
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const albumName = input.value.trim();

            if (albumName.length < 1) {
                showNotification(s('dashboard.add.empty'), 'error');
                return;
            }

            if (albumName.length > 255) {
                showNotification(s('dashboard.add.too_long'), 'error');
                return;
            }

            addAlbumToCategory(albumName, categoryName, input, suggestions);
        });

        // Close modal on outside click
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeDynamicModal(modalId);
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                closeDynamicModal(modalId);
            }
        });

        input.focus();
    }
}

function closeDynamicModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal.parentNode) {
        modal.classList.remove('show');
        modal.parentNode.removeChild(modal);
    }
}

// Global function for adding albums to categories
function addAlbumToCategory(albumName, category, inputElement, suggestionsElement) {
    const categoryDisplayName = categoryLabel(category);

    const saveBtn = inputElement.closest('form').querySelector('button[type="submit"]');
    const originalContent = saveBtn.textContent;
    saveBtn.textContent = s('dashboard.add.saving');
    saveBtn.disabled = true;

    // Prepare album data
    const albumData = {
        album_name: albumName,
        external_album_id: inputElement.dataset.itunesCollectionId || null,
        external_artist_id: inputElement.dataset.itunesArtistId || null,
        artist_name: inputElement.dataset.artistName || null,
        image_url_60: inputElement.dataset.artwork60 || null,
        image_url_100: inputElement.dataset.artwork100 || null
    };

    fetch('/api/add_album_to_category.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            album_name: albumName,
            category: category,
            album_data: albumData
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = inputElement.closest('.add-album-modal');
                if (modal) modal.classList.remove('show');

                showNotification(s('notify.added', { category: categoryDisplayName }), 'success');
                refreshProfile('button[data-category="' + category + '"]');
            } else {
                showNotification(data.error || s('notify.error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(s('notify.offline'), 'error');
        })
        .finally(() => {
            saveBtn.textContent = originalContent;
            saveBtn.disabled = false;
        });
}

// Global function for removing albums from categories
function removeAlbumFromCategory(albumId, category) {
    const categoryDisplayName = categoryLabel(category);

    if (!confirm(s('dashboard.remove.confirm', { category: categoryDisplayName }))) {
        return;
    }

    fetch('/api/remove_album_from_category.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            album_id: albumId,
            category: category
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(s('notify.removed', { category: categoryDisplayName }), 'success');
                refreshProfile('button[data-category="' + category + '"]');
            } else {
                showNotification(data.error || s('notify.error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(s('notify.offline'), 'error');
        });
}
