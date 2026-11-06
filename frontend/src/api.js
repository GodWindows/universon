/* Appels à l'API Node. Les POST sont toujours en JSON (protection CSRF côté serveur). */

export async function getJson(url, options) {
    const res = await fetch(url, { credentials: 'same-origin', ...options });
    const data = await res.json().catch(() => ({}));
    return { status: res.status, ok: res.ok, data };
}

export function postJson(url, body) {
    return getJson(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
}
