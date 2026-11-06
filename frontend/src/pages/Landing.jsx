import { useEffect, useState } from 'react';
import { Link, Navigate, useSearchParams } from 'react-router-dom';
import { getJson } from '../api.js';
import { T, useI18n } from '../i18n.jsx';
import { Cover, SiteFooter, SiteHeader, useDocumentTitle } from '../components.jsx';

const SHOWCASE = ['godwin', 'yann', 'max'];
const STEPS = { 1: 'desk-laptop', 2: 'adding-album', 3: 'friends-sharing' };

export default function Landing() {
    const { t } = useI18n();
    const [params] = useSearchParams();
    const [loggedIn, setLoggedIn] = useState(false);
    const [covers, setCovers] = useState([]);

    useDocumentTitle(t('meta.landing.title'), t('meta.landing.description'));

    useEffect(() => {
        getJson('/api/me').then(({ ok }) => setLoggedIn(ok));
        // Le mur du bandeau : de vraies pochettes de profils publics.
        getJson('/api/wall_covers').then(({ data }) => setCovers(data.covers || []));
    }, []);

    // Ancienne adresse de profil : /?u=pseudo
    if (params.get('u')) {
        return <Navigate to={'/@' + params.get('u')} replace />;
    }

    const buttonUrl = loggedIn ? '/dashboard' : '/api/auth/google';

    return (
        <>
            <SiteHeader>
                <Link to="/@godwin" className="link-m"><T k="nav.explore" /></Link>
                <a href={buttonUrl} className="btn btn-line"><T k="nav.login" /></a>
            </SiteHeader>

            <main>
                {/* Bandeau : le mur de pochettes est l'interface. */}
                <section className="hero" aria-labelledby="hero-title">
                    <div className="hero-wall">
                        <figure className="hero-tile hero-tile--lead">
                            <img src="/img/landing/street-phone.webp" width="1600" height="900" alt={t('hero.img_alt')} fetchPriority="high" />
                        </figure>
                        <figure className="hero-tile hero-tile--second">
                            <img src="/img/landing/flatlay.webp" width="1200" height="1200" alt={t('hero.img2_alt')} />
                        </figure>
                        {covers.map((cover, i) => (
                            <div className="hero-tile hero-tile--cover" key={i}>
                                <Cover className="cover" src={cover.image_url} width="300" height="300" alt={cover.name} decoding="async" />
                            </div>
                        ))}
                    </div>
                    <div className="wrap hero-in">
                        <div className="hero-copy">
                            <span className="mo"><T k="hero.eyebrow" /></span>
                            <h1 id="hero-title"><T k="hero.title" /></h1>
                            <p><T k="hero.lede" /></p>
                            <div className="cta-row">
                                <a href={buttonUrl} className="btn"><T k="hero.cta.primary" /></a>
                                <Link to="/@godwin" className="link-m"><T k="hero.cta.secondary" /></Link>
                            </div>
                        </div>
                    </div>
                </section>

                <div className="wrap">
                    <div className="slab slab-row counters">
                        {['shelves', 'albums', 'link', 'algo'].map(key => (
                            <div className="slab-cell" key={key}>
                                <b><T k={`counters.${key}.value`} /></b>
                                <span className="mo"><T k={`counters.${key}.label`} /></span>
                            </div>
                        ))}
                    </div>

                    {/* Vitrine : captures brutes de vrais profils publics, cliquables. */}
                    <section className="showcase" aria-labelledby="showcase-title">
                        <div className="showcase-copy">
                            <span className="mo"><T k="showcase.aside" /></span>
                            <h2 id="showcase-title"><T k="showcase.title" /></h2>
                            <p><T k="showcase.body" /></p>
                        </div>
                        <div className="slab slab-row showcase-row">
                            {SHOWCASE.map(pseudo => (
                                <Link to={'/@' + pseudo} className="slab-cell screen-card" key={pseudo}>
                                    <img src={`/img/landing/screen-${pseudo}.webp`} width="780" height="1688" loading="lazy"
                                         alt={t('showcase.alt', { pseudo })} />
                                    <span className="mo">@{pseudo} →</span>
                                </Link>
                            ))}
                        </div>
                    </section>

                    <section className="steps-section" aria-labelledby="steps-title">
                        <div className="section-head">
                            <h2 id="steps-title"><T k="steps.title" /></h2>
                            <span className="mo"><T k="steps.aside" /></span>
                        </div>
                        <div className="slab slab-row steps">
                            {Object.entries(STEPS).map(([n, photo]) => (
                                <div className="slab-cell" key={n}>
                                    <img src={`/img/landing/${photo}.webp`} width="1400" height="1050" loading="lazy"
                                         className="step-photo" alt={t(`steps.${n}.img_alt`)} />
                                    <span className="mo"><T k={`steps.${n}.index`} /></span>
                                    <h3><T k={`steps.${n}.title`} /></h3>
                                    <p><T k={`steps.${n}.body`} /></p>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* Détails du produit, en captures brutes. */}
                    <section className="details" aria-labelledby="details-title">
                        <div className="section-head">
                            <h2 id="details-title"><T k="details.title" /></h2>
                        </div>
                        <figure className="wall-strip">
                            <img src="/img/landing/screen-wall.webp" width="2762" height="326" loading="lazy" alt={t('details.wall_alt')} />
                            <figcaption className="mo"><T k="details.wall_caption" /></figcaption>
                        </figure>
                        <div className="detail-row">
                            <div>
                                <h3><T k="details.toggle.title" /></h3>
                                <p><T k="details.toggle.body" /></p>
                            </div>
                            <figure className="detail-shot">
                                <img src="/img/landing/screen-toggle.webp" width="2784" height="168" loading="lazy" alt={t('details.toggle_alt')} />
                            </figure>
                        </div>
                    </section>

                    <SiteFooter />
                </div>
            </main>
        </>
    );
}
