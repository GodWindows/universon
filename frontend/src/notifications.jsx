/* Messages « .notification » (succès, erreur, info), masqués après 3 secondes. */
import { createContext, useCallback, useContext, useState } from 'react';
import { useI18n } from './i18n.jsx';

const NotifyContext = createContext(() => {});
let nextId = 1;

export function NotificationProvider({ children }) {
    const { t } = useI18n();
    const [items, setItems] = useState([]);

    const hide = useCallback(id => setItems(list => list.filter(item => item.id !== id)), []);
    const notify = useCallback((message, type = 'info') => {
        const id = nextId++;
        setItems(list => [...list, { id, message, type }]);
        setTimeout(() => hide(id), 3000);
    }, [hide]);

    return (
        <NotifyContext.Provider value={notify}>
            {children}
            {items.map(item => (
                <div key={item.id} className={`notification notification-${item.type}`} role="status">
                    <span>{item.message}</span>
                    <button type="button" className="notification-close" onClick={() => hide(item.id)}>{t('notify.close')}</button>
                </div>
            ))}
        </NotifyContext.Provider>
    );
}

export function useNotify() {
    return useContext(NotifyContext);
}

/* Copie un lien dans le presse-papiers ; repli sur une invite. */
export function copyLink(url, onCopied, promptText) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(onCopied).catch(() => window.prompt(promptText, url));
    } else {
        window.prompt(promptText, url);
    }
}
