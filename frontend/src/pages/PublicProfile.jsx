import { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import { getJson } from '../api.js';
import { T, useI18n } from '../i18n.jsx';
import { copyLink } from '../notifications.jsx';
import {
    Cover, LogoutButton, ProfileStats, ProfileTabs, ReviewList, SiteFooter, SiteHeader,
    countAlbums, coverOf, fullName, pad2, useDocumentTitle, useProfileTab,
} from '../components.jsx';

/* Écran d'erreur commun (400, 404, privé). */
function ErrorScreen({ code, vars }) {
    const { t } = useI18n();
    useDocumentTitle('Universon — ' + t(`error.${code}.title`), t(`error.${code}.body`, vars));
    return (
        <>
            <SiteHeader />
            <main className="error">
                <h1><T k={`error.${code}.title`} /></h1>
                <p><T k={`error.${code}.body`} vars={vars} /></p>
                <a href="/" className="btn"><T k="error.back" /></a>
            </main>
        </>
    );
}

/* Mur en lecture seule : aucun bouton de retrait ici. */
function AlbumWall({ albums }) {
    return (
        <ul className="album-list">
            {albums.map((album, i) => (
                <li className="album" key={album.id}>
                    <Cover src={coverOf(album)} alt={album.name} loading="lazy" />
                    <span className="album-idx">{pad2(i + 1)}</span>
                    <div className="album-meta">
                        <h3 className="album-title">{album.name}</h3>
                        {album.artist_name && <p className="album-artist">{album.artist_name}</p>}
                    </div>
                </li>
            ))}
        </ul>
    );
}

function Profile({ data }) {
    const { t } = useI18n();
    const { user, categories, reviews, viewer } = data;
    const [tab, selectTab] = useProfileTab();
    const [toast, setToast] = useState('');
    const toastTimer = useRef(null);
    const name = fullName(user);
    const shareUrl = `${window.location.origin}/@${user.pseudo}`;

    useDocumentTitle(
        `Universon — ${name} (@${user.pseudo})`,
        t('meta.profile.description', { pseudo: user.pseudo }) + ' ' + (user.bio ? user.bio.slice(0, 200) : t('meta.profile.bio_fallback')),
    );
    useEffect(() => () => clearTimeout(toastTimer.current), []);

    function share() {
        copyLink(shareUrl, () => {
            setToast(t('notify.link_copied'));
            clearTimeout(toastTimer.current);
            toastTimer.current = setTimeout(() => setToast(''), 2200);
        }, t('notify.link_prompt'));
    }

    return (
        <>
            <SiteHeader>{viewer && <LogoutButton />}</SiteHeader>
            <main>
                <div className="wrap">
                    <section className="profile" aria-labelledby="profile-title">
                        {user.picture && <img src={user.picture} alt={name} className="profile-avatar" />}
                        <div>
                            <h1 id="profile-title">{name}</h1>
                            <p><span className="pseudo-display">@{user.pseudo}</span></p>
                            <p className="bio">{user.bio || <T k="profile.bio.empty" />}</p>
                            <div className="profile-actions">
                                <button type="button" id="shareProfileBtn" className="btn" onClick={share}><T k="profile.share" /></button>
                            </div>
                            <ProfileStats totalAlbums={countAlbums(categories)} shelves={categories.length} createdAt={user.created_at} />
                        </div>
                    </section>

                    <ProfileTabs selected={tab} onSelect={selectTab} listCount={countAlbums(categories)} reviewCount={reviews.length} />

                    <section id="lists" className="profile-panel" role="tabpanel" aria-labelledby="tab-lists" tabIndex={0} hidden={tab !== 'lists'}>
                        <h2 className="sr-only"><T k="section.lists" /></h2>
                        {categories.length === 0 && <p className="no-albums"><T k="category.empty" /></p>}
                        {categories.map((category, index) => (
                            <section className="category" aria-labelledby={'cat-' + category.name} key={category.name}>
                                <div className="cat-head">
                                    <h3 id={'cat-' + category.name}><T k={'category.' + category.name} /></h3>
                                    <div className="cat-head-aside">
                                        <span className="mo cat-meta"><T k="category.meta" vars={{ count: category.albums.length, n: pad2(index + 1) }} /></span>
                                    </div>
                                </div>
                                {category.albums.length
                                    ? <AlbumWall albums={category.albums} />
                                    : <p className="no-albums"><T k="category.empty" /></p>}
                            </section>
                        ))}
                    </section>

                    <section id="reviews" className="profile-panel profile-panel--read" role="tabpanel" aria-labelledby="tab-reviews" tabIndex={0} hidden={tab !== 'reviews'}>
                        <h2 className="sr-only"><T k="section.reviews" /></h2>
                        {reviews.length
                            ? <ReviewList reviews={reviews} />
                            : <p className="no-albums"><T k="review.empty.public" /></p>}
                    </section>

                    <SiteFooter />
                </div>
            </main>
            <p id="ppToast" role="status" aria-live="polite">{toast}</p>
        </>
    );
}

export default function PublicProfile() {
    const { handle = '' } = useParams();
    const pseudo = /^@[A-Za-z0-9_.-]+$/.test(handle) ? handle.slice(1) : null;
    const [result, setResult] = useState(null);

    useEffect(() => {
        if (!pseudo) return;
        setResult(null);
        getJson('/api/profile/' + encodeURIComponent(pseudo)).then(setResult);
    }, [pseudo]);

    if (!pseudo) return <ErrorScreen code="badrequest" />;
    if (!result) return null;
    if (result.status === 404) return <ErrorScreen code="notfound" vars={{ pseudo }} />;
    if (result.status === 403) return <ErrorScreen code="private" />;
    if (!result.ok) return <ErrorScreen code="badrequest" />;
    return <Profile data={result.data} />;
}
