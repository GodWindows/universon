/* Port de util/functions.php : mêmes requêtes, mêmes règles. */
import crypto from 'node:crypto';
import { pool, query } from './db.js';

/* Durée de vie d'une session : cookie et contrôle en base. */
export const SESSION_LIFETIME_DAYS = 30;

/* La base ne garde que l'empreinte du jeton : une fuite de la table
   sessions ne donne aucun cookie utilisable. */
function sessionTokenHash(token) {
    return crypto.createHash('sha256').update(token).digest('hex');
}

export async function userExists(email) {
    const rows = await query('SELECT id FROM users WHERE email = ?', [email]);
    return rows.length >= 1;
}

export async function createUser(email, givenName, picture) {
    await query(
        "INSERT INTO users (email, firstName, picture, profile_visibility) VALUES (?, ?, ?, 'private')",
        [email, givenName ?? null, picture ?? null],
    );
}

export async function getUserData(email) {
    const rows = await query('SELECT * FROM users WHERE email = ?', [email]);
    return rows[0] ?? null;
}

export async function updateUserBio(email, bio) {
    await query('UPDATE users SET bio = ? WHERE email = ?', [bio, email]);
}

export async function updateProfileVisibility(email, visibility) {
    await query('UPDATE users SET profile_visibility = ? WHERE email = ?', [visibility, email]);
}

export async function checkPseudoAvailability(pseudo) {
    const rows = await query('SELECT COUNT(*) AS n FROM users WHERE pseudo = ?', [pseudo]);
    return Number(rows[0].n) === 0;
}

export async function updateUserPseudo(email, pseudo) {
    if (!(await checkPseudoAvailability(pseudo))) {
        return false;
    }
    await query('UPDATE users SET pseudo = ? WHERE email = ?', [pseudo, email]);
    return true;
}

export async function saveSession(token, email) {
    // Ménage des sessions expirées à chaque connexion
    await query(`DELETE FROM sessions WHERE created_at < NOW() - INTERVAL ${SESSION_LIFETIME_DAYS} DAY`);
    await query('INSERT INTO sessions (session_token, email) VALUES (?, ?)', [sessionTokenHash(token), email]);
}

export async function deleteSession(token) {
    await query('DELETE FROM sessions WHERE session_token = ?', [sessionTokenHash(token)]);
}

export async function getUserFromSessionToken(token) {
    if (typeof token !== 'string' || token === '') {
        return null;
    }
    const rows = await query(
        `SELECT email FROM sessions WHERE session_token = ? AND created_at > NOW() - INTERVAL ${SESSION_LIFETIME_DAYS} DAY LIMIT 1`,
        [sessionTokenHash(token)],
    );
    return rows.length ? getUserData(rows[0].email) : null;
}

export async function getUserPublicByPseudo(pseudo) {
    const rows = await query('SELECT * FROM users WHERE pseudo = ? LIMIT 1', [pseudo]);
    return rows[0] ?? null;
}

/* Pochettes des profils publics, pour le mur du bandeau d'accueil.
   Tirées au hasard ; s'il y en a moins que count, la liste est répétée. */
export async function getPublicWallCovers(count) {
    const covers = await query(`
        SELECT DISTINCT a.id, a.name, a.artist_name, a.image_url_100
        FROM albums a
        INNER JOIN user_album_categories uac ON a.id = uac.album_id
        INNER JOIN users u ON uac.user_id = u.id
        WHERE u.profile_visibility = 'public' AND a.image_url_100 IS NOT NULL AND a.image_url_100 <> ''
        ORDER BY RAND()
        LIMIT ${Number(count)}`);
    if (!covers.length) {
        return [];
    }
    for (const cover of covers) {
        // Les vignettes iTunes existent en plusieurs tailles : 300px reste net en 2x.
        cover.image_url = cover.image_url_100.replace('/100x100bb.', '/300x300bb.');
    }
    const wall = [];
    for (let i = 0; wall.length < count; i++) {
        wall.push(covers[i % covers.length]);
    }
    return wall;
}

export async function getAllCategories() {
    return query('SELECT id, name, description FROM album_categories ORDER BY name ASC');
}

export async function categoryExists(name) {
    const rows = await query('SELECT id FROM album_categories WHERE name = ?', [name]);
    return rows.length > 0;
}

export async function getUserAlbumsByCategory(userId, categoryName) {
    return query(`
        SELECT a.id, a.name, a.artist_name, a.image_url_60, a.image_url_100, a.created_at, uac.created_at AS categorized_at
        FROM albums a
        INNER JOIN user_album_categories uac ON a.id = uac.album_id
        INNER JOIN album_categories ac ON uac.category_id = ac.id
        WHERE uac.user_id = ? AND ac.name = ?
        ORDER BY uac.created_at DESC`, [userId, categoryName]);
}

/* Avis de l'utilisateur, indépendants des listes, du plus récent au plus ancien. */
export async function getUserReviews(userId) {
    return query(`
        SELECT a.id, a.external_album_id, a.name, a.artist_name, a.image_url_60, a.image_url_100,
               ar.rating, ar.body AS review
        FROM album_reviews ar
        INNER JOIN albums a ON a.id = ar.album_id
        WHERE ar.user_id = ?
        ORDER BY ar.updated_at DESC`, [userId]);
}

/* Profil complet affiché par le tableau de bord et la page publique :
   rayons avec leurs albums, et avis. */
export async function getProfileContent(userId) {
    const categories = await getAllCategories();
    for (const category of categories) {
        category.albums = await getUserAlbumsByCategory(userId, category.name);
    }
    return { categories, reviews: await getUserReviews(userId) };
}

/* Album Deezer (api.deezer.com/album/{id}) : titre, artiste, pochettes. Ou null. */
async function fetchDeezerAlbum(deezerId) {
    if (!/^\d+$/.test(deezerId)) {
        return null;
    }
    try {
        const res = await fetch('https://api.deezer.com/album/' + deezerId, { signal: AbortSignal.timeout(5000) });
        // Un identifiant inconnu renvoie aussi 200, avec une clé "error"
        const data = res.ok ? await res.json() : null;
        if (!data || data.error || !data.title) {
            return null;
        }
        return {
            name: data.title,
            artist_id: data.artist?.id ?? null,
            artist_name: data.artist?.name ?? null,
            image_url_60: data.cover_small ?? null,
            image_url_100: data.cover_medium ?? null,
        };
    } catch {
        return null;
    }
}

/* Album par identifiant Deezer, créé s'il n'existe pas. Titre, artiste et
   pochette viennent de l'API Deezer, jamais du navigateur. Sans identifiant
   (saisie libre), l'album garde le nom tapé, sans pochette.
   Retourne son id, ou null si l'album est introuvable. */
async function findOrCreateAlbum(conn, albumData) {
    const raw = albumData?.external_album_id;
    const externalId = raw !== null && raw !== undefined && typeof raw !== 'object' ? String(raw).trim() : '';

    // Saisie libre : nom et artiste tapés, aucune image venant du navigateur
    if (externalId === '') {
        const name = typeof albumData?.album_name === 'string' ? albumData.album_name.trim() : '';
        const artist = typeof albumData?.artist_name === 'string' ? albumData.artist_name.trim() : '';
        if (name === '') {
            return null;
        }
        const [res] = await conn.execute('INSERT INTO albums (name, artist_name) VALUES (?, ?)', [name, artist || null]);
        return res.insertId;
    }

    // Album déjà connu : réutilisé tel quel
    const [existing] = await conn.execute('SELECT id FROM albums WHERE external_album_id = ? LIMIT 1', [externalId]);
    if (existing.length) {
        return existing[0].id;
    }

    // Nouvel album : ses données viennent de Deezer
    const album = await fetchDeezerAlbum(externalId);
    if (!album) {
        return null;
    }
    const [res] = await conn.execute(
        'INSERT INTO albums (external_album_id, external_artist_id, name, artist_name, image_url_60, image_url_100) VALUES (?, ?, ?, ?, ?, ?)',
        [externalId, album.artist_id, album.name, album.artist_name, album.image_url_60, album.image_url_100],
    );
    return res.insertId;
}

export async function addAlbumToCategory(userId, albumData, categoryName) {
    const conn = await pool.getConnection();
    try {
        await conn.beginTransaction();
        const [category] = await conn.execute('SELECT id FROM album_categories WHERE name = ?', [categoryName]);
        const albumId = category.length ? await findOrCreateAlbum(conn, albumData) : null;
        if (!albumId) {
            await conn.rollback();
            return false;
        }
        await conn.execute(
            'INSERT INTO user_album_categories (user_id, album_id, category_id) VALUES (?, ?, ?)',
            [userId, albumId, category[0].id],
        );
        await conn.commit();
        return true;
    } catch (err) {
        await conn.rollback();
        throw err;
    } finally {
        conn.release();
    }
}

export async function removeAlbumFromCategory(userId, albumId, categoryName) {
    await query(`
        DELETE uac FROM user_album_categories uac
        INNER JOIN album_categories ac ON uac.category_id = ac.id
        WHERE uac.user_id = ? AND uac.album_id = ? AND ac.name = ?`, [userId, albumId, categoryName]);
}

/* Note (1–10 demi-étoiles, ou null) et avis d'un album. Sans note ni
   avis, la ligne est supprimée. Retourne l'id de l'album, ou null. */
export async function saveAlbumReview(userId, albumId, albumData, rating, body) {
    const conn = await pool.getConnection();
    try {
        if (!albumId) {
            albumId = await findOrCreateAlbum(conn, albumData);
            if (!albumId) {
                return null;
            }
        }
        if (rating === null && body === '') {
            await conn.execute('DELETE FROM album_reviews WHERE user_id = ? AND album_id = ?', [userId, albumId]);
            return albumId;
        }
        await conn.execute(`
            INSERT INTO album_reviews (user_id, album_id, rating, body) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), body = VALUES(body)`,
            [userId, albumId, rating, body === '' ? null : body]);
        return albumId;
    } finally {
        conn.release();
    }
}
