import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// En dev (npm run dev), /api part vers le backend lancé par docker compose.
export default defineConfig({
    plugins: [react()],
    server: { proxy: { '/api': 'http://localhost:8080' } },
});
