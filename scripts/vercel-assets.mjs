import { cp, mkdir, access } from 'node:fs/promises';

// Publica somente assets, nunca os arquivos PHP ou a raiz do projeto.
await access('public/build/manifest.json');
await mkdir('dist', { recursive: true });
await cp('public/build', 'dist/build', { recursive: true });
for (const file of ['favicon.ico', 'robots.txt']) {
    try {
        await access(`public/${file}`);
    } catch {
        continue;
    }
    await cp(`public/${file}`, `dist/${file}`);
}
