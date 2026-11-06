import mysql from 'mysql2/promise';

export const pool = mysql.createPool({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT || 3306),
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    charset: 'utf8mb4',
    connectionLimit: 10,
});

/* Lignes d'un SELECT. */
export async function query(sql, params = []) {
    const [rows] = await pool.execute(sql, params);
    return rows;
}
