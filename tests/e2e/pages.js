// Every page at phone and laptop width: it loads, nothing overflows sideways, no broken image, no script error
const L = require('./lib');
const SHOTS = process.env.E2E_SHOTS || require('os').tmpdir();
const guest = ['/', '/sign-in', '/register', '/forgot-password', '/forgot-password-confirmation', '/create-password?token=t1&email=ada@example.test', '/create-new-password?encrypted_token=e1&email=ada@example.test', '/create-new-password-confirmation', '/registration-complete', '/nope'];
const authed = ['/home', '/home?game=g-2', '/games', '/games/g-1', '/games/g-old', '/stats', '/players', '/new-game', '/new-player', '/add-players-to-game/g-1', '/account', '/account/confirm-delete-yahtzee-account', '/account/confirm-delete-account', '/game/g-1/player/p-1/score-sheet', '/game/g-old/player/p-1/score-sheet'];

async function check(page, path, kind, name) {
    const errors = [];
    const onc = m => { if (m.type() === 'error') errors.push('console: ' + m.text()); };
    const onp = e => errors.push('pageerror: ' + e.message);
    page.on('console', onc); page.on('pageerror', onp);
    const resp = await page.goto(L.APP + path, { waitUntil: 'networkidle' });
    // Images below the fold load lazily, scroll to the bottom so they are fetched before they are checked
    await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 500) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 40)); } window.scrollTo(0, 0); });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(300);
    const info = await page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth - window.innerWidth,
        title: document.title,
        h1: (document.querySelector('h1') || {}).textContent,
        brokenImages: Array.from(document.images).filter(i => !i.complete || i.naturalWidth === 0).map(i => i.src),
    }));
    await page.screenshot({ path: `${SHOTS}/${name}-${kind}.png`, fullPage: true });
    page.off('console', onc); page.off('pageerror', onp);
    // The 404 page logs the failed request in the console, that is the page doing its job
    if (resp.status() === 404) { errors.length = 0; }
    const flag = (info.overflow > 0 ? ' OVERFLOW ' + info.overflow : '') + (info.brokenImages.length ? ' BROKEN IMG ' + info.brokenImages : '') + (errors.length ? ' ERRORS ' + JSON.stringify(errors) : '');
    console.log(kind.padEnd(6), String(resp.status()).padEnd(4), path.padEnd(48), JSON.stringify(info.h1 && info.h1.trim().slice(0, 40)), flag);
}

(async () => {
    const b = await L.browser();
    for (const kind of ['phone', 'laptop']) {
        const ctx = await L.context(b, kind);
        const page = await ctx.newPage();
        for (const p of guest) { await check(page, p, kind, 'g' + guest.indexOf(p)); }
        await L.scenario('two');
        await L.login(page);
        for (const p of authed) { await check(page, p, kind, 'a' + authed.indexOf(p)); }
        await ctx.close();
    }
    await b.close();
})().catch(e => { console.error(e); process.exit(1); });
