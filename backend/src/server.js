/* Universon — API (remplace api/*.php, util/redirect.php et le rendu des pages). */
import crypto from 'node:crypto';
import express from 'express';
import cookieParser from 'cookie-parser';
import * as q from './queries.js';

const {
    GOOGLE_CLIENT_ID,
    GOOGLE_CLIENT_SECRET,
    SITE_URL,
    PORT = 3000,
} = process.env;
const GOOGLE_REDIRECT_URI = `${SITE_URL}/api/auth/google/callback`;
const SECURE_COOKIES = SITE_URL.startsWith('https://');
const PSEUDO_RE = /^[A-Za-z0-9_.-]+$/;

const app = express();
app.disable('x-powered-by');
app.use(cookieParser());
app.use(express.json());

/* ─── Middlewares ─────────────────────────────────────────────────── */

// JSON only: a cross-site form cannot send this Content-Type (CSRF)
function requireJson(req, res, next) {
    if (!(req.headers['content-type'] || '').toLowerCase().startsWith('application/json')) {
        return res.status(415).json({ error: 'Content-Type must be application/json' });
    }
    next();
}

async function requireUser(req, res, next) {
    const token = req.cookies.session_token;
    if (!token) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    req.user = await q.getUserFromSessionToken(token);
    if (!req.user) {
        return res.status(401).json({ error: 'Invalid session' });
    }
    next();
}

const post = (path, handler) => app.post(path, requireJson, requireUser, handler);
const str = value => (typeof value === 'string' ? value.trim() : '');

/* Champs d'un profil visibles côté navigateur (jamais l'e-mail). */
function publicUser(user) {
    const { firstName, lastName, pseudo, bio, picture, profile_visibility, created_at } = user;
    return { firstName, lastName, pseudo, bio, picture, profile_visibility, created_at };
}

/* ─── Connexion Google ────────────────────────────────────────────── */

const cookieOptions = { path: '/', secure: SECURE_COOKIES, httpOnly: true, sameSite: 'lax' };

/* Redirection vers Google avec un « state » aléatoire, gardé en cookie et
   vérifié au retour (protection contre le login CSRF). Le state existant est
   réutilisé : deux onglets ouverts restent valides. */
app.get('/api/auth/google', (req, res) => {
    let state = req.cookies.oauth_state;
    if (typeof state !== 'string' || !/^[a-f0-9]{32}$/.test(state)) {
        state = crypto.randomBytes(16).toString('hex');
        res.cookie('oauth_state', state, { ...cookieOptions, maxAge: 3600 * 1000 });
    }
    const params = new URLSearchParams({
        response_type: 'code',
        client_id: GOOGLE_CLIENT_ID,
        redirect_uri: GOOGLE_REDIRECT_URI,
        scope: 'email profile',
        state,
    });
    res.redirect('https://accounts.google.com/o/oauth2/v2/auth?' + params);
});

app.get('/api/auth/google/callback', async (req, res) => {
    const expected = req.cookies.oauth_state;
    const { code, state } = req.query;
    if (typeof code !== 'string' || typeof state !== 'string' || typeof expected !== 'string'
        || expected.length !== state.length
        || !crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(state))) {
        return res.status(400).send('login failed');
    }
    res.clearCookie('oauth_state', cookieOptions);

    const tokenRes = await fetch('https://oauth2.googleapis.com/token', {
        method: 'POST',
        body: new URLSearchParams({
            code,
            client_id: GOOGLE_CLIENT_ID,
            client_secret: GOOGLE_CLIENT_SECRET,
            redirect_uri: GOOGLE_REDIRECT_URI,
            grant_type: 'authorization_code',
        }),
    });
    const token = await tokenRes.json();
    if (!tokenRes.ok || !token.access_token) {
        console.error('Google token error:', token);
        return res.status(400).send('login failed');
    }
    const infoRes = await fetch('https://www.googleapis.com/oauth2/v2/userinfo', {
        headers: { Authorization: `Bearer ${token.access_token}` },
    });
    const info = await infoRes.json();
    if (!infoRes.ok || !info.email) {
        console.error('Google userinfo error:', info);
        return res.status(400).send('login failed');
    }

    if (!(await q.userExists(info.email))) {
        await q.createUser(info.email, info.given_name, info.picture);
    }
    const sessionToken = crypto.randomBytes(32).toString('base64');
    await q.saveSession(sessionToken, info.email);
    res.cookie('session_token', sessionToken, {
        ...cookieOptions,
        maxAge: q.SESSION_LIFETIME_DAYS * 86400 * 1000,
    });
    res.redirect('/dashboard');
});

app.get('/api/logout', async (req, res) => {
    const token = req.cookies.session_token;
    if (typeof token === 'string' && token !== '') {
        await q.deleteSession(token);
    }
    res.clearCookie('session_token', cookieOptions);
    res.redirect('/');
});

/* ─── Données des pages (anciennement rendues par PHP) ────────────── */

// Tableau de bord : l'utilisateur connecté, ses rayons et ses avis.
app.get('/api/me', requireUser, async (req, res) => {
    res.json({ user: publicUser(req.user), ...(await q.getProfileContent(req.user.id)) });
});

// Profil public /@pseudo.
app.get('/api/profile/:pseudo', async (req, res) => {
    const owner = await q.getUserPublicByPseudo(req.params.pseudo);
    if (!owner) {
        return res.status(404).json({ error: 'notfound' });
    }
    if (owner.profile_visibility !== 'public') {
        return res.status(403).json({ error: 'private' });
    }
    const viewer = await q.getUserFromSessionToken(req.cookies.session_token);
    res.json({ user: publicUser(owner), viewer: Boolean(viewer), ...(await q.getProfileContent(owner.id)) });
});

// Mur de pochettes du bandeau d'accueil.
app.get('/api/wall_covers', async (req, res) => {
    res.json({ covers: await q.getPublicWallCovers(40) });
});

/* ─── API (mêmes noms que les anciens api/*.php) ──────────────────── */

app.get('/api/get_categories', async (req, res) => {
    res.json({ success: true, categories: await q.getAllCategories() });
});

app.get('/api/get_albums_by_category', requireUser, async (req, res) => {
    const category = str(req.query.category);
    if (!(await q.categoryExists(category))) {
        return res.status(400).json({ error: 'Invalid category' });
    }
    res.json({ success: true, category, albums: await q.getUserAlbumsByCategory(req.user.id, category) });
});

post('/api/add_album_to_category', async (req, res) => {
    const category = str(req.body.category);
    if (!str(req.body.album_name)) {
        return res.status(400).json({ error: 'Album name is required' });
    }
    if (!(await q.categoryExists(category))) {
        return res.status(400).json({ error: 'Invalid category' });
    }
    // Ajoute l'album (et le crée au besoin)
    if (!(await q.addAlbumToCategory(req.user.id, req.body.album_data || {}, category))) {
        return res.status(500).json({ error: 'Failed to add album to category' });
    }
    res.json({ success: true, message: 'Album added to category successfully', category });
});

post('/api/remove_album_from_category', async (req, res) => {
    const albumId = parseInt(req.body.album_id, 10) || 0;
    const category = str(req.body.category);
    if (albumId <= 0) {
        return res.status(400).json({ error: 'Invalid album ID' });
    }
    if (!(await q.categoryExists(category))) {
        return res.status(400).json({ error: 'Invalid category' });
    }
    await q.removeAlbumFromCategory(req.user.id, albumId, category);
    res.json({ success: true, message: 'Album removed from category successfully', category });
});

// Une note (demi-étoiles 1–10) ou null, un avis ≤ 2000 caractères.
post('/api/save_review', async (req, res) => {
    const { body } = req;
    const albumId = parseInt(body.album_id, 10) || 0;
    const albumData = body.album_data && typeof body.album_data === 'object' ? body.album_data : {};
    let rating = body.rating === undefined || body.rating === null || body.rating === '' ? null : body.rating;
    const review = str(body.review);

    if (albumId <= 0 && !albumData.album_name) {
        return res.status(400).json({ error: 'Album is required' });
    }
    if (rating !== null) {
        rating = Number(rating);
        if (!Number.isInteger(rating) || rating < 1 || rating > 10) {
            return res.status(400).json({ error: 'Rating must be between 1 and 10' });
        }
    }
    if ([...review].length > 2000) {
        return res.status(400).json({ error: 'Review too long (max 2000 characters)' });
    }
    if (albumId <= 0 && rating === null && review === '') {
        return res.status(400).json({ error: 'Rating or review is required' });
    }

    // Enregistre, ou supprime quand les deux sont vides
    const saved = await q.saveAlbumReview(req.user.id, albumId, albumData, rating, review);
    if (!saved) {
        return res.status(400).json({ error: 'Failed to save review' });
    }
    res.json({ success: true, album_id: Number(saved), rating, review });
});

function validatePseudo(pseudo) {
    if (!pseudo) return 'Pseudo cannot be empty';
    if (pseudo.length < 3 || pseudo.length > 45) return 'Pseudo must be between 3 and 45 characters';
    // Same characters as the /@pseudo route: letters, digits, _ . -
    if (!PSEUDO_RE.test(pseudo)) return 'Pseudo can only contain letters, digits, _ . -';
    return null;
}

post('/api/check_pseudo', async (req, res) => {
    const pseudo = str(req.body.pseudo);
    const error = validatePseudo(pseudo);
    if (error) {
        return res.status(400).json({ error });
    }
    res.json({ success: true, available: await q.checkPseudoAvailability(pseudo), pseudo });
});

post('/api/update_pseudo', async (req, res) => {
    const pseudo = str(req.body.pseudo);
    const error = validatePseudo(pseudo);
    if (error) {
        return res.status(400).json({ error });
    }
    if (!(await q.updateUserPseudo(req.user.email, pseudo))) {
        return res.status(500).json({ error: 'Failed to update pseudo' });
    }
    res.json({ success: true, message: 'Pseudo updated successfully', pseudo });
});

post('/api/update_bio', async (req, res) => {
    const bio = str(req.body.bio);
    if (Buffer.byteLength(bio) > 255) {
        return res.status(400).json({ error: 'Bio too long (max 255 characters)' });
    }
    await q.updateUserBio(req.user.email, bio);
    res.json({ success: true, message: 'Bio updated successfully', bio });
});

post('/api/update_profile_visibility', async (req, res) => {
    const visibility = str(req.body.visibility);
    if (!['private', 'public'].includes(visibility)) {
        return res.status(400).json({ error: 'Invalid visibility value' });
    }
    await q.updateProfileVisibility(req.user.email, visibility);
    res.json({ success: true, message: 'Profile visibility updated successfully', visibility });
});

// Recherche Deezer, réservée aux pages du site (Origin, sinon Referer).
app.get('/api/search_albums', async (req, res) => {
    const origin = req.headers.origin || '';
    const referer = req.headers.referer || '';
    if (origin !== SITE_URL && !referer.startsWith(SITE_URL + '/')) {
        return res.status(403).json({ error: 'Access denied' });
    }
    const term = str(req.query.q);
    if (!term) {
        return res.status(400).json({ error: 'Search term is required' });
    }
    const url = 'https://api.deezer.com/search/album?' + new URLSearchParams({
        q: `"${term}"`,
        limit: String(req.query.limit || '8'),
    });
    let deezer = null;
    try {
        const r = await fetch(url, { signal: AbortSignal.timeout(5000) });
        deezer = r.ok ? await r.json() : null;
    } catch {
        deezer = null;
    }
    if (!deezer) {
        return res.status(500).json({ error: 'Failed to search Deezer' });
    }
    const results = (deezer.data || []).map(album => ({
        collectionName: album.title ?? '',
        artistName: album.artist?.name ?? '',
        artworkUrl100: album.cover_medium ?? '',
        collectionId: album.id ?? '',
        artistId: album.artist?.id ?? '',
    }));
    res.json({ resultCount: results.length, results });
});

app.use('/api', (req, res) => res.status(404).json({ error: 'Not found' }));

app.use((err, req, res, next) => {
    console.error(err);
    res.status(500).json({ error: 'Internal server error' });
});

app.listen(PORT, () => console.log(`Universon API on :${PORT}`));
