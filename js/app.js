// Universon — application logic.
//
// This file is behaviour only: data fetching, form handling and DOM state.
// It deliberately contains no styling, animation or decoration.

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
            showNotification('Lien du profil copié !', 'success');
        }).catch(() => {
            prompt('Copiez le lien', url);
        });
    } else {
        prompt('Copiez le lien', url);
    }
}

function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();
            this.disabled = true;
            this.textContent = 'Déconnexion...';
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

        this.textContent = 'Sauvegarde...';
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

                    showNotification('Bio mise à jour avec succès !', 'success');
                } else {
                    showNotification(data.error || 'Erreur lors de la mise à jour', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Erreur de connexion', 'error');
            })
            .finally(() => {
                this.textContent = 'Sauvegarder';
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

        pseudoFeedback.textContent = '';
        savePseudoBtn.disabled = true;

        if (pseudo.length < 3) {
            pseudoFeedback.textContent = 'Le pseudo doit contenir au moins 3 caractères';
            return;
        }

        if (pseudo.length > 45) {
            pseudoFeedback.textContent = 'Le pseudo ne peut pas dépasser 45 caractères';
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

        this.textContent = 'Enregistrement...';
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
        if (e.key === 'Escape' && pseudoModal.style.display !== 'none') {
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
        pseudoFeedback.textContent = '';
        savePseudoBtn.disabled = true;
    }

    function hidePseudoModal() {
        pseudoModal.style.display = 'none';
    }

    function updateSwitchLabel(visibility) {
        if (!switchLabelText) return;
        switchLabelText.textContent = visibility === 'public' ? 'Public' : 'Privé';
    }

    function checkPseudoAvailability(pseudo) {
        pseudoFeedback.textContent = 'Vérification...';

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
                        pseudoFeedback.textContent = 'Pseudo disponible !';
                        savePseudoBtn.disabled = false;
                    } else {
                        pseudoFeedback.textContent = 'Pseudo déjà pris';
                        savePseudoBtn.disabled = true;
                    }
                } else {
                    pseudoFeedback.textContent = 'Erreur de vérification';
                    savePseudoBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                pseudoFeedback.textContent = 'Erreur de connexion';
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

                    showNotification('Pseudo enregistré avec succès !', 'success');
                } else {
                    pseudoFeedback.textContent = data.error || 'Erreur lors de l\'enregistrement';
                    savePseudoBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                pseudoFeedback.textContent = 'Erreur de connexion';
                savePseudoBtn.disabled = true;
            })
            .finally(() => {
                savePseudoBtn.textContent = 'Enregistrer';
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
                    showNotification(`Profil maintenant ${visibility === 'public' ? 'public' : 'privé'} !`, 'success');
                } else {
                    // Revert toggle on error
                    visibilityToggle.checked = !visibilityToggle.checked;
                    showNotification(data.error || 'Erreur lors de la mise à jour', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert toggle on error
                visibilityToggle.checked = !visibilityToggle.checked;
                showNotification('Erreur de connexion', 'error');
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
    }
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
    items.forEach(item => {
        const row = document.createElement('div');
        row.className = 'album-suggestion-item';
        const coverHtml = item.cover ? '<img src="' + item.cover + '" alt="">' : '';
        row.innerHTML = `
            ${coverHtml}
            <span class="album-suggestion-title">${escapeHtml(item.title)}</span>
            <span class="album-suggestion-artist">${escapeHtml(item.artist)}</span>
            <button type="button" class="album-suggestion-select">Sélectionner</button>
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
    closeBtn.textContent = 'Fermer';
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

function initDynamicCategoryButtons() {
    // Find all add album buttons (they have IDs like addMostplayedBtn, addGuiltypleasureBtn, etc.)
    const addButtons = document.querySelectorAll('[id^="add"][id$="Btn"]');

    addButtons.forEach(button => {
        // Extract category name from button ID (e.g., "addMostplayedBtn" -> "most_played")
        const buttonId = button.id;
        const categoryName = buttonId.replace('add', '').replace('Btn', '').toLowerCase();

        // Map the button IDs to actual category names
        const categoryMapping = {
            'favorite': 'favorite',
            'guiltypleasure': 'guilty_pleasure',
            'mostplayed': 'most_played'
        };

        const snakeCaseCategory = categoryMapping[categoryName] || categoryName;

        button.addEventListener('click', function () {
            createDynamicModal(snakeCaseCategory);
        });
    });
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
    modal.setAttribute('aria-label', 'Ajouter un album');
    modal.innerHTML = `
        <h2>Ajouter un album</h2>
        <button type="button" class="close-btn" onclick="closeDynamicModal('${modalId}')">Fermer</button>
        <form id="${categoryName}Form">
            <div class="album-input-group">
                <label for="${inputId}">Nom de l'album</label>
                <input type="text" id="${inputId}" class="album-input" name="album_name" placeholder="Ex: Dark Side of the Moon" maxlength="255" required autocomplete="off">
                <div class="album-suggestions" id="${suggestionsId}" style="display:none;"></div>
            </div>
            <button type="button" onclick="closeDynamicModal('${modalId}')">Annuler</button>
            <button type="submit">Ajouter</button>
        </form>
    `;

    document.body.appendChild(modal);
    modal.classList.add('show');

    // Initialize the input functionality
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);
    const form = document.getElementById(`${categoryName}Form`);

    if (input && suggestions && form) {
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
                showNotification('Le nom de l\'album ne peut pas être vide', 'error');
                return;
            }

            if (albumName.length > 255) {
                showNotification('Le nom de l\'album est trop long', 'error');
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
    // Use dynamic categories from database
    const categoryDisplayName = categories[category] || category;

    const saveBtn = inputElement.closest('form').querySelector('button[type="submit"]');
    const originalContent = saveBtn.textContent;
    saveBtn.textContent = 'Ajout...';
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

                showNotification(`Album ajouté aux ${categoryDisplayName} !`, 'success');

                // Reload page to show new album
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.error || 'Erreur lors de l\'ajout à la catégorie', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erreur de connexion', 'error');
        })
        .finally(() => {
            saveBtn.textContent = originalContent;
            saveBtn.disabled = false;
        });
}

// Global function for removing albums from categories
function removeAlbumFromCategory(albumId, category) {
    // Use dynamic categories from database
    const categoryDisplayName = categories[category] || category;

    if (!confirm(`Êtes-vous sûr de vouloir retirer cet album des ${categoryDisplayName} ?`)) {
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
                showNotification(`Album retiré des ${categoryDisplayName} !`, 'success');
                // Reload page to show updated categories
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.error || 'Erreur lors de la suppression de la catégorie', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erreur de connexion', 'error');
        });
}
