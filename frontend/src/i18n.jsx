/* Contenu textuel (port de util/i18n.php). Aucune chaîne visible n'est écrite
   dans un composant : tout passe par t() / <T>. Une clé absente rend « [ma.cle] » :
   le trou est visible, jamais silencieux. */
import { createContext, Fragment, useContext, useEffect, useState } from 'react';
import fr from './lang/fr.json';
import en from './lang/en.json';
import fon from './lang/fon.json';

/* Locales disponibles : code => nom de la langue, écrit dans cette langue. */
export const LOCALES = { fr: 'Français', en: 'English', fon: 'Fɔ̀ngbè' };
const STRINGS = { fr, en, fon };
const DEFAULT_LOCALE = 'fr';

function readCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}

function rememberLocale(code) {
    document.cookie = `lang=${code}; path=/; max-age=${60 * 60 * 24 * 365}; samesite=lax`;
}

/* La locale vient de ?lang= (mémorisée dans un cookie un an), sinon du cookie,
   sinon de la locale par défaut. */
function initialLocale() {
    const requested = new URLSearchParams(window.location.search).get('lang');
    if (requested in LOCALES) {
        rememberLocale(requested);
        return requested;
    }
    const saved = readCookie('lang');
    return saved in LOCALES ? saved : DEFAULT_LOCALE;
}

export function translate(locale, key, vars = {}) {
    const table = STRINGS[locale];
    if (!Object.prototype.hasOwnProperty.call(table, key)) {
        return '[' + key + ']';
    }
    let value = table[key];
    for (const [name, replacement] of Object.entries(vars)) {
        value = value.split('{' + name + '}').join(String(replacement));
    }
    return value;
}

const I18nContext = createContext(null);

export function I18nProvider({ children }) {
    const [locale, setLocaleState] = useState(initialLocale);

    useEffect(() => {
        document.documentElement.lang = locale;
    }, [locale]);

    function setLocale(code) {
        rememberLocale(code);
        setLocaleState(code);
    }

    const t = (key, vars) => translate(locale, key, vars);
    const has = key => Object.prototype.hasOwnProperty.call(STRINGS[locale], key);

    return <I18nContext.Provider value={{ locale, setLocale, t, has }}>{children}</I18nContext.Provider>;
}

export function useI18n() {
    return useContext(I18nContext);
}

/** Rendu d'une chaîne avec les sauts de ligne convertis en <br>. */
export function T({ k, vars }) {
    const { t } = useI18n();
    const lines = t(k, vars).split('\n');
    return lines.map((line, i) => (
        <Fragment key={i}>{i > 0 && <br />}{line}</Fragment>
    ));
}

/* 7 → « 3,5 » (décimale selon la langue). */
export function ratingValue(t, rating) {
    return String(rating / 2).replace('.', t('review.decimal'));
}
