// axe-core on every page and every dialog, needs npm install axe-core next to this file
const L = require('./lib');
const SHOTS = process.env.E2E_SHOTS || require('os').tmpdir();
const fs = require('fs');
const axe = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const pages = [
    ['/', 'guest'], ['/sign-in', 'guest'], ['/register', 'guest'], ['/forgot-password', 'guest'], ['/create-password?token=t&email=a@b.c', 'guest'], ['/nope', 'guest'],
    ['/home', 'auth'], ['/games', 'auth'], ['/games/g-1', 'auth'], ['/games/g-old', 'auth'], ['/stats', 'auth'], ['/players', 'auth'], ['/new-game', 'auth'], ['/new-player', 'auth'], ['/add-players-to-game/g-1', 'auth'], ['/account', 'auth'], ['/account/confirm-delete-yahtzee-account', 'auth'],
    ['/game/g-1/player/p-1/score-sheet', 'auth'], ['/game/g-old/player/p-1/score-sheet', 'auth'],
];
(async () => {
    const b = await L.browser();
    let total = 0;
    for (const kind of ['phone', 'laptop']) {
        const ctx = await L.context(b, kind);
        const page = await ctx.newPage();
        await L.scenario('two');
        L.seedTokens('g-1', [['p-1', 'Ada'], ['p-2', 'Ben'], ['p-3', 'Cleo']]);
        await L.login(page);
        for (const [path] of pages) {
            await page.goto(L.APP + path, { waitUntil: 'networkidle' });
            await page.waitForTimeout(250);
            // Open the dialogs the page has too, their contents are part of the page
            await page.addScriptTag({ content: axe });
            const states = [''];
            const dialogs = await page.$$eval('dialog.sheet', els => els.map(e => e.id));
            for (const state of [''].concat(dialogs)) {
                if (state) { await page.evaluate(id => document.getElementById(id).showModal(), state); }
                const result = await page.evaluate(() => axe.run(document, { runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa', 'best-practice'] }));
                for (const v of result.violations) {
                    total++;
                    console.log(kind, path, state || '(page)', v.id, v.impact, '-', v.help, '|', v.nodes.slice(0, 2).map(n => n.target.join(' ')).join(' ; '));
                }
                if (state) { await page.evaluate(id => document.getElementById(id).close(), state); }
            }
        }
        await ctx.close();
    }
    console.log('violations:', total);
    await b.close();
})().catch(e => { console.error(e); process.exit(1); });
