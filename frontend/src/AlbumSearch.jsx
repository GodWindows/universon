/* Recherche d'album (Deezer via /api/search_albums), modèle combobox :
   ↓ ↑ parcourent, Entrée choisit, Échap ferme la liste seulement. */
import { useEffect, useRef, useState } from 'react';
import { getJson } from './api.js';
import { useI18n } from './i18n.jsx';

export default function AlbumSearch({ id, value, onChange, onSelect, inputRef, ...inputProps }) {
    const { t } = useI18n();
    const [items, setItems] = useState([]);
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(-1);
    const timer = useRef(null);
    const controller = useRef(null);
    const listId = id + 'Suggestions';

    useEffect(() => () => {
        clearTimeout(timer.current);
        controller.current?.abort();
    }, []);

    function hide() {
        setOpen(false);
        setItems([]);
        setActive(-1);
    }

    function search(query) {
        controller.current?.abort();
        controller.current = new AbortController();
        const params = new URLSearchParams({ q: query, type: 'album', limit: '8' });
        getJson('/api/search_albums?' + params, { signal: controller.current.signal })
            .then(({ data }) => {
                const results = (Array.isArray(data.results) ? data.results : []).map(r => ({
                    title: r.collectionName || '',
                    artist: r.artistName || '',
                    cover: r.artworkUrl100 || '',
                    collectionId: r.collectionId || '',
                    artistId: r.artistId || '',
                }));
                setItems(results);
                setOpen(results.length > 0);
                setActive(-1);
            })
            .catch(err => { if (err.name !== 'AbortError') hide(); });
    }

    function handleChange(e) {
        const next = e.target.value;
        onChange(next);
        clearTimeout(timer.current);
        timer.current = setTimeout(() => {
            const query = next.trim();
            if (query.length < 2) hide();
            else search(query);
        }, 300);
    }

    function choose(item) {
        onChange(item.title);
        onSelect(item);
        hide();
    }

    function handleKeyDown(e) {
        if (!open || items.length === 0) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const next = e.key === 'ArrowDown'
                ? (active + 1) % items.length
                : (active <= 0 ? items.length - 1 : active - 1);
            setActive(next);
            document.getElementById(`${listId}-option-${next}`)?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && active >= 0) {
            e.preventDefault();
            choose(items[active]);
        } else if (e.key === 'Escape') {
            // Ferme la liste sans fermer la modale ni la fiche autour.
            e.preventDefault();
            e.stopPropagation();
            hide();
        }
    }

    return (
        <>
            <input
                {...inputProps}
                ref={inputRef}
                type="text"
                id={id}
                value={value}
                onChange={handleChange}
                onKeyDown={handleKeyDown}
                onFocus={() => { if (items.length > 0) setOpen(true); }}
                autoComplete="off"
                role="combobox"
                aria-autocomplete="list"
                aria-controls={listId}
                aria-expanded={open ? 'true' : 'false'}
                aria-activedescendant={open && active >= 0 ? `${listId}-option-${active}` : undefined}
            />
            <div className="album-suggestions" id={listId} role="listbox" style={{ display: open ? 'block' : 'none' }}>
                {open && items.map((item, index) => (
                    <div
                        key={index}
                        id={`${listId}-option-${index}`}
                        className={'album-suggestion-item' + (index === active ? ' is-active' : '')}
                        role="option"
                        aria-selected={index === active ? 'true' : 'false'}
                        onClick={e => { e.preventDefault(); e.stopPropagation(); choose(item); }}
                    >
                        {item.cover && <img src={item.cover} alt="" />}
                        <span className="album-suggestion-title">{item.title}</span>
                        <span className="album-suggestion-artist">{item.artist}</span>
                        <button type="button" className="album-suggestion-select">{t('dashboard.add.select')}</button>
                    </div>
                ))}
            </div>
        </>
    );
}
