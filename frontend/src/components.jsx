/* Morceaux communs aux pages (en-tête, pied, onglets, listes). Les noms de
   classes sont ceux des anciens gabarits PHP : css/universon.css s'applique tel quel. */
import { useEffect, useRef, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { LOCALES, T, ratingValue, useI18n } from './i18n.jsx';

export const pad2 = n => String(n).padStart(2, '0');

/* Libellé d'un rayon : le fichier de langue fait foi, sinon la description en base. */
export function useCategoryLabel() {
    const { t, has } = useI18n();
    return category => (has('category.' + category.name) ? t('category.' + category.name) : category.description || category.name);
}

export function useDocumentTitle(title, description) {
    useEffect(() => {
        document.title = title;
        if (description !== undefined) {
            document.querySelector('meta[name="description"]')?.setAttribute('content', description);
        }
    }, [title, description]);
}

/** Bouton langue (文A) : ouvre la liste des langues. */
export function LangSwitcher() {
    const { locale, setLocale, t } = useI18n();
    const location = useLocation();
    const ref = useRef(null);

    useEffect(() => {
        const close = () => ref.current?.removeAttribute('open');
        const onClick = e => { if (ref.current && !ref.current.contains(e.target)) close(); };
        const onKey = e => { if (e.key === 'Escape') close(); };
        document.addEventListener('click', onClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('click', onClick);
            document.removeEventListener('keydown', onKey);
        };
    }, []);

    return (
        <details className="lang-switch" ref={ref}>
            <summary className="btn btn-line btn--sm" aria-label={t('lang.switch')} title={t('lang.switch')}>
                <svg className="lang-switch-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">
                    <path d="m5 8 6 6" /><path d="m4 14 6-6 2-3" /><path d="M2 5h12" /><path d="M7 2h1" />
                    <path d="m22 22-5-10-5 10" /><path d="M14 18h6" />
                </svg>
                <span>{locale.toUpperCase()}</span>
            </summary>
            <ul className="lang-switch-menu">
                {Object.entries(LOCALES).map(([code, name]) => (
                    <li key={code}>
                        <a
                            href={`${location.pathname}?lang=${code}${location.hash}`}
                            hrefLang={code}
                            lang={code}
                            aria-current={code === locale ? 'true' : undefined}
                            onClick={e => {
                                e.preventDefault();
                                setLocale(code);
                                ref.current?.removeAttribute('open');
                            }}
                        >{name}</a>
                    </li>
                ))}
            </ul>
        </details>
    );
}

export function Logo() {
    return <Link to="/" className="site-logo"><T k="brand.wordmark" /><b><T k="brand.dot" /></b></Link>;
}

export function LogoutButton() {
    const { t } = useI18n();
    const [busy, setBusy] = useState(false);
    return (
        <button
            type="button"
            id="logoutBtn"
            className="btn btn-line"
            disabled={busy}
            onClick={() => {
                setBusy(true);
                window.location.href = '/api/logout';
            }}
        >{busy ? t('nav.logging_out') : t('nav.logout')}</button>
    );
}

export function SiteHeader({ children }) {
    return (
        <div className="wrap">
            <header className="site-header">
                <Logo />
                <nav>
                    {children}
                    <LangSwitcher />
                </nav>
            </header>
        </div>
    );
}

export function SiteFooter() {
    return (
        <footer className="site-footer">
            <p className="big"><T k="footer.big" /><b><T k="brand.dot" /></b></p>
            <p className="mo"><T k="footer.rights" vars={{ year: new Date().getFullYear() }} /></p>
        </footer>
    );
}

/* Onglets « Listes » / « Avis ». L'onglet courant vit dans le fragment de
   l'URL (#lists, #reviews), sans entrée d'historique. */
const TAB_IDS = ['lists', 'reviews'];

export function useProfileTab() {
    const [tab, setTab] = useState(() => (location.hash === '#reviews' ? 'reviews' : 'lists'));
    function select(id) {
        setTab(id);
        history.replaceState(null, '', location.pathname + location.search + '#' + id);
    }
    return [tab, select];
}

export function ProfileTabs({ selected, onSelect, listCount, reviewCount }) {
    const { t } = useI18n();
    const refs = useRef({});
    const counts = { lists: listCount, reviews: reviewCount };
    const labels = { lists: 'section.lists', reviews: 'section.reviews' };

    function onKeyDown(event, index) {
        const last = TAB_IDS.length - 1;
        const target = {
            ArrowRight: index === last ? 0 : index + 1,
            ArrowLeft: index === 0 ? last : index - 1,
            Home: 0,
            End: last,
            ' ': index,
        }[event.key];
        if (target === undefined) return;
        event.preventDefault();
        onSelect(TAB_IDS[target]);
        refs.current[TAB_IDS[target]]?.focus();
    }

    return (
        <nav className="profile-tabs" role="tablist" aria-label={t('tabs.label')}>
            {TAB_IDS.map((id, index) => (
                <a
                    key={id}
                    ref={el => { refs.current[id] = el; }}
                    href={'#' + id}
                    className="profile-tab"
                    role="tab"
                    id={'tab-' + id}
                    aria-controls={id}
                    aria-selected={selected === id ? 'true' : 'false'}
                    tabIndex={selected === id ? 0 : -1}
                    onClick={e => { e.preventDefault(); onSelect(id); }}
                    onKeyDown={e => onKeyDown(e, index)}
                >
                    <T k={labels[id]} /><span className="tab-count">{counts[id]}</span>
                </a>
            ))}
        </nav>
    );
}

/* Note en étoiles : seules les étoiles obtenues, demie comprise. */
export function RatingStars({ rating }) {
    const { t } = useI18n();
    if (!(rating >= 1 && rating <= 10)) return null;
    return (
        <span className="stars" role="img" aria-label={t('review.rating.aria', { value: ratingValue(t, rating) })}>
            {Array.from({ length: Math.floor(rating / 2) }, (_, i) => <i key={i} className="star" />)}
            {rating % 2 === 1 && <i className="star star--half" />}
        </span>
    );
}

export const coverOf = item => item.image_url_100 || item.image_url_60 || '';

/* Image qui disparaît si elle ne charge pas (onerror="this.remove()"). */
export function Cover(props) {
    const [failed, setFailed] = useState(false);
    if (failed || !props.src) return null;
    return <img {...props} onError={() => setFailed(true)} />;
}

/* Liste d'avis. onEdit ajoute le bouton « Modifier ». */
export function ReviewList({ reviews, onEdit }) {
    const { t } = useI18n();
    return (
        <ul className="review-list">
            {reviews.map(review => (
                <li className="review-item" key={review.id}>
                    <div className="review-item-cover">
                        <Cover src={coverOf(review)} alt="" loading="lazy" />
                    </div>
                    <div className="review-item-body">
                        <h3 className="review-item-title">{review.name}</h3>
                        {review.artist_name && <p className="mo review-item-artist">{review.artist_name}</p>}
                        {review.rating ? (
                            <p className="review-score">
                                <RatingStars rating={review.rating} />
                                <span className="rating-value">{t('review.rating.out_of', { value: ratingValue(t, review.rating) })}</span>
                            </p>
                        ) : null}
                        {review.review && <p className="review-text">{review.review}</p>}
                    </div>
                    {onEdit && (
                        <button
                            type="button"
                            className="btn btn-line btn--sm review-edit"
                            data-album-id={review.id}
                            aria-label={t('review.edit.aria', { album: review.name })}
                            onClick={e => onEdit(review, e.currentTarget)}
                        >{t('review.edit')}</button>
                    )}
                </li>
            ))}
        </ul>
    );
}

export function ProfileStats({ totalAlbums, shelves, createdAt }) {
    return (
        <div className="stats-row">
            <div>
                <span className="mo"><T k="profile.stat.albums" /></span>
                <b>{totalAlbums}</b>
            </div>
            <div>
                <span className="mo"><T k="profile.stat.shelves" /></span>
                <b>{shelves}</b>
            </div>
            {createdAt && (
                <div>
                    <span className="mo"><T k="profile.stat.since" /></span>
                    <b>{new Date(createdAt).getFullYear()}</b>
                </div>
            )}
        </div>
    );
}

export const fullName = user => [user.firstName, user.lastName].filter(Boolean).join(' ');
export const countAlbums = categories => categories.reduce((sum, c) => sum + c.albums.length, 0);
