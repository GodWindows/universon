/* Modales du tableau de bord : choix du pseudo, ajout d'un album, fiche d'avis. */
import { useEffect, useRef, useState } from 'react';
import { postJson } from '../api.js';
import { T, ratingValue, useI18n } from '../i18n.jsx';
import { useNotify } from '../notifications.jsx';
import AlbumSearch from '../AlbumSearch.jsx';

/* ─── Choix du pseudo ─────────────────────────────────────────────── */
export function PseudoModal({ open, onCancel, onSaved }) {
    const { t } = useI18n();
    const [value, setValue] = useState('');
    const [feedback, setFeedback] = useState({ text: '', state: '' });
    const [canSave, setCanSave] = useState(false);
    const [saving, setSaving] = useState(false);
    const inputRef = useRef(null);
    const timer = useRef(null);

    useEffect(() => {
        if (!open) return;
        setValue('');
        setFeedback({ text: '', state: '' });
        setCanSave(false);
        inputRef.current?.focus();
        const onKey = e => { if (e.key === 'Escape') onCancel(); };
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('keydown', onKey);
            clearTimeout(timer.current);
        };
    }, [open, onCancel]);

    function check(pseudo) {
        setFeedback({ text: t('pseudo.checking'), state: '' });
        postJson('/api/check_pseudo', { pseudo })
            .then(({ data }) => {
                if (!data.success) {
                    setFeedback({ text: t('pseudo.check_error'), state: 'error' });
                    setCanSave(false);
                } else {
                    setFeedback({ text: t(data.available ? 'pseudo.available' : 'pseudo.taken'), state: data.available ? 'ok' : 'error' });
                    setCanSave(data.available);
                }
            })
            .catch(() => {
                setFeedback({ text: t('notify.offline'), state: 'error' });
                setCanSave(false);
            });
    }

    function handleInput(e) {
        setValue(e.target.value);
        const pseudo = e.target.value.trim();
        clearTimeout(timer.current);
        setCanSave(false);
        if (pseudo.length < 3) return setFeedback({ text: t('pseudo.too_short'), state: '' });
        if (pseudo.length > 45) return setFeedback({ text: t('pseudo.too_long'), state: '' });
        setFeedback({ text: '', state: '' });
        timer.current = setTimeout(() => check(pseudo), 500);
    }

    function save() {
        const pseudo = value.trim();
        if (pseudo.length < 3 || pseudo.length > 45) return;
        setSaving(true);
        postJson('/api/update_pseudo', { pseudo })
            .then(({ data }) => {
                if (data.success) {
                    onSaved(pseudo);
                } else {
                    setFeedback({ text: data.error || t('notify.error'), state: 'error' });
                    setCanSave(false);
                }
            })
            .catch(() => {
                setFeedback({ text: t('notify.offline'), state: 'error' });
                setCanSave(false);
            })
            .finally(() => setSaving(false));
    }

    const feedbackClass = 'feedback' + (feedback.state === 'ok' ? ' is-ok' : '') + (feedback.state === 'error' ? ' is-error' : '');

    return (
        <div id="pseudoModal" className="modal" role="dialog" aria-modal="true" aria-labelledby="pseudoModalTitle"
             style={{ display: open ? 'block' : 'none' }}>
            <div className="modal-head">
                <h2 id="pseudoModalTitle"><T k="pseudo.title" /></h2>
            </div>
            <div className="modal-body">
                <p className="modal-lede"><T k="pseudo.body" /></p>
                <label htmlFor="pseudoInput" className="field-label"><T k="pseudo.label" /></label>
                <input ref={inputRef} type="text" id="pseudoInput" placeholder={t('pseudo.placeholder')} maxLength={45} minLength={3}
                       value={value} onChange={handleInput} />
                <p id="pseudoFeedback" className={feedbackClass} role="status" aria-live="polite">{feedback.text}</p>
            </div>
            <div className="modal-foot">
                <button type="button" id="cancelPseudoBtn" className="btn btn-line" onClick={onCancel}><T k="pseudo.cancel" /></button>
                <button type="button" id="savePseudoBtn" className="btn" disabled={!canSave || saving} onClick={save}>
                    {saving ? t('pseudo.saving') : t('pseudo.save')}
                </button>
            </div>
        </div>
    );
}

/* ─── Ajout d'un album dans un rayon ─────────────────────────────── */
export function AddAlbumModal({ category, categoryLabel, onClose, onAdded }) {
    const { t } = useI18n();
    const notify = useNotify();
    const [value, setValue] = useState('');
    const [selected, setSelected] = useState(null);
    const [saving, setSaving] = useState(false);
    const inputRef = useRef(null);
    const inputId = category + 'Input';

    useEffect(() => { inputRef.current?.focus(); }, []);

    function submit(e) {
        e.preventDefault();
        const albumName = value.trim();
        if (albumName.length < 1) return notify(t('dashboard.add.empty'), 'error');
        if (albumName.length > 255) return notify(t('dashboard.add.too_long'), 'error');

        setSaving(true);
        postJson('/api/add_album_to_category', {
            album_name: albumName,
            category,
            album_data: {
                album_name: albumName,
                external_album_id: selected?.collectionId || null,
                external_artist_id: selected?.artistId || null,
                artist_name: selected?.artist || null,
            },
        })
            .then(({ data }) => {
                if (data.success) onAdded();
                else notify(data.error || t('notify.error'), 'error');
            })
            .catch(() => notify(t('notify.offline'), 'error'))
            .finally(() => setSaving(false));
    }

    return (
        <div className="add-album-modal show" role="dialog" aria-modal="true" aria-label={t('dashboard.add.title')}
             onClick={e => { if (e.target === e.currentTarget) onClose(); }}
             onKeyDown={e => { if (e.key === 'Escape') onClose(); }}>
            <div className="modal-head">
                <div>
                    <h2>{t('dashboard.add.title')}</h2>
                    <span className="mo modal-context">{t('dashboard.add.context', { category: categoryLabel })}</span>
                </div>
                <button type="button" className="btn btn-line btn--sm close-btn" onClick={onClose}>{t('notify.close')}</button>
            </div>
            <form onSubmit={submit}>
                <div className="modal-body">
                    <div className="album-input-group">
                        <label htmlFor={inputId} className="field-label">{t('dashboard.add.label')}</label>
                        <AlbumSearch id={inputId} inputRef={inputRef} className="album-input" name="album_name"
                                     placeholder={t('dashboard.add.placeholder')} maxLength={255} required
                                     value={value} onChange={setValue} onSelect={setSelected} />
                    </div>
                </div>
                <div className="modal-foot">
                    <button type="button" className="btn btn-line" onClick={onClose}>{t('dashboard.add.cancel')}</button>
                    <button type="submit" className="btn" disabled={saving}>{saving ? t('dashboard.add.saving') : t('dashboard.add.confirm')}</button>
                </div>
            </form>
        </div>
    );
}

/* ─── Fiche d'écriture d'un avis ─────────────────────────────────── */
/* <dialog> en deux étapes : « pick » (choisir un album) puis « write »
   (note et texte). `request` l'ouvre : { review } pour modifier, {} pour un nouvel avis. */
const EMPTY = { rating: null, review: '' };

export function ReviewSheet({ request, reviews, onSaved }) {
    const { t } = useI18n();
    const notify = useNotify();
    const dialogRef = useRef(null);
    const starsRef = useRef(null);
    const opener = useRef(null);
    const dragging = useRef(false);

    const [step, setStep] = useState('pick');
    const [album, setAlbum] = useState(null);       // { id } pour un avis existant, { data } pour un album choisi
    const [head, setHead] = useState({ title: '', artist: '', cover: '' });
    const [initial, setInitial] = useState(EMPTY);
    const [hasExisting, setHasExisting] = useState(false);
    const [rating, setRating] = useState(null);
    const [hover, setHover] = useState(null);
    const [body, setBody] = useState('');
    const [pick, setPick] = useState('');
    const [saving, setSaving] = useState(false);

    const focusRating = () => setTimeout(() => {
        const dialog = dialogRef.current;
        (dialog.querySelector('input[name="rating"]:checked') || dialog.querySelector('input[name="rating"]'))?.focus();
    });

    function showWrite(nextHead, existing, nextAlbum) {
        setStep('write');
        setAlbum(nextAlbum);
        setHead(nextHead);
        setInitial(existing || EMPTY);
        setHasExisting(Boolean(existing));
        setRating(existing ? existing.rating : null);
        setBody(existing ? existing.review : '');
        setHover(null);
        focusRating();
    }

    const fromReview = review => ({
        head: { title: review.name, artist: review.artist_name || '', cover: review.image_url_100 || review.image_url_60 || '' },
        existing: { rating: review.rating ? Number(review.rating) : null, review: review.review || '' },
    });

    useEffect(() => {
        if (!request) return;
        opener.current = request.opener;
        if (request.review) {
            const found = fromReview(request.review);
            dialogRef.current.showModal();
            showWrite(found.head, found.existing, { id: request.review.id });
        } else {
            setStep('pick');
            setAlbum(null);
            setHead({ title: t('review.new'), artist: '', cover: '' });
            setInitial(EMPTY);
            setPick('');
            dialogRef.current.showModal();
            setTimeout(() => document.getElementById('reviewAlbumInput')?.focus());
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [request]);

    // Album choisi dans la recherche : s'il a déjà un avis, on le reprend.
    function onPick(item) {
        const known = item.collectionId && reviews.find(r => String(r.external_album_id) === String(item.collectionId));
        if (known) {
            const found = fromReview(known);
            return showWrite(found.head, found.existing, { id: known.id });
        }
        showWrite({ title: item.title, artist: item.artist, cover: item.cover }, null, {
            data: {
                album_name: item.title,
                external_album_id: item.collectionId || null,
                external_artist_id: item.artistId || null,
                artist_name: item.artist || null,
            },
        });
    }

    /* ── Champ de note ── */
    function valueFromPointer(event) {
        const rect = starsRef.current.getBoundingClientRect();
        return Math.min(10, Math.max(1, Math.ceil(((event.clientX - rect.left) / rect.width) * 10)));
    }
    const shown = hover ?? rating;

    const dirty = step === 'write' && (rating !== initial.rating || body.trim() !== initial.review);

    function requestClose() {
        if (dirty && !window.confirm(t('review.discard'))) return;
        dialogRef.current.close();
    }

    function send(payloadRating, payloadReview) {
        const payload = { rating: payloadRating, review: payloadReview };
        if (album.id) payload.album_id = album.id;
        else payload.album_data = album.data;
        const deleting = !payloadRating && !payloadReview;
        setSaving(true);

        postJson('/api/save_review', payload)
            .then(({ data }) => {
                if (!data.success) {
                    notify(t('notify.review_error'), 'error');
                    return;
                }
                opener.current = null;
                setInitial({ rating: payloadRating, review: payloadReview });
                dialogRef.current.close();
                notify(t(deleting ? 'notify.review_deleted' : 'notify.review_saved'), 'success');
                onSaved(data.album_id, deleting);
            })
            .catch(() => notify(t('notify.offline'), 'error'))
            .finally(() => setSaving(false));
    }

    return (
        <dialog ref={dialogRef} id="reviewSheet" className="review-sheet" data-step={step} aria-labelledby="reviewTitle"
                onCancel={e => { e.preventDefault(); requestClose(); }}
                onClick={e => { if (e.target === dialogRef.current) requestClose(); }}
                onClose={() => { opener.current?.isConnected && opener.current.focus(); }}>
            <div className="review-head">
                <img className="review-cover" src={head.cover || undefined} alt="" width="96" height="96" hidden={!head.cover} />
                <div>
                    <span className="mo review-eyebrow"><T k="review.eyebrow" /></span>
                    <h2 id="reviewTitle">{head.title}</h2>
                    <p className="mo review-artist">{head.artist}</p>
                </div>
            </div>
            <div className="review-main">
                <div className="album-input-group review-pick">
                    <label htmlFor="reviewAlbumInput" className="field-label"><T k="review.pick.label" /></label>
                    <AlbumSearch id="reviewAlbumInput" className="album-input" placeholder={t('review.pick.placeholder')} maxLength={255}
                                 value={pick} onChange={setPick} onSelect={onPick} />
                </div>
                <div className="review-write">
                    <fieldset className="rating-input">
                        <legend className="field-label"><T k="review.rating.label" /></legend>
                        <div className="rating-row">
                            <div className="rating-stars" aria-hidden="true" ref={starsRef}
                                 onPointerDown={e => {
                                     dragging.current = true;
                                     starsRef.current.setPointerCapture(e.pointerId);
                                     setRating(valueFromPointer(e));
                                 }}
                                 onPointerMove={e => {
                                     if (dragging.current) setRating(valueFromPointer(e));
                                     else if (e.pointerType === 'mouse') setHover(valueFromPointer(e));
                                 }}
                                 onPointerUp={() => { dragging.current = false; }}
                                 onPointerCancel={() => { dragging.current = false; setHover(null); }}
                                 onPointerLeave={() => { if (!dragging.current) setHover(null); }}>
                                {[1, 2, 3, 4, 5].map(n => {
                                    const full = 2 * n;
                                    const cls = shown >= full ? '' : shown === full - 1 ? ' star--half' : ' star--off';
                                    return <i key={n} className={'star' + cls} />;
                                })}
                            </div>
                            <span className={'rating-value' + (shown ? '' : ' is-empty')}>
                                {shown ? t('review.rating.out_of', { value: ratingValue(t, shown) }) : t('review.rating.none')}
                            </span>
                            <button type="button" className="btn btn-line btn--sm rating-clear" hidden={!rating}
                                    onClick={() => { setRating(null); focusRating(); }}>
                                <T k="review.rating.clear" />
                            </button>
                        </div>
                        {Array.from({ length: 10 }, (_, i) => i + 1).map(v => (
                            <input key={v} type="radio" name="rating" value={v} className="sr-only"
                                   checked={rating === v} onChange={() => setRating(v)}
                                   aria-label={t('review.rating.aria', { value: ratingValue(t, v) })} />
                        ))}
                    </fieldset>
                    <label htmlFor="reviewBody" className="field-label review-body-label"><T k="review.body.label" /></label>
                    <textarea id="reviewBody" className="field review-body" maxLength={2000} rows={6}
                              value={body} onChange={e => setBody(e.target.value)} />
                    <p className="mo review-count">{t('review.body.count', { count: body.length })}</p>
                </div>
            </div>
            <div className="modal-foot">
                <button type="button" className="btn btn-line review-delete" hidden={step !== 'write' || !hasExisting}
                        onClick={() => { if (window.confirm(t('review.delete.confirm'))) send(null, ''); }}>
                    <T k="review.delete" />
                </button>
                <button type="button" className="btn btn-line review-cancel" onClick={requestClose}><T k="review.cancel" /></button>
                <button type="button" className="btn review-save" disabled={!dirty || saving} onClick={() => send(rating, body.trim())}>
                    {saving ? t('review.saving') : t('review.save')}
                </button>
            </div>
        </dialog>
    );
}
