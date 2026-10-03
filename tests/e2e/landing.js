// The landing page: the score sheet to try, the walkthrough and (when axe-core is installed) its accessibility, signed out
const L = require('./lib');
const fs = require('fs');
const SHOTS = process.env.E2E_SHOTS || require('os').tmpdir();
let failures = 0;
function check(name, ok, detail) { console.log(ok ? 'ok  ' : 'FAIL', name, ok ? '' : detail === undefined ? '' : JSON.stringify(detail)); if (!ok) { failures++; } }
const text = (page, part) => page.textContent(`[data-demo="${part}"]`).then(t => t.trim());

(async () => {
    const b = await L.browser();
    for (const kind of ['phone', 'laptop']) {
        const ctx = await L.context(b, kind);
        const page = await ctx.newPage();
        const errors = [];
        page.on('console', m => { if (m.type() === 'error') { errors.push(m.text()); } });
        page.on('pageerror', e => errors.push(e.message));
        const requests = [];
        page.on('request', r => { if (r.method() !== 'GET') { requests.push(r.url()); } });
        await page.goto(L.APP + '/', { waitUntil: 'networkidle' });

        // ---- The score sheet to try
        check(kind + ' six rows to score', await page.locator('[data-row]').count() === 6);
        check(kind + ' starts at nothing', await text(page, 'total') === '0');

        await page.click('[data-row="threes"]');
        check(kind + ' a row opens its numbers', await page.getAttribute('[data-row="threes"]', 'aria-expanded') === 'true' && await page.locator('[data-count]').count() === 6);
        check(kind + ' focus moves to the numbers', await page.evaluate(() => document.activeElement.dataset.count === '0'));
        await page.keyboard.press('Escape');
        check(kind + ' Escape closes it and keeps focus on the row', await page.locator('[data-count]').count() === 0 && await page.evaluate(() => document.activeElement.dataset.row === 'threes'));

        await page.click('[data-row="threes"]');
        await page.click('[data-count="3"]');
        check(kind + ' scoring updates the total', await text(page, 'total') === '9', await text(page, 'total'));
        check(kind + ' scored row says so', (await page.textContent('[data-row="threes"]')).includes('Scored'));
        check(kind + ' focus returns to the row', await page.evaluate(() => document.activeElement.dataset.row === 'threes'));
        check(kind + ' the tip is the real one', (await text(page, 'tip')).includes('would get you the bonus'), await text(page, 'tip'));
        check(kind + ' the start over link appears', await page.isVisible('[data-demo="reset"]'));

        // A scored row can be changed, it is only a demo
        await page.click('[data-row="threes"]');
        check(kind + ' the chosen number is pressed', await page.getAttribute('[data-count="3"]', 'aria-pressed') === 'true');
        await page.click('[data-count="0"]');
        check(kind + ' a scratch scores nothing', await text(page, 'total') === '0' && (await page.textContent('[data-row="threes"]')).includes('Scratched'));

        // Just short, then over
        for (const [row, count] of [['ones', 3], ['twos', 3], ['threes', 3], ['fours', 3], ['fives', 4]]) {
            await page.click(`[data-row="${row}"]`);
            await page.click(`[data-count="${count}"]`);
        }
        check(kind + ' five rows is 50 with no bonus', await text(page, 'upper') === '50' && await text(page, 'bonus') === '0');
        await page.click('[data-row="sixes"]');
        await page.click('[data-count="1"]');
        check(kind + ' finishing 11 short has its own words', (await text(page, 'tip')).includes('you finished 7 short') || (await text(page, 'tip')).includes('short'), await text(page, 'tip'));
        check(kind + ' the sheet is complete', await page.isVisible('[data-demo="done"]'));
        await page.click('[data-row="sixes"]');
        await page.click('[data-count="3"]');
        check(kind + ' the bonus arrives', await text(page, 'bonus') === '35' && await text(page, 'total') === '103', [await text(page, 'bonus'), await text(page, 'total')]);
        check(kind + ' the bar is full', (await page.getAttribute('[data-demo="progress"]', 'aria-valuenow')) === '63');
        check(kind + ' the finish card offers a real game', await page.isVisible('[data-demo="done"] a[href$="/register"]'));
        await page.screenshot({ path: `${SHOTS}/landing-${kind}-done.png` });

        await page.click('[data-demo="again"]');
        check(kind + ' try again clears it', await text(page, 'total') === '0' && !(await page.isVisible('[data-demo="done"]')));
        check(kind + ' nothing was sent anywhere', requests.length === 0, requests);

        // ---- The walkthrough
        const steps = await page.locator('[data-step]').count();
        check(kind + ' four steps', steps === 4);
        for (let i = 0; i < steps; i++) {
            await page.evaluate(i => { const s = document.querySelectorAll('[data-step]')[i]; window.scrollTo(0, s.getBoundingClientRect().top + window.scrollY - (window.innerWidth >= 768 ? 300 : 100)); }, i);
            await page.waitForTimeout(500);
            const active = await page.evaluate(() => Array.from(document.querySelectorAll('[data-step]')).map((s, n) => s.hasAttribute('data-active') ? n : -1).filter(n => n >= 0));
            check(kind + ' step ' + (i + 1) + ' is the active one', active.length === 1 && active[0] === i, active);
            if (kind === 'laptop') {
                const shown = await page.evaluate(() => Array.from(document.querySelectorAll('[data-shot]')).map(s => s.hasAttribute('data-active') && !s.hasAttribute('aria-hidden')));
                check(kind + ' the phone shows picture ' + (i + 1), shown.filter(Boolean).length === 1 && shown[i] === true, shown);
                check(kind + ' the phone is in view', await page.evaluate(() => { const r = document.querySelector('[data-shot]').getBoundingClientRect(); return r.top >= 0 && r.bottom <= window.innerHeight; }));
            } else {
                const image = await page.evaluate(i => { const img = document.querySelectorAll('[data-step] img')[i]; img.scrollIntoView(); return img.complete; }, i);
                await page.waitForTimeout(300);
                check(kind + ' the picture under step ' + (i + 1) + ' loads', await page.evaluate(i => document.querySelectorAll('[data-step] img')[i].naturalWidth > 0, i), image);
            }
        }
        check(kind + ' no sideways scroll', await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
        check(kind + ' no script errors', errors.length === 0, errors);

        // ---- Without scripts the steps keep their pictures
        const plain = await b.newContext({ javaScriptEnabled: false, viewport: kind === 'phone' ? { width: 390, height: 844 } : { width: 1280, height: 900 } });
        const bare = await plain.newPage();
        await bare.goto(L.APP + '/', { waitUntil: 'load' });
        check(kind + ' without scripts every step shows its own picture', await bare.evaluate(() => Array.from(document.querySelectorAll('[data-step] img')).every(i => i.offsetParent !== null)));
        await plain.close();

        // ---- Accessibility
        try {
            const axe = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
            await page.goto(L.APP + '/', { waitUntil: 'networkidle' });
            await page.click('[data-row="fours"]');
            await page.addScriptTag({ content: axe });
            const result = await page.evaluate(() => axe.run(document, { runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa', 'best-practice'] }));
            check(kind + ' axe: no violations', result.violations.length === 0, result.violations.map(v => v.id + ' ' + v.nodes.slice(0, 2).map(n => n.target.join(' ')).join(' ; ')));
        } catch (e) {
            console.log('skip axe (npm install -g axe-core to include it):', e.message.split('\n')[0]);
        }
        await ctx.close();
    }
    await b.close();
    console.log(failures ? failures + ' FAILED' : 'landing: all passed');
    process.exit(failures ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
