// The public scoreboard switched off: the public score sheet has no Everyone panel, never asks for everyone's scores and
// still scores as usual, and a game that has the scoreboard still shows it (so the checks above are not vacuous)
const L = require('./lib');
let pass = 0, fail = 0;
function ok(cond, msg) { if (cond) { pass++; console.log('  ok   ' + msg); } else { fail++; console.log('  FAIL ' + msg); } }
const txt = async (page, sel) => ((await page.textContent(sel)) || '').replace(/\s+/g, ' ').trim();
const row = (s, i) => `[data-section=${s}][data-id=${i}]`;
const settle = (page, ms = 600) => page.waitForTimeout(ms);

(async () => {
    const b = await L.browser();
    const errors = [];
    const watch = p => { p.on('pageerror', e => errors.push('pageerror ' + e.message)); p.on('console', m => { if (m.type() === 'error' && !/status of (4|5)\d\d/.test(m.text())) errors.push('console ' + m.text()); }); };

    // The owner's links for a game, by the first letter of the player's name
    async function linkFor(owner, letter) {
        await owner.goto(L.APP + '/home');
        await owner.click('[data-dialog-open=share-dialog]');
        const links = await owner.$$eval('#share-dialog [data-copy]', els => els.map(e => e.dataset.copy));
        const names = await owner.$$eval('#share-dialog li', els => els.map(e => e.textContent.replace(/\s+/g, ' ').trim()));

        return { link: links[names.findIndex(n => n.startsWith(letter))], count: links.length, dialog: await txt(owner, '#share-dialog') };
    }

    const ownerCtx = await L.context(b, 'laptop');
    const owner = await ownerCtx.newPage(); watch(owner);
    const guestCtx = await L.context(b, 'phone');

    console.log('The scoreboard is switched off');
    await L.scenario('busy');
    L.seedTokens('g-1', [['p-1', 'Ada'], ['p-2', 'Ben'], ['p-3', 'Cleo']], true);
    await L.login(owner);
    let shared = await linkFor(owner, 'B');
    ok(shared.count === 3, 'three links');
    ok(shared.dialog.includes('The scoreboard is hidden on these links'), 'the share dialog tells the owner it is hidden');

    const guest = await guestCtx.newPage(); watch(guest);
    const requested = [];
    guest.on('request', request => requested.push(request.url()));
    await guest.goto(shared.link);
    await guest.waitForSelector('#upper-list');
    await settle(guest, 1500);
    ok((await guest.$('#everyone')) === null && (await guest.$('#everyone-heading')) === null, 'no Everyone panel');
    ok(!requested.some(url => url.includes('/player-scores')), 'everyone\'s scores were never asked for');
    await guest.evaluate(() => document.dispatchEvent(new Event('visibilitychange')));
    await settle(guest);
    ok(!requested.some(url => url.includes('/player-scores')), 'not even when the page comes back into view');
    ok((await txt(guest, '#done-waiting')) === '', 'nothing about who the others are waiting for');
    ok((await txt(guest, '#total')) === '104', 'it is still Ben\'s sheet, on 104');

    const token = shared.link.split('/').pop();
    const status = await guest.evaluate(url => fetch(url, { headers: { Accept: 'application/json' } }).then(r => r.status), L.APP + '/public/game/' + token + '/player-scores');
    ok(status === 403, 'the address of everyone\'s scores answers 403 (' + status + ')');

    await guest.click(row('upper', 'fives'));
    await guest.click('[data-count="2"]');
    await settle(guest);
    const st = await L.get('/__state');
    ok(st.sheets['g-1']['p-2']['upper-section'].fives === 10, 'scoring through the link still saves to Ben\'s sheet');
    ok((await txt(guest, '#total')) === '114', 'total 114');

    console.log('The owner still sees everything');
    await owner.goto(L.APP + '/game/g-1/player/p-1/score-sheet');
    await owner.waitForSelector('#everyone li');
    await settle(owner, 800);
    ok((await txt(owner, '#everyone')).includes('114'), 'Ada\'s Everyone panel shows Ben on 114');

    console.log('A game with the scoreboard still shows it');
    L.seedTokens('g-1', [['p-1', 'Ada'], ['p-2', 'Ben'], ['p-3', 'Cleo']], false);
    shared = await linkFor(owner, 'B');
    ok(!shared.dialog.includes('The scoreboard is hidden'), 'the share dialog says nothing');
    const open = await guestCtx.newPage(); watch(open);
    const openRequests = [];
    open.on('request', request => openRequests.push(request.url()));
    await open.goto(shared.link);
    await open.waitForSelector('#everyone li');
    await settle(open, 800);
    ok((await open.$('#everyone')) !== null, 'the Everyone panel is there');
    ok(openRequests.some(url => url.includes('/player-scores')), 'and it reads everyone\'s scores');

    console.log('Errors: ' + errors.length);
    errors.forEach(e => console.log('  ' + e));
    ok(errors.length === 0, 'no script errors');
    await b.close();
    console.log(`\n${pass} passed, ${fail} failed`);
    process.exit(fail ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
