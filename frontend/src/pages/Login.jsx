import { T, useI18n } from '../i18n.jsx';
import { LangSwitcher, Logo, useDocumentTitle } from '../components.jsx';

export default function Login() {
    const { t } = useI18n();
    useDocumentTitle('Universon — ' + t('meta.login.title'), t('meta.login.description'));

    return (
        <>
            <div className="lang-corner"><LangSwitcher /></div>
            <main className="login">
                <div className="login-in">
                    <h1><Logo /></h1>
                    <p><T k="login.tagline" /></p>
                    <a href="/api/auth/google" className="btn btn-google"><T k="login.google" /></a>
                    <p className="mo login-terms"><T k="login.terms" /></p>
                </div>
            </main>
        </>
    );
}
