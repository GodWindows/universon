import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { getJson, postJson } from '../api.js';
import { T, useI18n } from '../i18n.jsx';
import { copyLink, useNotify } from '../notifications.jsx';
import {
    Cover, LogoutButton, ProfileStats, ProfileTabs, ReviewList, SiteFooter, SiteHeader,
    countAlbums, coverOf, fullName, pad2, useCategoryLabel, useDocumentTitle, useProfileTab,
} from '../components.jsx';
import { AddAlbumModal, PseudoModal, ReviewSheet } from './DashboardModals.jsx';

function BioBlock({ bio, onSaved }) {
    const { t } = useI18n();
    const notify = useNotify();
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(bio || '');
    const [saving, setSaving] = useState(false);
    const textareaRef = useRef(null);

    useEffect(() => { if (editing) textareaRef.current?.focus(); }, [editing]);

    function cancel() {
        setDraft(bio || '');
        setEditing(false);
    }

    function save() {
        const next = draft.trim();
        if (next === (bio || '')) return setEditing(false);
        setSaving(true);
        postJson('/api/update_bio', { bio: next })
            .then(({ data }) => {
                if (data.success) {
                    onSaved(next);
                    setEditing(false);
                    notify(t('notify.bio_saved'), 'success');
                } else {
                    notify(data.error || t('notify.error'), 'error');
                }
            })
            .catch(() => notify(t('notify.offline'), 'error'))
            .finally(() => setSaving(false));
    }

    return (
        <div className="bio-block">
            <div className="bio-head">
                <h2 id="bio-title"><T k="profile.bio.title" /></h2>
                <button type="button" id="editBioBtn" className="btn btn-line btn--sm" onClick={() => setEditing(true)}><T k="profile.bio.edit" /></button>
            </div>
            <div id="bioContent" style={{ display: editing ? 'none' : 'block' }}>
                <p className="bio">{bio || <T k="profile.bio.empty" />}</p>
            </div>
            <div id="bioEditForm" style={{ display: editing ? 'block' : 'none' }}>
                <label htmlFor="bioTextarea" className="field-label"><T k="profile.bio.title" /></label>
                <textarea id="bioTextarea" ref={textareaRef} rows={4} placeholder={t('profile.bio.placeholder')}
                          value={draft} onChange={e => setDraft(e.target.value)}
                          onKeyDown={e => { if (e.ctrlKey && e.key === 'Enter') save(); }} />
                <div className="form-actions">
                    <button type="button" id="cancelBioBtn" className="btn btn-line" onClick={cancel}><T k="profile.bio.cancel" /></button>
                    <button type="button" id="saveBioBtn" className="btn" disabled={saving} onClick={save}>
                        {saving ? t('profile.bio.saving') : t('profile.bio.save')}
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Dashboard() {
    const { t } = useI18n();
    const notify = useNotify();
    const categoryLabel = useCategoryLabel();
    const [data, setData] = useState(null);
    const [unauthorized, setUnauthorized] = useState(false);
    const [tab, selectTab] = useProfileTab();
    const [pseudoOpen, setPseudoOpen] = useState(false);
    const [addTo, setAddTo] = useState(null);           // rayon dont la modale d'ajout est ouverte
    const [reviewRequest, setReviewRequest] = useState(null);
    const pendingFocus = useRef(null);

    useDocumentTitle('Universon — ' + t('meta.dashboard.title'));

    /* Mise à jour sur place après une action : seules les données changent,
       onglet et défilement sont conservés ; le focus va à focusSelector. */
    const load = useCallback(focusSelector => {
        pendingFocus.current = focusSelector || null;
        return getJson('/api/me').then(({ status, data: body }) => {
            if (status === 401) setUnauthorized(true);
            else setData(body);
        });
    }, []);

    useEffect(() => { load(); }, [load]);

    useEffect(() => {
        if (pendingFocus.current) {
            document.querySelector(pendingFocus.current)?.focus();
            pendingFocus.current = null;
        }
    }, [data]);

    const closePseudo = useCallback(() => setPseudoOpen(false), []);

    if (unauthorized) return <Navigate to="/login" replace />;
    if (!data) return null;

    const { user, categories, reviews } = data;
    const setUser = patch => setData(d => ({ ...d, user: { ...d.user, ...patch } }));
    const totalAlbums = countAlbums(categories);
    const isPublic = user.profile_visibility === 'public';
    const visibilityLabel = v => t(v === 'public' ? 'dashboard.visibility.public' : 'dashboard.visibility.private');

    function updateVisibility(visibility) {
        const previous = user.profile_visibility;
        setUser({ profile_visibility: visibility });
        postJson('/api/update_profile_visibility', { visibility })
            .then(({ data: res }) => {
                if (res.success) {
                    notify(t('notify.visibility', { state: visibilityLabel(visibility) }), 'success');
                } else {
                    setUser({ profile_visibility: previous });
                    notify(res.error || t('notify.error'), 'error');
                }
            })
            .catch(() => {
                setUser({ profile_visibility: previous });
                notify(t('notify.offline'), 'error');
            });
    }

    function onToggle(e) {
        const visibility = e.target.checked ? 'public' : 'private';
        // Rendre public sans pseudo : on demande d'abord le pseudo
        if (visibility === 'public' && !user.pseudo) return setPseudoOpen(true);
        updateVisibility(visibility);
    }

    function onPseudoSaved(pseudo) {
        setUser({ pseudo });
        setPseudoOpen(false);
        updateVisibility('public');
        notify(t('pseudo.saved'), 'success');
    }

    function share() {
        if (!user.pseudo) return setPseudoOpen(true);
        copyLink(`${window.location.origin}/@${user.pseudo}`, () => notify(t('notify.link_copied'), 'success'), t('notify.link_prompt'));
    }

    function removeAlbum(albumId, category) {
        const label = categoryLabel(category);
        if (!window.confirm(t('dashboard.remove.confirm', { category: label }))) return;
        postJson('/api/remove_album_from_category', { album_id: albumId, category: category.name })
            .then(({ data: res }) => {
                if (res.success) {
                    notify(t('notify.removed', { category: label }), 'success');
                    load(`button[data-category="${category.name}"]`);
                } else {
                    notify(res.error || t('notify.error'), 'error');
                }
            })
            .catch(() => notify(t('notify.offline'), 'error'));
    }

    return (
        <>
            <SiteHeader><LogoutButton /></SiteHeader>

            <main>
                <div className="wrap">
                    <section className="profile" aria-labelledby="profile-title">
                        {user.picture && <img src={user.picture} alt={t('profile.avatar.alt')} className="profile-avatar" />}
                        <div>
                            <h1 id="profile-title">{fullName(user)}</h1>
                            <p><span className="pseudo-display" hidden={!user.pseudo}>{user.pseudo ? '@' + user.pseudo : ''}</span></p>

                            <BioBlock bio={user.bio} onSaved={bio => setUser({ bio })} />

                            <div className="profile-actions">
                                <button type="button" id="shareOwnProfileBtn" className="btn" onClick={share}><T k="profile.share" /></button>
                                {user.pseudo && <Link to={'/@' + user.pseudo} className="btn btn-line"><T k="profile.enlarge" /></Link>}
                            </div>

                            <ProfileStats totalAlbums={totalAlbums} shelves={categories.length} createdAt={user.created_at} />
                        </div>
                    </section>
                </div>

                <div className="panelbox">
                    <div className="wrap">
                        <section className="visibility" aria-labelledby="visibility-title">
                            <div className="row">
                                <div>
                                    <h2 className="row-title" id="visibility-title"><T k="dashboard.visibility.title" /></h2>
                                    {user.pseudo && <span className="mo row-hint"><T k="dashboard.visibility.hint" vars={{ pseudo: user.pseudo }} /></span>}
                                </div>
                                <p className="switch">
                                    <span className="switch-label">
                                        <span className="switch-text mo">{visibilityLabel(user.profile_visibility)}</span>
                                    </span>
                                    <input type="checkbox" id="visibilityToggle" checked={isPublic} onChange={onToggle} />
                                    <label htmlFor="visibilityToggle"><span className="sr-only">{t('dashboard.visibility.title')}</span></label>
                                </p>
                            </div>
                        </section>
                    </div>
                </div>

                <div className="wrap">
                    <ProfileTabs selected={tab} onSelect={selectTab} listCount={totalAlbums} reviewCount={reviews.length} />

                    <section id="lists" className="profile-panel" role="tabpanel" aria-labelledby="tab-lists" tabIndex={0} hidden={tab !== 'lists'}>
                        <h2 className="sr-only"><T k="section.lists" /></h2>
                        {categories.map((category, index) => (
                            <section className="category" aria-labelledby={'cat-' + category.name} key={category.name}>
                                <div className="cat-head">
                                    <h3 id={'cat-' + category.name}><T k={'category.' + category.name} /></h3>
                                    <div className="cat-head-aside">
                                        <span className="mo cat-meta"><T k="category.meta" vars={{ count: category.albums.length, n: pad2(index + 1) }} /></span>
                                        <button type="button" data-category={category.name} className="btn btn--sm" onClick={() => setAddTo(category)}>
                                            <T k="category.add" />
                                        </button>
                                    </div>
                                </div>
                                {category.albums.length ? (
                                    <ul className="album-list">
                                        {category.albums.map((album, i) => (
                                            <li className="album-card-horizontal" data-album-id={album.id} key={album.id}>
                                                <Cover src={coverOf(album)} alt={album.name} loading="lazy" />
                                                <span className="album-idx">{pad2(i + 1)}</span>
                                                <div className="album-meta">
                                                    <h3 className="album-title">{album.name}</h3>
                                                    {album.artist_name && <p className="album-artist">{album.artist_name}</p>}
                                                </div>
                                                <button type="button" className="remove-album-btn" onClick={() => removeAlbum(album.id, category)}>
                                                    <T k="dashboard.remove" />
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                ) : <p className="no-albums"><T k="category.empty" /></p>}
                            </section>
                        ))}
                    </section>

                    {/* Avis, indépendants des listes */}
                    <section id="reviews" className="profile-panel" role="tabpanel" aria-labelledby="tab-reviews" tabIndex={0} hidden={tab !== 'reviews'}>
                        <h2 className="sr-only"><T k="section.reviews" /></h2>
                        <div className="panel-bar">
                            <button type="button" id="newReviewBtn" className="btn btn--sm" aria-haspopup="dialog"
                                    onClick={e => setReviewRequest({ opener: e.currentTarget })}>
                                <T k="review.new" />
                            </button>
                        </div>
                        {reviews.length
                            ? <ReviewList reviews={reviews} onEdit={(review, opener) => setReviewRequest({ review, opener })} />
                            : <p className="no-albums"><T k="review.empty.own" /></p>}
                    </section>

                    <SiteFooter />
                </div>
            </main>

            <PseudoModal open={pseudoOpen} onCancel={closePseudo} onSaved={onPseudoSaved} />

            {addTo && (
                <AddAlbumModal
                    category={addTo.name}
                    categoryLabel={categoryLabel(addTo)}
                    onClose={() => setAddTo(null)}
                    onAdded={() => {
                        notify(t('notify.added', { category: categoryLabel(addTo) }), 'success');
                        setAddTo(null);
                        load(`button[data-category="${addTo.name}"]`);
                    }}
                />
            )}

            <ReviewSheet
                request={reviewRequest}
                reviews={reviews}
                onSaved={(albumId, deleting) => {
                    load(deleting ? '#newReviewBtn' : `.review-edit[data-album-id="${albumId}"]`);
                }}
            />
        </>
    );
}
